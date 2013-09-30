<?php
class service_update_db_lock extends Service {
	public function get_required_rights() {
		return array();
	}
	public function documentation() { echo "update the given lock (extends its expiration time)"; }
	public function input_documentation() { echo "<code>id</code>: the id of the lock to extend"; }
	public function output_documentation() { echo "return true on success"; }
	public function execute(&$component, $input) {
		require_once("component/data_model/DataBaseLock.inc");
		$error = DataBaseLock::update($input["id"]);
		if (!$error)
			echo "true";
		else
			PNApplication::error($error);
	}
} 
?>