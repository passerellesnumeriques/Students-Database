<?php 
require_once("component/people/PeopleTypePlugin.inc");
class ApplicantPeopleTypePlugin implements PeopleTypePlugin {
	
	public function getSecurityPriority() { return 100; }
	
	public function getId() { return "applicant"; }
	public function getName() { return "Applicant"; }
	public function getIcon16() { return "/static/selection/applicant/applicant_16.png"; }
	public function getIcon32() { return "/static/selection/applicant/applicant_32.png"; }
	public function isStandalone() { return true; }
	
	public function getTables() { return array("Applicant"); }
	
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
		$c = PNApplication::$instance->selection->getCampaignFromApplicant($people["id"], true);
		return "Applicant during campaign ".toHTML($c["name"]);
	}
	
	public function getBuiltinDefaultUserRoles($people) { return array(); }
	
	public function remove($people_id) {
		// TODO with sub model helper
	}
}
?>