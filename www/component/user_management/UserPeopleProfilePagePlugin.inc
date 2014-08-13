<?php 
require_once("component/people/PeopleProfilePagePlugin.inc");
class UserPeopleProfilePagePlugin implements PeopleProfilePagePlugin {
	
	public function isValidFor($people_id, $types) {
		return 
			PNApplication::$instance->user_management->has_right("consult_user_rights") &&
			PNApplication::$instance->user_management->isPeopleUser($people_id);
	}
	public function getPriority() { return 1000; }
	public function getIcon() { return "/static/user_management/access_list.png"; }
	public function getName() { return "User Rights"; }
	public function getTooltip() { return "What this person can access on the application"; }
	public function getURL($people_id) {
		$user = PNApplication::$instance->user_management->get_user_from_people($people_id); 
		return "/dynamic/user_management/page/user_rights?user=".$user; 
	}
	
}
?>