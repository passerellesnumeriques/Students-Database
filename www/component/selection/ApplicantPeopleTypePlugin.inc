<?php 
require_once("component/people/PeopleTypePlugin.inc");
class ApplicantPeopleTypePlugin implements PeopleTypePlugin {
	
	public function getSecurityPriority() { return 100; }
	
	public function getId() { return "applicant"; }
	public function getName() { return "Applicant"; }
	public function isStandalone() { return true; }
	
	public function canRead() {
		return PNApplication::$instance->user_management->has_right("see_applicant_info");
	}
	public function canWrite() {
		return PNApplication::$instance->user_management->has_right("edit_applicants");
	}
	public function canRemove() {
		return PNApplication::$instance->user_management->has_right("edit_applicants");
	}
	
	public function getPeopleRowDescription($people) {
		return "Applicant"; // TODO
	}
	
}
?>