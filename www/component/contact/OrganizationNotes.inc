<?php 
require_once("component/notes/NotesPlugin.inc");
class OrganizationNotes implements NotesPlugin {
	
	public function getAttachedSubModel() { return null; }
	public function getAttachedTable() { return "Organization"; }
	
}
?>