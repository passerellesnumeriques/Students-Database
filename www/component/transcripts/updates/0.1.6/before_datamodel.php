<?php 
// re-compute grades for every subjects, having grades in evaluations
$subjects_ids = SQLQuery::create()->bypassSecurity()
	->select("StudentSubjectEvaluationGrade")
	->join("StudentSubjectEvaluationGrade", "CurriculumSubjectEvaluation", array("evaluation"=>"id"))
	->join("CurriculumSubjectEvaluation", "CurriculumSubjectEvaluationType", array("type"=>"id"))
	->groupBy("CurriculumSubjectEvaluationType", "subject")
	->field("CurriculumSubjectEvaluationType", "subject")
	->executeSingleField();
foreach ($subjects_ids as $subject_id) {
	PNApplication::$instance->transcripts->compute_subject_grades($subject_id, true);
}
?>