<?php 
require_once("component/people/PeopleTypePlugin.inc");
class StudentPeopleTypePlugin implements PeopleTypePlugin {
	
	public function getSecurityPriority() { return 50; }
	
	public function getId() { return "student"; }
	public function getName() { return "Student"; }
	public function getIcon16() { return "/static/students/student_16.png"; }
	public function getIcon32() { return "/static/students/student_32.png"; }
	public function isStandalone() { return true; }
	
	public function getTables() { return array("Student"); }
	
	public function canRead() { return PNApplication::$instance->user_management->has_right("consult_students_list"); }
	public function canWrite() { return PNApplication::$instance->user_management->has_right("manage_batches"); }
	public function canRemove() { return PNApplication::$instance->user_management->has_right("manage_batches"); }
	
	public function getPeopleRowDescription($people) {
		$q = SQLQuery::create()->bypassSecurity()
			->select("Student")
			->whereValue("Student", "people", $people["id"])
			;
		PNApplication::$instance->curriculum->joinBatch($q, "Student", "batch");
		$batch = $q->field("StudentBatch","name")->executeSingleRow();
		if ($batch == null) return "Student";
		return "Student of batch ".$batch["name"];
	}
	
	public function getBuiltinDefaultUserRoles($people) { return array("Student"); }
	
	public function remove($people_id) {
		$rows = SQLQuery::create()->bypassSecurity()->select("Student")->whereValue("Student","people",$people_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("Student", $rows);
		$rows = SQLQuery::create()->bypassSecurity()->select("StudentClass")->whereValue("StudentClass","people",$people_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("StudentClass", $rows);
		// TODO better: we should be able to know all tables having links to students...
		$rows = SQLQuery::create()->bypassSecurity()->select("StudentSubjectEvaluationTypeGrade")->whereValue("StudentSubjectEvaluationTypeGrade","people",$people_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("StudentSubjectEvaluationTypeGrade", $rows);
		$rows = SQLQuery::create()->bypassSecurity()->select("StudentSubjectEvaluationGrade")->whereValue("StudentSubjectEvaluationGrade","people",$people_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("StudentSubjectEvaluationGrade", $rows);
		$rows = SQLQuery::create()->bypassSecurity()->select("StudentSubjectGrade")->whereValue("StudentSubjectGrade","people",$people_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("StudentSubjectGrade", $rows);
		$rows = SQLQuery::create()->bypassSecurity()->select("StudentTranscriptGeneralComment")->whereValue("StudentTranscriptGeneralComment","people",$people_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("StudentTranscriptGeneralComment", $rows);
		$rows = SQLQuery::create()->bypassSecurity()->select("PublishedTranscriptStudentSubjectGrade")->whereValue("PublishedTranscriptStudentSubjectGrade","people",$people_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("PublishedTranscriptStudentSubjectGrade", $rows);
		$rows = SQLQuery::create()->bypassSecurity()->select("PublishedTranscriptStudentGeneralComment")->whereValue("PublishedTranscriptStudentGeneralComment","people",$people_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("PublishedTranscriptStudentGeneralComment", $rows);
	}
}
?>