<?php 
class service_save_batch extends Service {
	
	public function get_required_rights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Save or create a batch, with periods and specializations"; }
	public function input_documentation() {
		// TODO
	}
	public function output_documentation() { 
		echo "In case of success, <code>id</code>: the batch id, <code>periods_ids</code>: for created periods contains the mapping of ids : [{given_id,new_id}]"; 
	}
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		// StudentBatch
		$batch_id = @$input["id"];
		$new_batch = $batch_id == null;
		$fields = array(
			"name"=>$input["name"],
			"start_date"=>$input["start_date"],
			"end_date"=>$input["end_date"]
		);
		if ($batch_id <> null)
			SQLQuery::create()->updateByKey("StudentBatch", $batch_id, $fields, $input["lock"]);
		else
			$batch_id = SQLQuery::create()->insert("StudentBatch", $fields, $input["lock"]);
		// periods
		if (!$new_batch)
			$previous_periods = SQLQuery::create()->select("AcademicPeriod")->whereValue("AcademicPeriod","batch", $batch_id)->execute();
		$new_periods_mapping = array();
		$periods_ids = array();
		foreach ($input["periods"] as $period) {
			$period_id = $period["id"];
			if ($period_id > 0)
				for ($i = 0; $i < count($previous_periods); $i++)
					if ($previous_periods[$i]["id"] == $period_id) {
						array_splice($previous_periods, $i, 1);
						break;					
					}
			$fields = array(
				"batch"=>$batch_id,
				"name"=>$period["name"],
				"start_date"=>$period["start_date"],
				"end_date"=>$period["end_date"]
			);
			if ($period_id > 0) {
				SQLQuery::create()->updateByKey("AcademicPeriod", $period_id, $fields);
				array_push($periods_ids, $period_id);
			} else {
				$id = SQLQuery::create()->insert("AcademicPeriod", $fields);
				$new_periods_mapping[$period_id] = $id;
			}
		}
		if (!$new_batch) {
			$ids = array();
			foreach ($previous_periods as $p) array_push($ids, $p["id"]);
			if (count($ids) > 0)
				SQLQuery::create()->removeKeys("AcademicPeriod",$ids);
		}
		// periods' specializations
		if (!$new_batch) {
			$rows = SQLQuery::create()->select("AcademicPeriodSpecialization")->whereIn("AcademicPeriodSpecialization","period",$periods_ids)->execute();
			if (count($rows) > 0)
				SQLQuery::create()->removeRows("AcademicPeriodSpecialization", $rows);
		}
		$list = array();
		foreach ($input["periods_specializations"] as $ps) {
			array_push($list, array(
				"period"=>$ps["period_id"],
				"specialization"=>$ps["specialization_id"]
			));
		}
		if (count($list) > 0)
			SQLQuery::create()->insertMultiple("AcademicPeriodSpecialization", $list);
		
		if (PNApplication::has_errors())
			SQLQuery::rollbackTransaction();
		else {
			SQLQuery::commitTransaction();
			echo "{id:".$batch_id;
			echo ",periods_ids:[";
			$first = true;
			foreach ($new_periods_mapping as $given_id=>$new_id) {
				if ($first) $first = false; else echo ",";
				echo "{given_id:".$given_id.",new_id:".$new_id."}";
			}
			echo "]";
			echo "}";
		}
	}
	
}
?>