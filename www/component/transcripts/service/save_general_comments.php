<?php 
class service_save_general_comments extends Service {
	
	public function getRequiredRights() { return array("consult_students_grades"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$period_id = $input["period"];
		$students = $input["students"];
		if (count($students) == 0) {
			echo "true";
			return;
		}
		$students_ids = array();
		foreach ($students as $s) array_push($students_ids, $s["people"]);
		SQLQuery::startTransaction();
		$rows = SQLQuery::create()
			->select("StudentTranscriptGeneralComment")
			->whereValue("StudentTranscriptGeneralComment", "period", $period_id)
			->whereIn("StudentTranscriptGeneralComment", "people", $students_ids)
			->execute();
		SQLQuery::create()->removeRows("StudentTranscriptGeneralComment", $rows);
		$to_insert = array();
		foreach ($students as $s)
			if (trim($s["comment"]) <> "")
				array_push($to_insert, array("period"=>$period_id,"people"=>$s["people"],"comment"=>trim($s["comment"])));
		if (count($to_insert) > 0)
			SQLQuery::create()->insertMultiple("StudentTranscriptGeneralComment", $to_insert);
		SQLQuery::commitTransaction();
		echo "true";
	}
	
}
?>