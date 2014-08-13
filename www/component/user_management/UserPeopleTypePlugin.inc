<?php 
require_once("component/people/PeopleTypePlugin.inc");
class UserPeopleTypePlugin implements PeopleTypePlugin {
	
	public function getSecurityPriority() { return 10000; }
	
	public function getId() { return "user"; }
	public function getName() { return "User"; }
	public function isStandalone() { return true; }
	
	public function getTables() { return array("Users"); }
	
	public function canRead() { return PNApplication::$instance->user_management->has_right("consult_user_list"); }
	public function canWrite() { return PNApplication::$instance->user_management->has_right("manage_users"); }
	public function canRemove() { return PNApplication::$instance->user_management->has_right("manage_users"); }
	
	public function getPeopleRowDescription($people) {
		return null; // TODO
	}
	
	public function getBuiltinDefaultUserRoles($people) { return array(); }
}
?>