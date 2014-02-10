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
			->field("Country","name","country_name")
			->orderBy("Country", "name", true)
			;
		$countries = $q->execute();
		
		// put the countries of PN domains first
		$PN_countries = array();
		foreach (PNApplication::$instance->get_domains() as $domain=>$descr) {
			$code = $descr["country_code"];
			$code = strtolower($code);
			if (!in_array($code, $PN_countries)) array_push($PN_countries, $code);
		}
		for ($i = 0; $i < count($countries); $i++) {
			$code = strtolower($countries[$i]["country_code"]); 
			if (in_array($code, $PN_countries)) {
				// found
				$country = $countries[$i];
				array_splice($countries, $i, 1); // remove it
				array_splice($countries, 0, 0, array($country)); // insert it
			}
		}
		
		// put the domain country first
		$local_code = PNApplication::$instance->get_domain_descriptor();
		$local_code = strtolower($local_code["country_code"]);
		for ($i = 0; $i < count($countries); $i++) {
			if (strtolower($countries[$i]["country_code"]) == $local_code) {
				// found
				$country = $countries[$i];
				array_splice($countries, $i, 1); // remove it
				array_splice($countries, 0, 0, array($country)); // insert it
				break;
			}
		}
		
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