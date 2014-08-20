<?php 
class service_test_data extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		$domain = $input["domain"];
		
		global $db_config;
		require_once("DataBaseSystem_".$db_config["type"].".inc");
		$db_system_class = "DataBaseSystem_".$db_config["type"];
		$db_system = new $db_system_class;
		$res = $db_system->connect($db_config["server"], $db_config["user"], $db_config["password"]);
		if ($res <> DataBaseSystem::ERR_OK) {
			switch ($res) {
				case DataBaseSystem::ERR_CANNOT_CONNECT_TO_SERVER: PNApplication::error("Unable to connect to the database server"); break;
				case DataBaseSystem::ERR_INVALID_CREDENTIALS: PNApplication::error("Invalid credentials to connect to the database server"); break;
				default: PNApplication::error("Unknown result when connecting to the database server"); break;
			}
		} else {
			set_time_limit(240);
 			$db_system->execute("USE students_".$domain);
			$this->SplitSQL($db_system, "component/development/data/organization.sql");
			$this->SplitSQL($db_system, "component/development/data/labtable.sql");
			//$this->SplitSQL($db_system, "component/development/data/students_pnc.sql");
			$this->SplitSQL($db_system, "component/development/data/PNP_AcademicYearsAndBatches.sql");
			$this->SplitSQL($db_system, "component/development/data/PNP_Batch2012_Curriculum.sql");
			$this->SplitSQL($db_system, "component/development/data/PNP_Batch2012.sql");
			$this->importStorage($db_system, "PNP_Batch2012", $domain);
			$this->SplitSQL($db_system, "component/development/data/PNP_Batch2013.sql");
			$this->importStorage($db_system, "PNP_Batch2013", $domain);
			$this->SplitSQL($db_system, "component/development/data/PNP_Batch2014.sql");
			$this->importStorage($db_system, "PNP_Batch2014", $domain);
			$this->SplitSQL($db_system, "component/development/data/PNP_Batch2015.sql");
			// generate events accordingly to data added
			PNApplication::$instance->user_management->login($input["domain"], "admin", $input["password"]);
			
			require_once("component/data_model/Model.inc");
			$model = DataModel::get();
			/*
			foreach ($model->internalGetTables() as $t) {
				if ($t->getModel() instanceof SubDataModel) continue;
				if (!$t->hasInsertListeners()) continue;
				$rows = SQLQuery::create()->bypassSecurity()->noWarning()->select($t->getName())->execute();
				foreach ($rows as $row) {
					$key = $t->getPrimaryKey();
					if ($key == null) {
						$keys = $t->getKey();
						$key = array();
						foreach ($keys as $colname) $key[$colname] = $row[$colname];
					} else {
						$key = $row[$key->name];
					}
					$t->fireInsert($row, $key, null);
				}
			}*/
			
			// create a user without any right
			$people_id = PNApplication::$instance->people->createPeople(array("first_name"=>"Guest","last_name"=>"No right","sex"=>"M"), array("user"), true);
			PNApplication::$instance->user_management->createInternalUser("guest", "", $people_id);

			// create a selection staff, who can access in read-only to all selection data
			$people_id = PNApplication::$instance->people->createPeople(array("first_name"=>"Selection","last_name"=>"Staff","sex"=>"F"), array("user","staff"), true);
			SQLQuery::create()->bypassSecurity()->noWarning()->insert("Staff", array("people"=>$people_id));
			$selection_department_id = SQLQuery::create()->noWarning()->bypassSecurity()->select("PNDepartment")->whereValue("PNDepartment","name","Selection")->field("id")->executeSingleValue();
			SQLQuery::create()->bypassSecurity()->noWarning()->insert("StaffPosition", array("people"=>$people_id,"department"=>$selection_department_id,"start"=>"2010-01-01","end"=>null,"position"=>"Selection Intern"));
			PNApplication::$instance->user_management->createInternalUser("selection_staff", "", $people_id);
			
			PNApplication::$instance->user_management->logout();
		}		
	}
	
	private function SplitSQL(&$db_system, $file, $delimiter = ';') {
		set_time_limit(0);
		if (is_file($file) === true) {
			$file = fopen($file, 'r');
			if (is_resource($file) === true) {
				$query = array();
				while (feof($file) === false) {
					$query[] = fgets($file);
					if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1) {
						$query = trim(implode('', $query));
						$db_system->execute($query);
					}
					if (is_string($query) === true)
						$query = array();
				}
				return fclose($file);
			}
		}
		return false;
	}
	
	private function importStorage(&$db_system, $name, $domain) {
		set_time_limit(240);
		$src_path = realpath(dirname($_SERVER["SCRIPT_FILENAME"])."/component/development/data/storage/$name");
		$dir = opendir($src_path);
		while (($filename = readdir($dir)) <> null) {
			if (is_dir($src_path."/".$filename)) continue;
			if ($filename == "insert.sql") continue;
			
			$id = $filename;
			$dir1 = $id%100;
			$dir2 = ($id/100)%100;
			$dir3 = ($id/10000)%100;
			$filename = intval($id/1000000);
			
			$path = realpath(dirname($_SERVER["SCRIPT_FILENAME"]))."/data/$domain/storage";
			$path .= "/$dir1";
			@mkdir($path);
			$path .= "/$dir2";
			@mkdir($path);
			$path .= "/$dir3";
			@mkdir($path);
			
			copy($src_path."/".$id, $path."/".$filename);	
		}
		closedir($dir);
		$this->SplitSQL($db_system, $src_path."/insert.sql");
	}
	
}
?>