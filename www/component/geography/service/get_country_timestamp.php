<?php
class service_get_country_timestamp extends Service{
	
	public function getRequiredRights(){return array();}
	
	public function documentation(){ echo "Return the timestamp of the last modification for the given country"; }
	public function inputDocumentation(){
		echo "country_id";
	}
	public function outputDocumentation(){
		echo "the timestamp when this country has been modified the last time";
	}
	public function execute(&$component, $input){
		echo SQLQuery::create()->select("Country")->whereValue("Country","id",$input["country_id"])->field("last_modified")->executeSingleValue();
	}
}

?>