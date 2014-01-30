<?php 
class service_unlock extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Release a lock"; }
	public function input_documentation() { 
		echo "<code>lock</code>: id of the lock to release";
		echo "<br/>or<br/>"; 
		echo "<code>locks</code>: list of locks' id to release";
	}
	public function output_documentation() { echo "return true on success"; }
	public function execute(&$component, $input) {
		require_once("component/data_model/DataBaseLock.inc");
		$error = null;
		if (isset($input["lock"]))
			$error = DataBaseLock::unlock($input["lock"]);
		if (isset($input["locks"]))
			$error = DataBaseLock::unlockMultiple($input["locks"]);
		if ($error <> null) PNApplication::error($error);
		echo PNApplication::has_errors() ? "false" : "true";
	}
}
?>