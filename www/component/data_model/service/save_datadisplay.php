<?php 
class service_save_datadisplay extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Save or create one or more DataDisplay"; }
	public function input_documentation() {
		echo "<ul>";
		echo "<li><code>table</code>: the table containing the DataDisplay</li>";
		echo "<li><code>sub_model</code>: the sub model if any</li>";
		echo "<li><code>come_from</code>: the entry point to the table, if any</li>";
		echo "<li><code>key</code>: the key representing a single data, or null to create a new data</li>";
		echo "<li><ul>";
			echo "<li><code>name</code>, <code>data</code>: the name and data of the DataDisplay, for a single data to be saved</li>";
			echo "<li><code>data:[{name:x,data:y}]</code>: the list of data to save or create</li>";
		echo "</ul></li>";
		echo "</ul>";
	}
	public function output_documentation() { echo "return the key on success"; }
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		$table = $input["table"];
		$sub_model = @$input["sub_model"];
		$t = DataModel::get()->getTable($table);
		$come_from = @$input["come_from"];
		$display = $t->getDisplayHandler($come_from);
		if ($display == null) {
			PNApplication::error("No DataDisplayHandler on table ".$table);
			return;
		}
		$key = @$input["key"];
		if (isset($input["name"]))
			$list = array("name"=>$input["name"],"data"=>$input["data"]);
		else
			$list = $input["data"]; 
		if ($key == null)
			$key = $display->createEntry($list, $sub_model);
		else
			foreach ($list as $data_to_save) {
				$found = false;
				foreach ($display->getDisplayableData() as $data) {
					if ($data->getDisplayName() == $data_to_save["name"]) {
						$data->saveData($key, $data_to_save["data"], $sub_model);
						$found = true;
						break;
					}
				}
				if (!$found) PNApplication::error("Unknown DataDisplay ".$data_to_save["name"]." on table ".$table);
			}
		echo "{key:".$key."}";
	}
	
}
?>