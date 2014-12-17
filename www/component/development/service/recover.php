<?php 
class service_recover extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		require_once("component/application/Backup.inc");
		if (!isset($input["datamodel_version"]) || $input["datamodel_version"] == "current") {
			if (isset($input["imported_from"])) $path = "data/imported_backups/".$input["imported_from"];
			else $path = "data/backups";
			Backup::recoverBackup($input["time"], $input["version"], $path);
		} else
			Backup::importBackup($input["time"], $input["version"], $input["datamodel_version"]);
		echo "true";
	}
}
?>