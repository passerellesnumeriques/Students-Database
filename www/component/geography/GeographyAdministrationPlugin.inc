<?php
require_once("component/administration/AdministrationPlugin.inc");
/**
 * Implementation of AdministrationPlugin for geography, to allow editing geography information
 */ 
class GeographyAdministrationPlugin implements AdministrationPlugin {
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage("Geography", "/dynamic/geography/page/admin", "/static/geography/geography_16.png", "/static/geography/geography_32.png", "Edit geographic information by country", function() { return PNApplication::$instance->user_management->has_right("edit_geography"); }),
		);
	}
	
}
?>