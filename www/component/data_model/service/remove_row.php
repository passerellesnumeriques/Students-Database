<?php
class service_remove_row extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Remove an entry in the given table"; }
	public function input_documentation() {
?>
<ul>
	<li><code>table</code>: table name of the row to remove</li>
	<li><code>row_key</code>: primary key of the row to remove</li>
</ul>
<?php		
	}
	public function output_documentation() { echo "return true on success"; }
	public function execute(&$component) {
		$table = $_POST["table"];
		$key = $_POST["row_key"];
		require_once("component/data_model/Model.inc");
		try {
			SQLQuery::create()->remove_key($table, $key);
		} catch (Exception $e) {
			PNApplication::error($e->getMessage());
		}
		echo PNApplication::has_errors() ? "false" : "true";
	}
}
?>