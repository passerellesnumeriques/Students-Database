<?php
class page_test_geography extends Page{
	public function get_required_rights(){return array();}
	public function execute() {

		
		echo "<div id='test'></div>";
		// $this->add_javascript("/static/geography/set_geographic_area.js");
		// $this->onload("new set_geographic_area('test','PH');");
		
		$this->add_javascript("/static/geography/geographic_area_selection.js");
		$this->onload("new geographic_area_selection('test','PH');");
		
		 // echo "<p>Returns a javascript <b>object</b>, giving the geographic structure of the country</p>";
		// echo "<p> This object structure is the following:</p>";
		// echo "<ul>";
		// echo "<li>If no country division: {}</li>";
		// echo "<li>Else: [{division_id: , division_name: ,areas:[<i>One object by area</i>]}]";
		// echo "<ul><li>If no area, areas:[]</li>";
		// echo "<li>Else areas:[{area_id: ,area_parent_id: , area_name: }]</li>";
		// echo "</ul>";
		// echo "</li>";
		// echo "</ul>";
	
	}
}
?>