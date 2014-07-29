<?php 
class service_save_datadisplay extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Save or create one or more DataDisplay"; }
	public function inputDocumentation() {
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
	public function outputDocumentation() { echo "return the key on success"; }
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		$table = $input["table"];
		$sub_model = @$input["sub_model"];
		$t = DataModel::get()->getTable($table);
		$come_from = @$input["come_from"];
		$display = DataModel::get()->getTableDataDisplay($table);
		if ($display == null) {
			PNApplication::error("No TableDataDisplay on table ".$table);
			return;
		}
		$key = @$input["key"];
		if (isset($input["name"]))
			$list = array(array("name"=>$input["name"],"data"=>$input["data"]));
		else
			$list = $input["data"];
		$tables_fields = new TablesToUpdate(); 
		foreach ($list as $data_to_save) {
			$found = false;
			foreach ($display->getDataDisplay($come_from, $sub_model) as $data) {
				if ($data->getDisplayName() == $data_to_save["name"]) {
					$data->saveData($key, $data_to_save["data"], $sub_model, $tables_fields, null, null);
					$found = true;
					break;
				}
			}
			if (!$found) PNApplication::error("Unknown DataDisplay ".$data_to_save["name"]." on table ".$table);
		}
		$keys = $tables_fields->execute();
		if ($key == null) $key = $keys[$table][$sub_model][null];
		echo "{key:".$key."}";
	}
	
}
?>