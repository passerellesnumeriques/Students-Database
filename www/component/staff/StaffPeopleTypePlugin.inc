<?php 
require_once("component/people/PeopleTypePlugin.inc");
class StaffPeopleTypePlugin implements PeopleTypePlugin {
	
	public function getSecurityPriority() { return 5; }
	
	public function getId() { return "staff"; }
	public function getName() { return "Staff"; }
	public function isStandalone() { return true; }
	
	public function canRead() { return PNApplication::$instance->user_management->has_right("consult_staff_list"); }
	public function canWrite() { return PNApplication::$instance->user_management->has_right("manage_staff"); }
	public function canRemove() { return PNApplication::$instance->user_management->has_right("manage_staff"); }
	
	public function getPeopleRowDescription($people) {
		return "PN Staff";
	}
	
	public function getBuiltinDefaultUserRoles() { return array("Staff"); }
}
?>