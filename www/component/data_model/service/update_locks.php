<?php
class service_update_locks extends Service {
	public function getRequiredRights() {
		return array();
	}
	public function documentation() { echo "update the given locks (extends expiration time)"; }
	public function inputDocumentation() { echo "<code>locks</code>: the id of the locks to extend"; }
	public function outputDocumentation() { echo "return true on success"; }
	public function execute(&$component, $input) {
		require_once("component/data_model/DataBaseLock.inc");
		DataBaseLock::updateLocks($input["locks"]);
		echo "true";
	}
} 
?>