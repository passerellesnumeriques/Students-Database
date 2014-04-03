<?php 
class service_save_academic_year extends Service {
	
	public function get_required_rights() { return array("edit_curriculum"); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		$id = @$input["id"];
		$fields = array(
			"year"=>$input["year"],
			"name"=>$input["name"]
		);
		if ($id <> null && $id > 0)
			SQLQuery::create()->updateByKey("AcademicYear", $id, $fields);
		else
			$id = SQLQuery::create()->insert("AcademicYear", $fields);
		
		foreach ($input["periods"] as $period) {
			$fields = array(
				"year"=>$id,
				"name"=>$period["name"],
				"start"=>$period["start"],
				"end"=>$period["end"],
				"weeks"=>$period["weeks"],
				"weeks_break"=>$period["weeks_break"]
			);
			$pid = @$period["id"];
			if ($pid <> null && $pid > 0)
				SQLQuery::create()->updateByKey("AcademicPeriod", $pid, $fields);
			else
				SQLQuery::create()->insert("AcademicPeriod", $fields);
		}
		echo "true";
	}
	
}
?>