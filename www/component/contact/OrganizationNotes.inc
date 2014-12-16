<?php 
require_once("component/notes/NotesPlugin.inc");
/**
 * Allow to attach notes to an organization
 */
class OrganizationNotes implements NotesPlugin {
	
	public function getAttachedSubModel() { return null; }
	public function getAttachedTable() { return "Organization"; }
	
}
?>