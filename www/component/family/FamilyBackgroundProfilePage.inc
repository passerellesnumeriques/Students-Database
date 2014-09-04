<?php 
require_once("component/people/PeopleProfilePagePlugin.inc");
class FamilyBackgroundProfilePage implements PeopleProfilePagePlugin {
	
	public function isValidFor($people_id, $types) {
		return in_array("student", $types) || in_array("applicant", $types) || in_array("family_member", $types);
	}
	public function getPriority() { return 5; }
	public function getIcon() { return "/static/family/family_16.png"; }
	public function getName() { return "Family Background"; }
	public function getTooltip() { return "Information about parents, siblings..."; }
	public function getURL($people_id) { return "/dynamic/family/page/family_background?people=".$people_id; }
}
?>