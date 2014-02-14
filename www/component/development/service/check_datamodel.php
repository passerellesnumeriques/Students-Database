<?php 
class service_check_datamodel extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		$problems = array();

		require_once("component/data_model/Model.inc");
		$model = DataModel::get();
		
		$ref = new ReflectionClass("DataModel");
		$p = $ref->getProperty("tables");
		$p->setAccessible(true);
		$tables = $p->getValue($model);
		$p2 = $ref->getProperty("sub_models");
		$p2->setAccessible(true);
		foreach ($p2->getValue($model) as $sm) {
			$sub_tables = $p->getValue($sm);
			$tables = array_merge($tables, $sub_tables); 
		}
		
		$ref = new ReflectionClass("\datamodel\Table");
		$p = $ref->getProperty("columns");
		$p->setAccessible(true);
		
		foreach ($tables as $table) {
			// check table name
			$name = $table->getName();
			if (!ctype_alpha(substr($name,0,1)) || strtoupper(substr($name,0,1)) <> substr($name,0,1)) {
				array_push($problems, "Invalid table name '".$name."': must start with a capital letter");
			} else {
				if (!ctype_alpha($name)) {
					array_push($problems, "Invalid table name '".$name."': must contain only letters");
				}
			}
			
			$columns = $p->getValue($table);
			foreach ($columns as $col) {
				// check column name
				$name = $col->name;
				if (!ctype_alpha(substr($name,0,1)) || strtolower(substr($name,0,1)) <> substr($name,0,1)) {
					array_push($problems, "Invalid column name '".$name."' in table '".$table->getName()."': must start with a small letter");
				} else {
					for ($i = 1; $i < strlen($name); $i++) {
						$c = substr($name, $i, 1);
						if ($c == "_") continue;
						if (!ctype_alpha($c)) {
							array_push($problems, "Invalid column name '".$name."' in table '".$table->getName()."': must contain only small letters and underscore");
							break;
						}
						if (strtolower($c) <> $c) {
							array_push($problems, "Invalid column name '".$name."' in table '".$table->getName()."': must contain only small letters and underscore");
							break;
						}
					}
				}
			}
		}
		
		echo json_encode($problems);
	}
	
}
?>