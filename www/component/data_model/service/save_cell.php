<?php
class service_save_cell extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() { echo "Save the value of a specific cell of a table"; }
	public function inputDocumentation() {
?>
<ul>
	<li><code>table</code>: table name of the cell to save</li>
	<li><code>column</code>: column name of the cell to save</li>
	<li><code>row_key</code>: primary key of the row to save</li>
	<li><code>value</code>: value to save</li>
	<li><code>sub_model</code>: optional, only if the table is in a sub model</li>
	<li><code>lock</code>: id of a lock which is locking at least the cell to save</li>
</ul>
<?php		
	}
	public function outputDocumentation() { echo "return true on success"; }
	public function execute(&$component, $input) {
		$table = $input["table"];
		$key = $input["row_key"];
		$field = $input["column"];
		$value = $input["value"];
		$lock_id = $input["lock"];
		require_once("component/data_model/Model.inc");
		try {
			$q = SQLQuery::create();
			$sub_model = @$input["sub_model"];
			if ($sub_model <> null) $q->selectSubModelForTable(DataModel::get()->getTable($table), $sub_model);
			$q->updateByKey($table, $key, array($field=>$value), $lock_id);
		} catch (Exception $e) {
			PNApplication::error($e->getMessage());
		}
		echo PNApplication::hasErrors() ? "false" : "true";
	}
}
?>