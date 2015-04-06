<?php 
class service_get_contact_point extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Returns details about a contact point, used to refresh information on organization profile"; }
	public function inputDocumentation() { echo "people: the people id"; }
	public function outputDocumentation() { echo "ContactPoint JSON structure"; }
	
	public function execute(&$component, $input) {
		require_once 'component/contact/ContactJSON.inc';
		$q = SQLQuery::create()
			->select("ContactPoint")
			->whereValue("ContactPoint", "people", $input["people"])
			;
		ContactJSON::OrganizationContactPointSQL($q);
		$contact_point = $q->executeSingleRow();
		$q = SQLQuery::create()
			->select("PeopleContact")
			->whereValue("PeopleContact","people",$input["people"])
			->join("PeopleContact","Contact",array("contact"=>"id"));
		ContactJSON::ContactSQL($q);
		$contact_point["contacts"] = $q->execute();
		echo ContactJSON::OrganizationContactPoint($contact_point);
	}
	
}
?>