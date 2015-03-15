<?php 
class service_recover extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		PNApplication::$instance->development->current_request()->no_process_time_warning = true;
		PNApplication::$instance->development->current_request()->no_database_warning = true;
		
		require_once("component/application/Backup.inc");
		if (!isset($input["datamodel_version"]) || $input["datamodel_version"] == "current") {
			if (isset($input["imported_from"])) $path = "data/imported_backups/".$input["imported_from"];
			else $path = "data/backups";
			Backup::recoverBackup($input["time"], $input["version"], $path);
		} else
			Backup::importBackup($input["time"], $input["version"], $input["datamodel_version"], PNApplication::$instance->local_domain);
		echo "true";
	}
}
?>