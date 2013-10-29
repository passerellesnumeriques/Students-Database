<?php
class service_lock_table extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Request a lock for a specific table"; }
	public function input_documentation() {
?>
<ul>
	<li><code>table</code>: table name of the entity</li>
	<li><code>sub_model</code>: optionnaly, the sub model instance</li>
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
		$sub_model = @$input["sub_model"];
		require_once("component/data_model/DataBaseLock.inc");
		require_once("component/data_model/Model.inc");
		$model = DataModel::get();
		$table = $model->getTable($table); // here check is done is the user can access this table
		if (!$table->canAdd() || !$table->canRemove()) {
			PNApplication::error("Access denied to add or remove in table '".$table->getName()."'");
			return;
		}
		$locked_by = null;
		$lock = DataBaseLock::lock_table($table->getSQLNameFor($sub_model), $locked_by);
		if ($lock == null) {
			PNApplication::error("This table is already locked by ".$locked_by);
			return;
		}
		echo "{lock:".json_encode($lock)."}";
	}
} 
?>