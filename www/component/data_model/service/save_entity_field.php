<?php
class service_save_entity_field extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Save the value of a specific cell of a table"; }
	public function input_documentation() {
?>
<ul>
	<li><code>table</code>: table name of the cell to save</li>
	<li><code>field</code>: column name of the cell to save</li>
	<li><code>key</code>: primary key of the row to save</li>
	<li><code>value</code>: value to save</li>
	<li><code>lock</code>: id of a lock which is locking at least the cell to save</li>
</ul>
<?php		
	}
	public function output_documentation() { echo "return true on success"; }
	public function execute(&$component) {
		$table = $_POST["table"];
		$key = $_POST["key"];
		$field = $_POST["field"];
		$value = $_POST["value"];
		$lock_id = $_POST["lock"];
		require_once("component/data_model/Model.inc");
		try {
			$table = DataModel::get()->getTable($table);
			$table->update_by_key($key, array($field=>$value), null, $lock_id);
		} catch (Exception $e) {
			PNApplication::error($e->getMessage());
		}
		echo PNApplication::has_errors() ? "false" : "true";
	}
}
?>