<?php
class service_close_db_lock extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Close the given lock"; }
	public function input_documentation() { echo "<code>id</code>: the id of the lock to close"; }
	public function output_documentation() { echo "return true on success"; }
	public function execute(&$component) {
		require_once("component/data_model/DataBaseLock.inc");
		$error = DataBaseLock::unlock($_POST["id"]);
		if (!$error)
			echo "true";
		else
			PNApplication::error($error);
	}
}
?>