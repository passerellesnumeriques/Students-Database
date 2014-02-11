<?php

class geography extends Component {
	
	public function getPluginImplementations() {
		require_once("GeographyAdministrationPlugin.inc");
		return array(
			new GeographyAdministrationPlugin()
		);
	}
	
	/**
	 * Get the text to display from an area id, based on the area name and its parents names<br/>
	 * For instance, the area_text for Cebu City from Cebu province is "Cebu City, Cebu"
	 * @param number $area_id
	 * @return string
	 */
	public function get_geographic_area_text($area_id) {
		$text = "";
		do {
			$area = SQLQuery::create()->select("GeographicArea")->where("id",$area_id)->executeSingleRow();
			if (strlen($text) > 0) $text .= ", ";
			$text .= $area["name"];
			$area_id = $area["parent"];
		} while ($area_id <> null);
		return $text;
	}
	
	/**
	 * Get all the countries set into the database<br/>
	 * The coutries are ordered:
	 * <ul><li>First country is the one of the current domain</li><li>Then come all the other "PN countries"</li><li>then all the other countries set into the database</li>
	 * </ul>
	 * @return array countries
	 */
	public function getCountriesList(){
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
		return $countries;
	}
	
}
?>