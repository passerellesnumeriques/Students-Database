<?php 
require_once("component/people/PeopleProfilePagePlugin.inc");
class HealthPeopleProfilePagePlugin implements PeopleProfilePagePlugin {
	
	public function isValidFor($people_id, $types) { return in_array("student", $types); }
	public function getPriority() { return 25; }
	public function getIcon() { return "/static/health/health.png"; }
	public function getName() { return "Health"; }
	public function getTooltip() { return "Medical information and health history of the student"; }
	public function getURL($people_id) { return "/dynamic/health/page/home?people=".$people_id; }
	
}
?>