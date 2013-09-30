<?php
class page_test_geography extends Page{
	public function get_required_rights(){return array();}
	public function execute() {

		
		echo "<div id='test'></div>";
		// $this->add_javascript("/static/geography/set_geographic_area.js");
		// $this->onload("new set_geographic_area('test','PH');");
		
		$this->add_javascript("/static/geography/geographic_area_selection.js");
		$this->onload("new geographic_area_selection('test','PH');");
		
		 // $q = SQLQuery::create()->select("Country")
						// ->field("Country","id","country_id")
						// ->field("Country","code","country_code")
						// ->field("Country","name","country_name")
						// ->field("Country_division","id","division_id")
						// ->field("Country_division","parent","division_parent_id")
						// ->field("Country_division","name","division_name")
						// ->field("Geographic_area","id","area_id")
						// ->field("Geographic_area","name","area_name")
						// ->field("Geographic_area","parent","area_parent_id")
						// ->join("Country","Country_division",array("id"=>"country"))
						// ->join("Country_division","Geographic_area",array("id"=>"country_division"))
						// ->where("Country.code='PH'")
						// ->order_by("division_id");
		// $country_data=$q->execute();
		// if(!isset($country_data[0]['division_id'])){
		// echo "vide";}
	}
}
?>