<?php 
class service_create_db extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		$domain = $input["domain"];
		require_once("component/data_model/Model.inc");
		require_once("component/data_model/DataBaseUtilities.inc");
		
		global $db_config;
		require_once("DataBaseSystem_".$db_config["type"].".inc");
		$db_system_class = "DataBaseSystem_".$db_config["type"];
		/* @var $db_system DataBaseSystem */
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
			$model = DataModel::get();
 			$res = $db_system->execute("CREATE DATABASE IF NOT EXISTS students_".$domain." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
 			$res = $db_system->execute("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';') FROM information_schema.tables WHERE table_schema = 'students_$domain'");
 			$db_system->execute("USE students_".$domain);
 			while (($sql = $db_system->nextRowArray($res)) <> null)
 				$db_system->execute($sql[0]);
 			$prev_local = PNApplication::$instance->local_domain;
 			$prev_current = PNApplication::$instance->current_domain;
 			PNApplication::$instance->local_domain = $domain;
 			PNApplication::$instance->current_domain = $domain;
 			
 			$ref = new ReflectionClass("DataModel");
 			$p = $ref->getProperty("tables");
 			$p->setAccessible(true);
 			$tables = $p->getValue($model);
 			foreach ($tables as $table)
 				DataBaseUtilities::createTable($db_system, $table);
 			
 			PNApplication::$instance->local_domain = $prev_local;
 			PNApplication::$instance->current_domain = $prev_current;
// 			$res = $db_system->execute("SHOW TABLES");
// 			if ($res !== FALSE) {
// 				while (($table = $db_system->nextRow($res)) !== FALSE) {
// 					$db_system->execute("DROP TABLE `".$table[0]."`");
// 				}
// 				DataBaseModel::update_model($model);
// 			} else
// 				PNApplication::error("Failed to get the list of tables)");
		}		
	}
	
}
?>