<?php 
class service_get_available_fields extends Service {
	
	public function getRequiredRights() {
		return array();
	}
	
	public function documentation() { echo "Start from the given table, and search for all reachable fields, and return the list of displayable fields"; }
	public function inputDocumentation() { 
		echo "<code>table</code>: name of starting table<br/>";
		echo "<code>sub_model</code>: optional, sub model instance of the starting table<br/>";
		echo "<code>go_to_submodels</code>: optional, if the starting table is not in a sub model, it indicates if we can go or not to sub model instances. By default it is false."; 
	}
	public function outputDocumentation() {
		echo "List of {data:the JavaScript DataDisplay, path: the data path}"; 
	}
	
	public function execute(&$component, $input) {
		$table = $input["table"];
		$sub_model = @$input["sub_model"];
		$go_to_submodels = isset($input["go_to_submodels"]) ? $input["go_to_submodels"] : false;
		$list = $component->getAvailableFields($table, $sub_model, $go_to_submodels);
		echo "[";
		$first = true;
		foreach ($list as $d) {
			if ($first) $first = false; else echo ",";
			$data = $d[0];
			$path = $d[1];
				echo "{data:";
				echo $data->javascriptDataDisplay($path->sub_model);
				echo ",path:".json_encode($path->getString());
				echo "}";
		}
		echo "]";
	}
}
?>