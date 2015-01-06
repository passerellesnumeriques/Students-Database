<?php
class service_add_row extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() { echo "Add a row to a table"; }
	public function inputDocumentation() {
?>
<ul>
	<li><code>table</code>: table name of the cell to save</li>
	<li><code>sub_model</code>: optional, only if the table is in a sub model</li>
	<li><code>columns</code>: column name/value</li>
</ul>
<?php		
	}
	public function outputDocumentation() { echo "id"; }
	public function execute(&$component, $input) {
		$table = $input["table"];
		$columns = $input["columns"];
		require_once("component/data_model/Model.inc");
		try {
			$q = SQLQuery::create();
			$sub_model = @$input["sub_model"];
			if ($sub_model <> null) $q->selectSubModelForTable(DataModel::get()->getTable($table), $sub_model);
			$id = $q->insert($table, $columns);
			echo "{id:".json_encode($id)."}";
		} catch (Exception $e) {
			PNApplication::error($e->getMessage());
		}
	}
}
?>