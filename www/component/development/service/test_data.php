<?php 
class service_test_data extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		$domain = $input["domain"];
		
		$db_conf = include("conf/local_db");
		require_once("DataBaseSystem_".$db_conf["type"].".inc");
		$db_system_class = "DataBaseSystem_".$db_conf["type"];
		$db_system = new $db_system_class;
		$res = $db_system->connect($db_conf["server"], $db_conf["user"], $db_conf["password"]);
		if ($res <> DataBaseSystem::ERR_OK) {
			switch ($res) {
				case DataBaseSystem::ERR_CANNOT_CONNECT_TO_SERVER: PNApplication::error("Unable to connect to the database server"); break;
				case DataBaseSystem::ERR_INVALID_CREDENTIALS: PNApplication::error("Invalid credentials to connect to the database server"); break;
				default: PNApplication::error("Unknown result when connecting to the database server"); break;
			}
		} else {
			set_time_limit(240);
 			$db_system->execute("USE students_".$domain);
 			$components = PNApplication::sortComponentsByDependencies();
 			if ($domain == PNApplication::$instance->local_domain) {
	 			foreach ($components as $c) {
	 				if (file_exists("component/".$c->name."/init_data.inc"))
	 					include("component/".$c->name."/init_data.inc");
	 			}
 			}
			$this->SplitSQL($db_system, "component/development/data/countries.sql");
			$this->SplitSQL($db_system, "component/development/data/countrydivision.sql");
			$this->SplitSQL($db_system, "component/development/data/geographicarea.sql");
			if ($domain == "Dev") {
				$this->SplitSQL($db_system, "component/development/data/curriculumsubjectcategory.sql");
				$this->SplitSQL($db_system, "component/development/data/specialization.sql");
				$this->SplitSQL($db_system, "component/development/data/organization.sql");
				$this->SplitSQL($db_system, "component/development/data/PNP_AcademicYearsAndBatches.sql");
				$this->SplitSQL($db_system, "component/development/data/PNP_Batch2012_Curriculum.sql");
				$this->SplitSQL($db_system, "component/development/data/PNP_Batch2012.sql");
				$this->importStorage($db_system, "PNP_Batch2012", $domain);
				$this->SplitSQL($db_system, "component/development/data/PNP_Batch2013.sql");
				$this->importStorage($db_system, "PNP_Batch2013", $domain);
				// generate events accordingly to data added
				PNApplication::$instance->user_management->login("Dev", "admin", "");
				$model = DataModel::get();
				foreach ($model->internalGetTables() as $t) {
					if ($t->getModel() instanceof SubDataModel) continue;
					$rows = SQLQuery::create()->bypassSecurity()->noWarning()->select($t->getName())->execute();
					foreach ($rows as $row)
						$t->fireInsert($row, @$row[$t->getPrimaryKey()->name], null);
				}
				
				PNApplication::$instance->user_management->logout();
			}
		}		
	}
	
	private function SplitSQL(&$db_system, $file, $delimiter = ';') {
		set_time_limit(0);
		if (is_file($file) === true) {
			$file = fopen($file, 'r');
			if (is_resource($file) === true) {
				$query = array();
				while (feof($file) === false) {
					$query[] = utf8_decode(fgets($file));
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
			
			$path = realpath(dirname($_SERVER["SCRIPT_FILENAME"]))."/data/$domain";
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