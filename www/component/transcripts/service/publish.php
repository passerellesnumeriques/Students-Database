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
		
		if (@$config["grades_details"] == 1 && count($subjects_detailed) > 0) {
			$types = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->whereIn("CurriculumSubjectEvaluationType","subject",$subjects_detailed)->execute();
			$types_ids = array();
			foreach ($types as $t) array_push($types_ids, $t["id"]);
			$evaluations = SQLQuery::create()->select("CurriculumSubjectEvaluation")->whereIn("CurriculumSubjectEvaluation","type",$types_ids)->execute();
			$eval_ids = array();
			foreach ($evaluations as $e) array_push($eval_ids, $e["id"]);
			$students_types_grades = SQLQuery::create()->select("StudentSubjectEvaluationTypeGrade")->whereIn("StudentSubjectEvaluationTypeGrade","type",$types_ids)->whereIn("StudentSubjectEvaluationTypeGrade","people",$students_ids)->execute();
			$students_eval_grades = SQLQuery::create()->select("StudentSubjectEvaluationGrade")->whereIn("StudentSubjectEvaluationGrade","evaluation",$eval_ids)->whereIn("StudentSubjectEvaluationGrade","people",$students_ids)->execute();
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
			$rows = SQLQuery::create()->select("PublishedTranscriptEvaluationType")->whereValue("PublishedTranscriptEvaluationType","transcript",$id)->execute();
			SQLQuery::create()->removeRows("PublishedTranscriptEvaluationType", $rows);
			// other detailed grades data are automatically removed when removing the data from PublishedTranscriptEvaluationType
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
		
		if (@$config["grades_details"] == 1 && count($subjects_detailed) > 0) {
			// PublishedTranscriptEvaluationType
			foreach ($types as $eval_type) {
				$type_id = SQLQuery::create()->insert("PublishedTranscriptEvaluationType", array(
					"transcript"=>$id,
					"subject"=>$eval_type["subject"],
					"name"=>$eval_type["name"],
					"weight"=>$eval_type["weight"],
				));
				// students' grade by type
				$to_insert = array();
				foreach ($students_types_grades as $sg) {
					if ($sg["type"] <> $eval_type["id"]) continue;
					array_push($to_insert, array(
						"people"=>$sg["people"],
						"type"=>$type_id,
						"grade"=>$sg["grade"]
					));
				}
				if (count($to_insert) > 0)
					SQLQuery::create()->insertMultiple("PublishedTranscriptStudentEvaluationTypeGrade", $to_insert);
				// evaluations
				foreach ($evaluations as $e) {
					if ($e["type"] <> $eval_type["id"]) continue;
					$eval_id = SQLQuery::create()->insert("PublishedTranscriptEvaluation", array(
						"type"=>$type_id,
						"name"=>$e["name"],
						"weight"=>$e["weight"],
						"max_grade"=>$e["max_grade"],
					));
					$to_insert = array();
					foreach ($students_eval_grades as $sg) {
						if ($sg["evaluation"] <> $e["id"]) continue;
						array_push($to_insert, array(
							"people"=>$sg["people"],
							"evaluation"=>$eval_id,
							"grade"=>$sg["grade"]
						));
					}
					if (count($to_insert) > 0)
						SQLQuery::create()->insertMultiple("PublishedTranscriptStudentEvaluationGrade", $to_insert);
				}
			}
		}
		
		if (PNApplication::hasErrors())
			return;
		
		SQLQuery::commitTransaction();
		echo "true";
	}
	
	
}
?>