<?php
require_once("PeopleProfilePagePlugin.inc");
class PeopleProfilePage implements PeopleProfilePagePlugin {
	
	public function isValidFor($people_id, $types) { return true; }
	public function getPriority() { return 0; }
	public function getIcon() { return "/static/people/profile_16.png"; }
	public function getName() { return "General Information"; }
	public function getTooltip() { return "Civil information, contacts (emails, phone...), and other general information"; }
	public function getURL($people_id) { return "/dynamic/people/page/profile_people?people=".$people_id; }
	
}

class PeopleDocumentsPage implements PeopleProfilePagePlugin {

	public function isValidFor($people_id, $types) { return true; }
	public function getPriority() { return 10000; }
	public function getIcon() { return "/static/documents/documents_16.png"; }
	public function getName() { return "Documents"; }
	public function getTooltip() { return "Files attached to this person"; }
	public function getURL($people_id) { return "/dynamic/people/page/people_docs?people=".$people_id; }

}

?>