<?php 
class service_create_datamodel extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		$domain = $input["domain"];
		$version = $input["version"];
		
		if ($version == "current") {
			require_once("component/data_model/Model.inc");
			require_once("component/data_model/DataBaseUtilities.inc");
			$model = DataModel::get();
			$ref = new ReflectionClass("DataModel");
			$p = $ref->getProperty("tables");
			$p->setAccessible(true);
			$tables = $p->getValue($model);
			$sql = "";
			foreach ($tables as $table) {
				$sql .= DataBaseUtilities::createTable(SQLQuery::getDataBaseAccessWithoutSecurity(), $table, null, true);
				$sql .= ";\n";
			}
		} else {
			if (!file_exists("data/datamodels/datamodel_".$version.".sql")) {
				if (!file_exists("data/datamodels/Students_Management_Software_".$version."_datamodel.zip")) {
					PNApplication::error("DataModel version ".$version." is not available");
					return;
				}
				try {
					@unlink("data/datamodels/datamodel.sql");
					$zip = new ZipArchive();
					$zip->open("data/datamodels/Students_Management_Software_".$version."_datamodel.zip");
					$zip->extractTo("data/datamodels", "datamodel.sql");
					$zip->close();
					rename("data/datamodels/datamodel.sql", "data/datamodels/datamodel_$version.sql");
				} catch (Exception $e) {
					@unlink("data/datamodels/Students_Management_Software_".$version."_datamodel.zip");
					PNApplication::error("Invalid datamodel ZIP file, we removed it, please download it again.", $e);
					return;
				}
				if (!file_exists("data/datamodels/datamodel_".$version.".sql")) {
					@unlink("data/datamodels/Students_Management_Software_".$version."_datamodel.zip");
					PNApplication::error("Invalid datamodel ZIP file, we removed it, please download it again.");
				}
			}
			$sql = file_get_contents("data/datamodels/datamodel_".$version.".sql");
		}
		
		// connect to Database
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
		$db_system->execute("USE ".$db_config["prefix"].$domain);
		// execute SQL
		$queries = explode("\n",$sql);
		foreach ($queries as $q) {
			if (trim($q) == "") continue;
			set_time_limit(300);
			$db_system->execute($q);
		}
		echo "true";	
	}
	
}
?>