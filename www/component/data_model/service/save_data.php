<?php
class service_save_data extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Save data into database"; }
	public function input_documentation() {
?>
<code>to_save</code>: an array of data to save, with for each element:
<ul>
	<li><code>table</code></li>
	<li><code>sub_model</code></li>
	<li><code>column</code></li>
	<li><code>row_key_name</code></li>
	<li><code>row_key_value</code></li>
	<li><code>value</code></li>
</ul>
<?php		
	}
	public function output_documentation() { echo "return true on success"; }
	public function execute(&$component) {
		$to_save = json_decode($_POST["to_save"], true);
		$sub_models = array();
		foreach ($to_save as $t) {
			if (!isset($sub_models[$t["sub_model"]]))
				$sub_models[$t["sub_model"]] = array();
			if (!isset($sub_models[$t["sub_model"]][$t["table"]]))
				$sub_models[$t["sub_model"]][$t["table"]] = array();
			if (!isset($sub_models[$t["sub_model"]][$t["table"]][$t["row_key_name"]]))
				$sub_models[$t["sub_model"]][$t["table"]][$t["row_key_name"]] = array();
			if (!isset($sub_models[$t["sub_model"]][$t["table"]][$t["row_key_name"]][$t["row_key_value"]]))
				$sub_models[$t["sub_model"]][$t["table"]][$t["row_key_name"]][$t["row_key_value"]] = array();
			$sub_models[$t["sub_model"]][$t["table"]][$t["row_key_name"]][$t["row_key_value"]][$t["column"]] = $t["value"];
		}
		require_once("component/data_model/Model.inc");
		foreach ($sub_models as $sub_model=>$for_sub_model) {
			foreach ($for_sub_model as $table=>$for_table) {
				$t = DataModel::get()->getTable($table);
				foreach ($for_table as $row_key_name=>$for_row_key_name) {
					foreach ($for_row_key_name as $row_key_value=>$to_update) {
						$q = SQLQuery::create();
						$sub_models = null;
						if ($sub_model <> null)
							$sub_models = array($t->getModel()->getParentTable() => $sub_model);
						if ($t->getPrimaryKey()->name == $row_key_name)
							$q->update_by_key($table, $row_key_value, $to_update, $sub_models);
						else
							$q->update($table, $to_update, array($row_key_name=>$row_key_value), $sub_models);
					}
				}
			}
		}
		echo "true";
	}
}
?>