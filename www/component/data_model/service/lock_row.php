<?php
class service_lock_row extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() { echo "Request a lock for a specific row in a table"; }
	public function inputDocumentation() {
?>
<ul>
	<li><code>table</code>: table name of the entity</li>
	<li><code>row_key</code>: primary key value of the row to lock</li>
	<li><code>sub_model</code>: (optional) submodel</li>
</ul>
<?php
	}
	public function outputDocumentation() {
?>
<ul>
	<li><code>lock</code>: id of the lock</li>
</ul>
<?php
	}
	public function execute(&$component, $input) {
		$table = $input["table"];
		$key = $input["row_key"];
		$sm = @$input["sub_model"];
		require_once("component/data_model/DataBaseLock.inc");
		require_once("component/data_model/Model.inc");
		$model = DataModel::get();
		$table = $model->getTable($table); // here check is done is the user can access this table
		$locked_by = null;
		$lock = DataBaseLock::lockRow($table->getSQLNameFor($sm), $key, $locked_by);
		if ($lock == null) {
			PNApplication::error("This is already edited by ".$locked_by.", you cannot edit it at the samt time.");
			return;
		}
		echo "{lock:".json_encode($lock)."}";
	}
} 
?>