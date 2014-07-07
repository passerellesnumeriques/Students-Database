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
	
}
?>