<?php 
class family extends Component {
	
	public function getPluginImplementations() {
		require_once("FamilyBackgroundProfilePage.inc");
		require_once("FamilyMemberPeopleType.inc");
		return array(
			new FamilyBackgroundProfilePage(),
			new FamilyMemberPeopleType()
		);
	}
	
	public function getFamily($people_id, $member_type) {
		$family_id = SQLQuery::create()->bypassSecurity()
			->select("FamilyMember")
			->whereValue("FamilyMember", "people", $people_id)
			->whereValue("FamilyMember", "member_type", $member_type)
			->field("family")
			->executeSingleValue();
		
		if ($family_id <> null) {
			$family = SQLQuery::create()->bypassSecurity()->select("Family")->whereValue("Family","id",$family_id)->executeSingleRow();
			$members = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereValue("FamilyMember","family",$family_id)->execute();
		} else {
			$family = array("id"=>-1);
			$members = array(array("family"=>-1,"id"=>-1,"people"=>$people_id,"member_type"=>$member_type));
		}
		
		$peoples_ids = array();
		foreach ($members as $m) array_push($peoples_ids, $m["people"]);
		if (count($peoples_ids) > 0) {
			$q = PNApplication::$instance->people->getPeoplesSQLQuery($peoples_ids);
			require_once("component/people/PeopleJSON.inc");
			PeopleJSON::PeopleSQL($q, false);
			$peoples = $q->execute();
		} else
			$peoples = array();
		
		for ($i = 0; $i < count($members); $i++)
			foreach ($peoples as $p) if ($p["people_id"] == $members[$i]["people"]) { $members[$i]["people"] = $p; break; }
		
		return array($family,$members);
	}
		
	// TODO when adding a member who is already in a family: merge 2 families!
	
}
?>