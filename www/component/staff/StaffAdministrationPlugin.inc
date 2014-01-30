<?php
require_once("component/administration/AdministrationPlugin.inc");
/**
 * Implementation of AdministrationPlugin to administrate staff list
 */ 
class StaffAdministrationPlugin implements AdministrationPlugin {
	
	public function getAdministrationPages() {
		return array(
				new AdministrationPage("PN Staff", "/dynamic/staff/page/staff", "/static/staff/staff_16.png"),
		);
	}
	
}
?>