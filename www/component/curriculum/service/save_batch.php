<?php 
class service_save_batch extends Service {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Save or create a batch, with periods and specializations"; }
	public function inputDocumentation() {
?>
<ul>
	<li><code>id</code>: optional, if not specified this is a new batch, else it must be the batch id</li>
	<li><code>name</code>: name of the batch</li>
	<li><code>start_date</code>: integration date, in SQL format</li>
	<li><code>end_date</code>: graduation date, in SQL format</li>
	<li><code>lock</code>: Database lock ID, locking the StudentBatch</li>
	<li><code>periods</code>: array of periods:<ul>
		<li><code>id</code>: id of the period (if this is a new period, it must be a negative value, unique)</li>
		<li><code>name</code>: period name</li>
		<li><code>academic_period</code>: id of the AcademicPeriod corresponding to the batch period</li>
	</ul></li>
	<li><code>periods_specializations</code>: array of associations between a period and a specialization:<ul>
		<li><code>period_id</code>: id of the period (it can be a negative value in case the period is new, matching the id in the list of periods)</li>
		<li><code>specialization_id</code>: id of the specialization to associate with the period</li>
	</ul></li>
</ul>
<?php 
	}
	public function outputDocumentation() { 
		echo "In case of success, <code>id</code>: the batch id, <code>periods_ids</code>: for created periods contains the mapping of ids : [{given_id,new_id}]"; 
	}
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		// StudentBatch
		$batch_id = @$input["id"];
		if ($batch_id <> null && $batch_id <= 0) $batch_id = null;
		$new_batch = $batch_id == null;
		$fields = array(
			"name"=>$input["name"],
			"start_date"=>$input["start_date"],
			"end_date"=>$input["end_date"]
		);
		if ($batch_id <> null)
			SQLQuery::create()->bypassSecurity()->updateByKey("StudentBatch", $batch_id, $fields, $input["lock"]);
		else
			$batch_id = SQLQuery::create()->bypassSecurity()->insert("StudentBatch", $fields, $input["lock"]);
		// periods
		if (!$new_batch)
			$previous_periods = SQLQuery::create()->bypassSecurity()->select("BatchPeriod")->whereValue("BatchPeriod","batch", $batch_id)->execute();
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
				"academic_period"=>$period["academic_period"],
			);
			if ($period_id > 0) {
				SQLQuery::create()->bypassSecurity()->updateByKey("BatchPeriod", $period_id, $fields);
				array_push($periods_ids, $period_id);
			} else {
				$id = SQLQuery::create()->bypassSecurity()->insert("BatchPeriod", $fields);
				$new_periods_mapping[$period_id] = $id;
			}
		}
		if (!$new_batch) {
			$ids = array();
			foreach ($previous_periods as $p) array_push($ids, $p["id"]);
			if (count($ids) > 0)
				SQLQuery::create()->bypassSecurity()->removeKeys("BatchPeriod",$ids);
		}
		// periods' specializations
		$list = array();
		foreach ($input["periods_specializations"] as $ps) {
			if ($ps["period_id"] <= 0) $ps["period_id"] = $new_periods_mapping[$ps["period_id"]];
			array_push($list, array(
				"period"=>$ps["period_id"],
				"specialization"=>$ps["specialization_id"]
			));
		}
		if (!$new_batch && count($periods_ids) > 0) {
			$rows = SQLQuery::create()->bypassSecurity()->select("BatchPeriodSpecialization")->whereIn("BatchPeriodSpecialization","period",$periods_ids)->execute();
			// remove what is already there
			for ($i = 0; $i < count($rows); $i++) {
				$found = false;
				for ($j = 0; $j < count($list); $j++) {
					if ($list[$j]["period"] == $rows[$i]["period"] && $list[$j]["specialization"] == $rows[$i]["specialization"]) {
						$found = true;
						array_splice($list, $j, 1);
						break;
					}
				}
				if ($found) {
					array_splice($rows, $i, 1);
					$i--;
				}
			}
			if (count($rows) > 0) {
				// some need to be removed
				SQLQuery::create()->bypassSecurity()->removeRows("BatchPeriodSpecialization", $rows);
			}
		}
		if (count($list) > 0)
			SQLQuery::create()->bypassSecurity()->insertMultiple("BatchPeriodSpecialization", $list);
		
		if (PNApplication::hasErrors())
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