<?php
class service_lock_row extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Request a lock for a specific row in a table"; }
	public function input_documentation() {
?>
<ul>
	<li><code>table</code>: table name of the entity</li>
	<li><code>row_key</code>: primary key value of the row to lock</li>
</ul>
<?php
	}
	public function output_documentation() {
?>
<ul>
	<li><code>lock</code>: id of the lock</li>
</ul>
<?php
	}
	public function execute(&$component, $input) {
		$table = $input["table"];
		$key = $input["row_key"];
		require_once("component/data_model/DataBaseLock.inc");
		require_once("component/data_model/Model.inc");
		$model = DataModel::get();
		$table = $model->getTable($table); // here check is done is the user can access this table
		$locked_by = null;
		$lock = DataBaseLock::lock_row($table->getName(), $key, $locked_by);
		if ($lock == null) {
			PNApplication::error("This row is already locked by ".$locked_by);
			return;
		}
		echo "{lock:".json_encode($lock)."}";
	}
} 
?>