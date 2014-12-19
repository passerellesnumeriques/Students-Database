<?php 
class service_get_importable_data extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve a list of DataDisplay that can be imported from the given table"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>root</code>: the table where to start</li>";
		echo "<li><code>sub_model</code>: optional, the sub model of <code>root</code></li>";
		echo "<li><code>known_columns</code>: optional, the values of columns we know, to help find data</li>";
		echo "</ul>";
	}
	public function outputDocumentation() {
		echo "An array of {data,path} with data being a DataDisplay and path a DataPath";
	}
	
	public function execute(&$component, $input) {
		$root_table = $input["root"];
		$sub_model = @$input["sub_model"];
		$known_columns = @$input["known_columns"];
		$fields = PNApplication::$instance->data_model->getAvailableFields($root_table, $sub_model,false,null,$known_columns,true);
		echo "[";
		$first = true;
		foreach ($fields as $f) {
			$data = $f[0];
			if (!$data->isEditableForNewData()) continue;
			$path = $f[1];
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "data:".$data->javascriptDataDisplay($path->sub_model);
			echo ",path:new DataPath(".json_encode($path->getString()).")";
			echo "}";
		}
		echo "]";
	}
}
?>