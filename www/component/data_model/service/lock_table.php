<?php
class service_lock_table extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() { echo "Request a lock for a specific table"; }
	public function inputDocumentation() {
?>
<ul>
	<li><code>table</code>: table name of the entity</li>
	<li><code>sub_model</code>: optionnaly, the sub model instance</li>
	<li><code>get_locker</code>: optionnaly, if true, in case the table is already locked by someone, the locker is returned instead of generating an error</li>
</ul>
<?php
	}
	public function outputDocumentation() {
?>
<ul>
	<li><code>lock</code>: id of the lock</li>
	<li><code>locker</code>: if <code>get_locker</code> was specified, contains the locker, or null if the table has been successfully locked</li>
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
		if (!$table->mayInsert() || !$table->mayRemove()) {
			PNApplication::error("Access denied to add or remove in table '".$table->getName()."'");
			return;
		}
		$locked_by = null;
		$lock = DataBaseLock::lockTable($table->getSQLNameFor($sub_model), $locked_by);
		if ($lock == null) {
			if (isset($input["get_locker"]) && $input["get_locker"])
				echo "{locker:".json_encode($locked_by)."}";
			else
				PNApplication::error("This table is already locked by ".$locked_by);
			return;
		}
		echo "{lock:".json_encode($lock)."}";
	}
} 
?>