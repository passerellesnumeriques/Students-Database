<?php
require_once("component/administration/AdministrationPlugin.inc");
/**
 * Implementation of AdministrationPlugin, to provide pages in the administration section to manage database functionalities
 */ 
class DataModelAdministrationPlugin implements AdministrationPlugin {
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage("Database", "/dynamic/data_model/page/administration", "/static/data_model/database.png", "/static/data_model/database_32.png", "Control status of the database")
		);
	}
	
}
?>