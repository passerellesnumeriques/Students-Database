<?php
require_once("component/people/PeopleTypePlugin.inc"); 
class FamilyMemberPeopleType implements PeopleTypePlugin {
	
	public function getSecurityPriority() { return 1000; }
	
	public function getId() { return "family_member"; }
	public function getName() { return "Family Member"; }
	public function getIcon16() { return "/static/family/family_16.png"; }
	public function getIcon32() { return "/static/family/family_32.png"; }
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
	
	public function remove($people_id) {
		$rows = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereValue("FamilyMember","people",$people_id)->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("FamilyMember", $rows);
		$families = array();
		foreach ($rows as $row) if (!in_array($row["family"], $families)) array_push($families, $row["family"]);
		if (count($families) == 0) return;
		$members = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereIn("FamilyMember","family",$families)->execute();
		for ($i = 0; $i < count($families); $i++) {
			$found = false;
			foreach ($members as $m) if ($m["family"] == $families[$i]) { $found = true; break; }
			if ($found) { array_splice($families, $i, 1); $i--; }
		}
		if (count($families) == 0) return;
		SQLQuery::create()->bypassSecurity()->removeKeys("Family", $families);
	}
	
}
?>