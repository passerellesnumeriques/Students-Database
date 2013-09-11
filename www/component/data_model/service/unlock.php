<?php 
class service_unlock extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Release a lock"; }
	public function input_documentation() { echo "<code>lock</code>: id of the lock to release"; }
	public function output_documentation() { echo "return true on success"; }
	public function execute(&$component) {
		$lock = $_POST["lock"];
		require_once("component/data_model/DataBaseLock.inc");
		$error = DataBaseLock::unlock($lock);
		if ($error == null) echo "true";
		else PNApplication::error($error);
	}
}
?>