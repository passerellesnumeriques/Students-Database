<?php
require_once("component/administration/AdministrationPlugin.inc"); 
class DataModelAdministrationPlugin implements AdministrationPlugin {
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage("Database", "/dynamic/data_model/page/administration", "/static/data_model/database.png")
		);
	}
	
}
?>