<?php
require_once("component/administration/AdministrationPlugin.inc");
/**
 * Implementation of AdministrationPlugin to administrate staff list
 */ 
class StaffAdministrationPlugin implements AdministrationPlugin {
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage("PN Staff", "/dynamic/staff/page/list", "/static/staff/staff_16.png", "/static/staff/staff_32.png", "Manage list of staff and their position in PN", function() { return PNApplication::$instance->user_management->hasRight("consult_staff_list"); }),
		);
	}
	
}
?>