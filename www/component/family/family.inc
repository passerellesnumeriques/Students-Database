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
	
	// TODO when adding a member who is already in a family: merge 2 families!
	
}
?>