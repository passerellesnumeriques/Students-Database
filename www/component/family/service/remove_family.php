<?php 
class service_remove_family extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Remove all information about a family"; }
	public function inputDocumentation() { echo "id: the family id"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$family_id = $input["id"];
		// TODO check security
		SQLQuery::startTransaction();
		$q = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereValue("FamilyMember","family",$family_id);
		PNApplication::$instance->people->joinPeople($q, "FamilyMember", "people", false);
		$q->field("FamilyMember","id","member_id");
		$members = $q->execute();
		// remove people not related anymore to anything
		foreach ($members as $m) {
			if ($m["people_id"] == null) continue; // member without people
			$types = PNApplication::$instance->people->parseTypes($m["people_types"]);
			if (count($types) > 1 || $types[0] <> "family_member") continue;
			$other_families = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereValue("FamilyMember","people",$m["people_id"])->whereNotValue("FamilyMember", "family", $family_id)->execute();
			if (count($other_families) > 0) continue;
			// no more link => remove people
			PNApplication::$instance->people->removePeople($m["people_id"], true);
		}
		// remove family
		SQLQuery::create()->bypassSecurity()->removeKey("Family",$family_id);
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		} else
			SQLQuery::rollbackTransaction();
	}
	
}
?>