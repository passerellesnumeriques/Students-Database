<?php
class service_save_row_with_no_primary extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Save an entry in the given table which has no primary key"; }
	public function input_documentation() {
?>
<ul>
	<li><code>table</code>: table name of the row to save</li>
	<li><code>where</code>: {array} [{col1:val1},{col2:val2}]</li>
	<li><code>new</code>: {boolean} (optional) if true, this service will perform an insert instead of an update</li>
	<li><i>fields to save</i>: each name must start with 'field_' to avoid collision with other input parameters.</li>
</ul>
<?php		
	}
	public function output_documentation() { echo "return true on success"; }
	public function execute(&$component, $input) {
		$table = $input["table"];
		$where = $input["where"];
		$new = false;
		$fields = array();
		if(isset($input["new"])) $new = $input["new"];
		
		$final = array();
		foreach($where as $f){
			foreach($f as $field => $value){
				$final[$field] = $value;
			}
		}
		
		require_once("component/data_model/Model.inc");
		
		$t = DataModel::get()->getTable($table);
		
		/* We don't handle the submodels */
		foreach ($t->getColumns(null) as $col) {
			if (!isset($input["field_".$col->name])) continue;
			$fields[$col->name] = $input["field_".$col->name];
		}
		
		try {
			if($new) SQLQuery::create()->insert($table, $fields);
			else SQLQuery::create()->update($table, $fields , $final);
		} catch (Exception $e) {
			PNApplication::error($e->getMessage());
		}
		echo PNApplication::has_errors() ? "false" : "true";
	}
}
?>