<?php
class service_remove_row_with_no_primary extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Remove an entry in the given table which has no primary key"; }
	public function input_documentation() {
?>
<ul>
	<li><code>table</code>: table name of the row to remove</li>
	<li><code>fields</code>: {array} [{col1:val1},{col2:val2}]</li>
</ul>
<?php		
	}
	public function output_documentation() { echo "return true on success"; }
	public function execute(&$component, $input) {
		$table = $input["table"];
		$fields = $input["fields"];
		$final = array();
		foreach($fields as $f){
			foreach($f as $field => $value){
				$final[$field] = $value;
			}
		}
		require_once("component/data_model/Model.inc");
		try {
			SQLQuery::create()->remove($table, $final);
		} catch (Exception $e) {
			PNApplication::error($e->getMessage());
		}
		echo PNApplication::has_errors() ? "false" : "true";
	}
}
?>