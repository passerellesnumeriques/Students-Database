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
		
		$db_conf = include("conf/local_db");
		require_once("DataBaseSystem_".$db_conf["type"].".inc");
		$db_system_class = "DataBaseSystem_".$db_conf["type"];
		/* @var $db_system DataBaseSystem */
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
			$model = DataModel::get();
 			$res = $db_system->execute("DROP DATABASE students_".$domain, false);
 			$res = $db_system->execute("CREATE DATABASE students_".$domain);
 			$res = $db_system->execute("USE students_".$domain);
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