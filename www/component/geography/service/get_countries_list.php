<?php
class service_get_countries_list extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){}//TODO
	public function output_documentation(){}//TODO
	public function documentation(){}//TODO
	public function execute(&$component,$input){
		$q = SQLQuery::create()->select("Country")
						->field("Country","id","country_id")
						->field("Country","code","country_code")
						->field("Country","name","country_name");
		$countries = $q->execute();
		echo "[";
		$first = true;
		foreach($countries as $country){
			if(!$first) echo ", ";
			echo "{country_id:".json_encode($country["country_id"]).", ";
			echo "country_code:".json_encode($country["country_code"]).", ";
			echo "country_name:".json_encode($country["country_name"])."}";
			$first = false;		
		}
		echo "]";
	}
}