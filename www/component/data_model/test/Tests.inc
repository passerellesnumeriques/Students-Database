<?php 
class data_model_Tests extends ComponentTests {
	
	public function getFunctionalitiesTests() {
		return array(
			"Read_Access_Table",
			"Write_Access_Table",
			"Insert_Access_Table",
			"Remove_Access_Table",
			"RemoveLinks",
			"Locks",
			// TODO Datadisplay
			// TODO modify/add: check validation of values/unicity...
			// TODO all tests again with sub models
			// TODO test on tables with no primary key
		);
	}
	public function getServicesTests() {
		return array(
		);
	}
	public function getUITests() {
		return array(
		);
	}
	
}
?>