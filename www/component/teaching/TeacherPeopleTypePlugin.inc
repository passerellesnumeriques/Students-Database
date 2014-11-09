<?php 
require_once("component/people/PeopleTypePlugin.inc");
/** Implementation of PeopleTypePlugin for a teacher */
class TeacherPeopleTypePlugin implements PeopleTypePlugin {
	
	public function getSecurityPriority() { return 20; }
	
	public function getId() { return "teacher"; }
	public function getName() { return "Teacher"; }
	public function getIcon16() { return "/static/teaching/teacher_16.png"; }
	public function getIcon32() { return "/static/teaching/teacher_32.png"; }
	public function isStandalone() { return true; }
	
	public function getTables() { return array("TeacherDates"); }
	
	public function canRead() {
		return PNApplication::$instance->user_management->has_right("consult_curriculum");
	}
	public function canWrite() {
		return PNApplication::$instance->user_management->has_right("edit_curriculum");
	}
	public function canRemove() {
		return PNApplication::$instance->user_management->has_right("edit_curriculum");
	}
	
	public function getPeopleRowDescription($people) {
		return "Teacher";
	}
	
	public function getBuiltinDefaultUserRoles($people) { return array("Teacher"); }
	
	public function remove($people_id) {
		$rows = SQLQuery::create()->bypassSecurity()->select("TeacherDates")->whereValue("TeacherDates","people",$people_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("TeacherDates", $rows);
		$rows = SQLQuery::create()->bypassSecurity()->select("TeacherAssignment")->whereValue("TeacherAssignment","people",$people_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("TeacherAssignment", $rows);
	}
}
?>