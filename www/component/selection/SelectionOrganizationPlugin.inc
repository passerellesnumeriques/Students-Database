<?php 
require_once("component/contact/OrganizationPlugin.inc");
/**
 * Implementation of OrganizationPlugin for the selection: the selection handles its own organizations (creator Selection)
 */
class SelectionOrganizationPlugin implements OrganizationPlugin {
	
	public function getOrganizationCreator() {
		return "Selection";
	}
	public function canReadOrganization() {
		return PNApplication::$instance->user_management->has_right("can_access_selection_data",true);
	}
	public function canWriteOrganization() {
		// TODO put the correct right
		return PNApplication::$instance->user_management->has_right("manage_selection_campaign",true);
	}
	public function canInsertOrganization() {
		// TODO put the correct right
		return PNApplication::$instance->user_management->has_right("manage_selection_campaign",true);
	}
	public function canRemoveOrganization() {
		// TODO put the correct right
		return PNApplication::$instance->user_management->has_right("manage_selection_campaign",true);
	}
}
?>