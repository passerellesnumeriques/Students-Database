<?php 
class service_recover extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		require_once("component/application/Backup.inc");
		Backup::recoverBackup($_GET["time"], $_GET["version"]);
		echo "true";
	}
}
?>