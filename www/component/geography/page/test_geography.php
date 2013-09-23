<?php
class page_test_geography extends Page{
	public function get_required_rights(){return array();}
	public function execute() {

		
		echo "<div id='test'></div>";
		$this->add_javascript("/static/geography/set_geographic_area.js");
		$this->onload("new set_geographic_area('test','PH');");
	}
}
?>