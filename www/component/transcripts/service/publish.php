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
		
		$config = SQLQuery::create()->select("TranscriptConfig")->whereValue("TranscriptConfig","period",$period_id)->whereNull("TranscriptConfig","specialization")->executeSingleRow();
		$config_spe = SQLQuery::create()->select("TranscriptConfig")->whereValue("TranscriptConfig","period",$period_id)->whereValue("TranscriptConfig","specialization",$spe_id)->executeSingleRow();
		if ($config_spe <> null)
			foreach ($config_spe as $col=>$value) if ($value !== null) $config[$col] = $value;
		$config["period"] = $period_id;
		$config["specialization"] = $spe_id;
		require_once("component/transcripts/page/design.inc");
		defaultTranscriptConfig($config);
		
		$sids = SQLQuery::create()->select("TranscriptSubjects")->whereValue("TranscriptSubjects","period",$period_id)->whereValue("TranscriptSubjects","specialization",$spe_id)->field("subject")->executeSingleField();
		$subjects_infos = SQLQuery::create()->select("CurriculumSubjectGrading")->whereIn("CurriculumSubjectGrading","subject",$sids)->execute();
		$subjects = array();
		$subjects_ids = array();
		if (@$config["grades_details"] == 1) $subjects_detailed = array();
		foreach ($sids as $sid) {
			$info = null;
			foreach ($subjects_infos as $si) if ($si["subject"] == $sid) { $info = $si; break; }
			if ($info == null || !isset($info["max_grade"]) || !isset($info["passing_grade"])) continue;
			array_push($subjects_ids, $sid);
			array_push($subjects, array(
				"transcript"=>@$input["id"],
				"subject"=>$sid,
				"max_grade"=>$info["max_grade"],
				"passing_grade"=>$info["passing_grade"]
			));
			if (@$config["grades_details"] == 1 && $info["only_final_grade"] <> 1)
				array_push($subjects_detailed, $sid);
		}
		
		if (count($subjects) == 0) {
			PNApplication::error("You cannot publish this transcript because none of the subjects are configured.");
			return;
		}
		
		$students_ids = PNApplication::$instance->students->getStudentsQueryForBatchPeriod($period_id, false, false, $spe_id <> null ? $spe_id : false)->field("Student","people")->executeSingleField();
		if (count($students_ids) == 0) {
			if ($spe_id == null) {
				$spes = PNApplication::$instance->curriculum->getBatchPeriodsSpecializationsWithName(array($period_id));
				if (count($spes) > 0) {
					PNApplication::error("You cannot publish a transcript at this level, you should first select a specialization");
					return;
				}
			}
			PNApplication::error("There is no student, you cannot publish a transcript without student!");
			return;
		}
		
		$students_grades = SQLQuery::create()->select("StudentSubjectGrade")->whereIn("StudentSubjectGrade","people",$students_ids)->whereIn("StudentSubjectGrade","subject",$subjects_ids)->execute();
		
		$students_comments = SQLQuery::create()->select("StudentTranscriptGeneralComment")->whereValue("StudentTranscriptGeneralComment","period",$period_id)->whereIn("StudentTranscriptGeneralComment","people",$students_ids)->execute();
		
		if (@$config["grades_details"] == 1) {
			//$types = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->whereIn("CurriculumSubjectEvaluationType","subject",$subjects_detailed)->execute();
			// TODO
		}
		
		if (isset($input["id"])) {
			// update
			$id = $input["id"];
			SQLQuery::create()->updateByKey("PublishedTranscript", $id, $config);
			$rows = SQLQuery::create()->select("PublishedTranscriptStudentSubjectGrade")->whereValue("PublishedTranscriptStudentSubjectGrade","id",$id)->execute();
			SQLQuery::create()->removeRows("PublishedTranscriptStudentSubjectGrade", $rows);
			$rows = SQLQuery::create()->select("PublishedTranscriptStudentGeneralComment")->whereValue("PublishedTranscriptStudentGeneralComment","id",$id)->execute();
			SQLQuery::create()->removeRows("PublishedTranscriptStudentGeneralComment", $rows);
			$rows = SQLQuery::create()->select("PublishedTranscriptSubject")->whereValue("PublishedTranscriptSubject","transcript",$id)->execute();
			SQLQuery::create()->removeRows("PublishedTranscriptSubject", $rows);
		} else {
			// new one
			$config["name"] = $input["name"];
			$id = SQLQuery::create()->insert("PublishedTranscript", $config);
			for ($i = 0; $i < count($subjects); $i++)
				$subjects[$i]["transcript"] = $id;
		}
		
		SQLQuery::create()->insertMultiple("PublishedTranscriptSubject", $subjects);
		
		$to_insert = array();
		foreach ($students_grades as $sg)
			array_push($to_insert, array(
				"id"=>$id,
				"subject"=>$sg["subject"],
				"people"=>$sg["people"],
				"grade"=>$sg["grade"],
				"comment"=>$sg["comment"]
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