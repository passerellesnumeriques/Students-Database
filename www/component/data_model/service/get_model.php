<?php 
class service_get_model extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		echo "{tables:[";
		$first_table = true;
		foreach (DataModel::get()->getTables() as $table) {
			if ($first_table) $first_table = false; else echo ",";
			echo "{";
			echo "name:".json_encode($table->getName());
			echo ",columns:[";
			$first_col = true;
			foreach ($table->getColumns() as $col) {
				if ($first_col) $first_col = false; else echo ",";
				echo "{";
				echo "name:".json_encode($col->name);
				$tf = PNApplication::$instance->widgets->get_typed_field($col);
				echo ",typed_field_classname:".json_encode($tf[0]);
				echo ",typed_field_args:".$tf[1];
				echo "}";
			}
			echo "]";
			echo "}";
		}
		echo "]}";
	}
	
}
?>