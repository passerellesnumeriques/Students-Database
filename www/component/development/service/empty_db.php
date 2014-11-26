<?php 
class service_empty_db extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		$domain = $input["domain"];

		// connect to DataBase
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
			return;
		}
		// empty database
		set_time_limit(240);
		global $db_config;
		$res = $db_system->execute("CREATE DATABASE IF NOT EXISTS ".$db_config["prefix"].$domain." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
		$res = $db_system->execute("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';') FROM information_schema.tables WHERE table_schema = '".$db_config["prefix"].$domain."'");
		$db_system->execute("USE ".$db_config["prefix"].$domain);
		while (($sql = $db_system->nextRowArray($res)) <> null)
			$db_system->execute($sql[0]);
		echo "true";
	}
	
}
?>