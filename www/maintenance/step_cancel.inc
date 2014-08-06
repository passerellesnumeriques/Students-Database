<?php 
if (isset($_GET["cancel_db"])) {
	global $db_config;
	require_once("component/PNApplication.inc");
	PNApplication::$instance = new PNApplication();
	PNApplication::$instance->init();
	require_once("DataBaseSystem_".$db_config["type"].".inc");
	$db_system_class = "DataBaseSystem_".$db_config["type"];
	$db_system = new $db_system_class;
	$res = $db_system->connect($db_config["server"], $db_config["user"], $db_config["password"]);
	if ($res <> DataBaseSystem::ERR_OK) die("Error: unable to create a backup because we cannot connect to the database");
	$domains = include("conf/domains");
	$path = realpath("data/update_backup");
	foreach ($domains as $domain=>$conf) {
		PNApplication::$instance->local_domain = $domain;
		PNApplication::$instance->current_domain = $domain;
		// remove tables
		$res = $db_system->execute("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';') FROM information_schema.tables WHERE table_schema = 'students_$domain'");
		$db_system->execute("USE students_".$domain);
		while (($sql = $db_system->nextRowArray($res)) <> null)
			$db_system->execute($sql[0]);
		// init database
		require_once("component/data_model/DataBaseUtilities.inc");
		$ref = new ReflectionClass("DataModel");
		$p = $ref->getProperty("tables");
		$p->setAccessible(true);
		$tables = $p->getValue($model);
		foreach ($tables as $table)
			DataBaseUtilities::createTable($db_system, $table);
		// put back data
		$res = $db_system->execute("SELECT concat('LOAD DATA INFILE \\'".str_replace("\\","/",$path."/$domain/")."',table_name,'\\' INTO TABLE ',table_name,';') FROM information_schema.tables WHERE table_schema = 'students_$domain'");
		$db_system->execute("USE students_".$domain);
		while (($sql = $db_system->nextRowArray($res)) <> null) {
			set_time_limit(600);
			$db_system->execute($sql[0]);
		}
	}
	if (PNApplication::hasErrors())
		PNApplication::printErrors();
		
	echo "Recovery of database done.";
	echo "<script type='text/javascript'>location.href='?step=destroy_sessions';</script>";
} else {
	echo "Recovering previous database...";
	echo "<script type='text/javascript'>location.href='?step=cancel&cancel_db=true';</script>";
}
?>