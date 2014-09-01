<?php 
class page_people_docs extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$people_id = $_GET["people"];
		PNApplication::$instance->documents->insertDivForAttachedDocuments($this, "People", null, $people_id, "people", "large", "full");
	}
	
}
?>