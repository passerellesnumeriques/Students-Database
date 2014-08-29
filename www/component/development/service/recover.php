<?php 
class service_recover extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		require_once("component/application/Backup.inc");
		Backup::recoverBackup($input["time"], $input["version"]);
		echo "true";
	}
}
?>