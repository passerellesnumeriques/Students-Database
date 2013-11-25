<?php 
class service_get_available_fields extends Service {
	
	public function get_required_rights() {
		return array();
	}
	
	public function documentation() { echo "Start from the given table, and search for all reachable fields, and return the list of displayable fields"; }
	public function input_documentation() { echo "<code>table</code>: name of starting table"; }
	public function output_documentation() {
		echo "List of {data:the JavaScript DataDisplay, path: the data path}"; 
	}
	
	public function execute(&$component, $input) {
		$table = $input["table"];
		$list = $component->get_available_fields($table);
		echo "[";
		$first = true;
		foreach ($list as $d) {
			if ($first) $first = false; else echo ",";
			$data = $d[0];
			$path = $d[1];
				echo "{data:";
				echo $data->javascriptDataDisplay($path->sub_model);
				echo ",path:".json_encode($path->get_string());
				echo "}";
		}
		echo "]";
	}
}
?>