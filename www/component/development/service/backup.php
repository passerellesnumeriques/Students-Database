<?php 
class service_backup extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function execute(&$component, $input) {
		require_once("component/application/Backup.inc");
		$time = Backup::createBackup();
		global $pn_app_version;
		echo "{version:".json_encode($pn_app_version).",time:".$time."}";
	}
}
?>