<?php
require_once("component/people/PeopleTypePlugin.inc"); 
class FamilyMemberPeopleType implements PeopleTypePlugin {
	
	public function getSecurityPriority() { return 1000; }
	
	public function getId() { return "family_member"; }
	public function getName() { return "Family Member"; }
	public function isStandalone() { return false; }
	
	public function canRead() {
		return true; // TODO
	}
	public function canWrite() {
		return true; // TODO
	}
	public function canRemove() {
		return true; // TODO
	}
	
	public function getTables() { return array("Family","FamilyMember"); }
	
	public function getPeopleRowDescription($people) {
		return "Family Member"; // TODO
	}
	
	public function getBuiltinDefaultUserRoles($people) {
		return array();
	}
	
}
?>