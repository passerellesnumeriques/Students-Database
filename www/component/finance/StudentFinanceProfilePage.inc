<?php 
require_once("component/people/PeopleProfilePagePlugin.inc");
class StudentFinanceProfilePage implements PeopleProfilePagePlugin {
	
	public function isValidFor($people_id, $types) {
		if (!PNApplication::$instance->user_management->hasRight("consult_student_finance")) return false;
		return in_array("student", $types);
	}
	public function getPriority() { return 30; }
	public function getIcon() { return "/static/finance/finance_16.png"; }
	public function getName() { return "Finance"; }
	public function getTooltip() { return "Financial follow-up of the student"; }
	public function getURL($people_id) { return "/dynamic/finance/page/student?people=".$people_id; }
}
?>