<?php
require_once("component/people/PeopleProfileGeneralInfoPlugin.inc"); 
class ContactPeopleProfileGeneralInfoPlugin implements PeopleProfileGeneralInfoPlugin {
	
	public function getPriority() { return 5; }
	public function getIcon() { return "/static/contact/contact_16.png"; }
	public function getName() { return "Contacts"; }
	
	public function isValidForTypes($types) { return true; }
	
	public function prepareRequestForSection(&$q, $people_id) {}
	public function generateSection(&$page, $people_id, $row, $prepareRequest, $q) {
		$contact_div_id = $page->generateID();
		$address_div_id = $page->generateID();
		echo "<table><tr><td valign=top id='$contact_div_id'></td><td valign=top id='$address_div_id'></td></tr></table>";
		require_once("page/contact.inc");
		contact($page, "people", $contact_div_id, $people_id);
		require_once("page/address.inc");
		address($page, "people", $address_div_id, $people_id);
	}
	
	
}
?>