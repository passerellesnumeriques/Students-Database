<?php 
require_once("component/people/PeopleProfilePagePlugin.inc");
class SocialInvestigationProfilePage implements PeopleProfilePagePlugin {
	
	public function isValidFor($people_id, $types) {
		return in_array("applicant", $types);
	}
	public function getPriority() { return 6; }
	public function getIcon() { return "/static/selection/si/si_16.png"; }
	public function getName() { return "Social Investigation"; }
	public function getTooltip() { return "Information gethered during social investigation"; }
	public function getURL($people_id) { return "/dynamic/selection/page/si/applicant?people=".$people_id; }
}
?>