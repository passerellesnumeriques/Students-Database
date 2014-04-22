<?php 
class service_save_academic_year extends Service {
	
	public function get_required_rights() { return array("edit_curriculum"); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		$id = @$input["id"];
		$fields = array(
			"year"=>$input["year"],
			"name"=>$input["name"]
		);
		if ($id <> null && $id > 0) {
			SQLQuery::create()->updateByKey("AcademicYear", $id, $fields);
			$existing_periods_ids = SQLQuery::create()->select("AcademicPeriod")->whereValue("AcademicPeriod","year",$id)->field("AcademicPeriod","id")->executeSingleField();
		} else {
			$id = SQLQuery::create()->insert("AcademicYear", $fields);
			$existing_periods_ids = array();
		}
		
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
		// remove periods not there anymore
		if (count($existing_periods_ids) > 0) {
			foreach ($input["periods"] as $period) {
				$pid = @$period["id"];
				if ($pid <> null && $pid > 0)
					for ($i = 0; $i < count($existing_periods_ids); $i++)
						if ($existing_periods_ids[$i] == $pid) {
							array_splice($existing_periods_ids, $i, 1);
							break;
						}
			}
			if (count($existing_periods_ids) > 0)
				SQLQuery::create()->removeKeys("AcademicPeriod", $existing_periods_ids);
		}
		
		if (PNApplication::has_errors()) {
			SQLQuery::rollbackTransaction();
			echo "false";
		} else {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>