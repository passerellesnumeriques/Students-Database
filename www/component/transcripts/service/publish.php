<?php 
class service_publish extends Service {
	
	public function getRequiredRights() { return array("edit_transcripts_design"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$period_id = $input["period"];
		$spe_id = $input["specialization"];

		SQLQuery::startTransaction();
		
		$config = SQLQuery::create()->select("TranscriptConfig")->whereValue("TranscriptConfig","period",$period_id)->whereValue("TranscriptConfig","specialization",$spe_id)->executeSingleRow();
		require_once("component/transcripts/page/design.inc");
		defaultTranscriptConfig($config);
		
		$subjects_ids = SQLQuery::create()->select("TranscriptSubjects")->whereValue("TranscriptSubjects","period",$period_id)->whereValue("TranscriptSubjects","specialization",$spe_id)->field("subject")->executeSingleField();
		
		$students_ids = PNApplication::$instance->students->getStudentsQueryForBatchPeriod($period_id, false, false, $spe_id)->field("Student","people")->executeSingleField();
		if (count($students_ids) == 0) {
			PNApplication::error("There is no student, you cannot publish transcripts without student!");
			return;
		}
		
		$students_grades = SQLQuery::create()->select("StudentSubjectGrade")->whereIn("StudentSubjectGrade","people",$students_ids)->whereIn("StudentSubjectGrade","subject",$subjects_ids)->execute();
		
		$students_comments = SQLQuery::create()->select("StudentTranscriptGeneralComment")->whereValue("StudentTranscriptGeneralComment","period",$period_id)->whereIn("StudentTranscriptGeneralComment","people",$students_ids)->execute();
		
		if (isset($input["id"])) {
			// update
			$id = $input["id"];
			$rows = SQLQuery::create()->select("PublishedTranscriptStudentSubjectGrade")->whereValue("PublishedTranscriptStudentSubjectGrade","id",$id)->execute();
			SQLQuery::create()->removeRows("PublishedTranscriptStudentSubjectGrade", $rows);
			$rows = SQLQuery::create()->select("PublishedTranscriptStudentGeneralComment")->whereValue("PublishedTranscriptStudentGeneralComment","id",$id)->execute();
			SQLQuery::create()->removeRows("PublishedTranscriptStudentGeneralComment", $rows);
		} else {
			// new one
			$config["name"] = $input["name"];
			$id = SQLQuery::create()->insert("PublishedTranscript", $config);
		}
		
		$to_insert = array();
		foreach ($students_grades as $sg)
			array_push($to_insert, array(
				"id"=>$id,
				"subject"=>$sg["subject"],
				"people"=>$sg["people"],
				"grade"=>$sg["grade"]
			));
		if (count($to_insert) > 0)
			SQLQuery::create()->insertMultiple("PublishedTranscriptStudentSubjectGrade", $to_insert);
		
		$to_insert = array();
		foreach ($students_comments as $c)
			array_push($to_insert, array(
				"id"=>$id,
				"people"=>$c["people"],
				"comment"=>$c["comment"]
			));
		if (count($to_insert) > 0)
			SQLQuery::create()->insertMultiple("PublishedTranscriptStudentGeneralComment", $to_insert);
		
		if (PNApplication::hasErrors())
			return;
		
		SQLQuery::commitTransaction();
		echo "true";
	}
	
	
}
?>