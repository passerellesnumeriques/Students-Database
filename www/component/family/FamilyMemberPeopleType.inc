<?php
require_once("component/people/PeopleTypePlugin.inc");
/**
 * Plug-in to add the 'family_member' type of people
 */
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
		$entries = SQLQuery::create()->bypassSecurity()
			->select("FamilyMember")
			->whereValue("FamilyMember", "people", $people["id"])
			->execute();
		$result = "";
		// search if we have someone else, from the same family, who is known
		foreach ($entries as $e) {
			$others = SQLQuery::create()->bypassSecurity()
				->select("FamilyMember")
				->whereValue("FamilyMember", "family", $e["family"])
				->whereNotValue("FamilyMember", "people", $people["id"])
				->whereNotNull("FamilyMember", "people")
				->execute();
			if (count($others) == 0)
				$descr = "Family member of an unknown family";
			else {
				$peoples_ids = array();
				foreach ($others as $o) array_push($peoples_ids, $o["people"]);
				$others_peoples = PNApplication::$instance->people->getPeoples($peoples_ids, false, false, true, false);
				$descr = "";
				foreach ($others_peoples as $p) {
					$types = PNApplication::$instance->people->parseTypes($p["types"]);
					$is_fm = in_array("family_member", $types);
					if (!$is_fm || count($types) > 1) { // not only a family member
						$other = null;
						foreach ($others as $o) if ($o["people"] == $p["id"]) { $other = $o; break; }
						if ($other["member_type"] == "Child") {
							if ($e["member_type"] == "Child")
								$s = $people["sex"] == "F" ? "Sister of " : "Brother of ";
							else if ($e["member_type"] == "Father")
								$s = "Father of ";
							else if ($e["member_type"] == "Mother")
								$s = "Mother of ";
							else
								$s = "Related of ";
						} else if ($other["member_type"] == "Father") {
							if ($e["member_type"] == "Child")
								$s = "Child of ";
							else if ($e["member_type"] == "Mother")
								$s = "Wife of ";
							else
								$s = "Related of ";
						} else if ($other["member_type"] == "Mother") {
							if ($e["member_type"] == "Child")
								$s = "Child of ";
							else if ($e["member_type"] == "Father")
								$s = "Housband of ";
							else
								$s = "Related of ";
						}  else
							$s = "Related of ";
						$s .= DataModel::get()->getTable("People")->getRowDescription($p);
						if ($descr <> "") $descr .= ", ";
						$descr .= $s;
					}
				}
			}
			if ($descr <> "") {
				if ($result <> "") $result .= ", ";
				$result .= $descr;
			}
		}
		return $result;
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