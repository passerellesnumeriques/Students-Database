<?php
class service_lock_rows extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() { echo "Request a lock for a specific row in a table"; }
	public function inputDocumentation() {
?>
<ul>
	<li><code>table</code>: table name of the entity</li>
	<li><code>row_keys</code>: primary keys value of the rows to lock</li>
	<li><code>sub_model</code>: (optional) submodel</li>
</ul>
<?php
	}
	public function outputDocumentation() {
?>
<ul>
	<li>array of lock id</li>
</ul>
<?php
	}
	public function execute(&$component, $input) {
		$table = $input["table"];
		$keys = $input["row_keys"];
		$sm = @$input["sub_model"];
		require_once("component/data_model/DataBaseLock.inc");
		require_once("component/data_model/Model.inc");
		$model = DataModel::get();
		$table = $model->getTable($table); // here check is done is the user can access this table
		$locked_by = null;
		$locks = DataBaseLock::lockRows($table->getSQLNameFor($sm), $keys, $locked_by);
		if ($locks == null) {
			PNApplication::error("Some data are already locked by ".$locked_by);
			return;
		}
		echo json_encode($locks);
	}
} 
?>