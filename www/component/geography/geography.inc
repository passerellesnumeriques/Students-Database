<?php

class geography extends Component {
	
	public function getPluginImplementations() {
		require_once("GeographyAdministrationPlugin.inc");
		return array(
			new GeographyAdministrationPlugin()
		);
	}
	
	public function joinGeographicArea(&$q, $table_from, $table_from_foreign_key, $force_join = true) {
		if (!$force_join) {
			$area_alias = $q->getTableAlias("GeographicArea");
			if ($area_alias <> null) return;
		}
		$area_alias = $q->generateTableAlias();
		$q->join($table_from, "GeographicArea", array($table_from_foreign_key=>"id"), $area_alias);
	}
	
	/**
	 * Get the text to display from an area id, based on the area name and its parents names<br/>
	 * For instance, the area_text for Cebu City from Cebu province is "Cebu City, Cebu"
	 * @param number $area_id
	 * @return string
	 */
	public function getGeographicAreaText($area_id) {
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
	
	/**
	 * Get the geographic area parent id, from a given area id
	 * @param number $child_id the id of the geographic area
	 * @return number|null, the request result
	 */
	public function getAreaParent($child_id){
		return SQLQuery::create()
			->select("GeographicArea")
			->field("GeographicArea","parent")
			->whereValue("GeographicArea", "id", $child_id)
			->executeSingleValue();
	}
	
	/**
	 * Get all the children ids that belong to the level below the one of the given area
	 * @param number | null $area_id null if root level
	 * @return array containing the children ids, empty array if no children
	 */
	public function getAreaChildrenLevelBelow ($area_id){
		$children = array();
		$q = SQLQuery::create()
			->select("GeographicArea")
			->field("GeographicArea","id")
			->whereValue("GeographicArea", "parent", $area_id)
			->executeSingleField();
		if($q <> null)
			$children = $q;
		return $children;
	}
	
	/**
	 * Get all the children (until highest level) of a geographic area in a flat array (no hierarchy between the ids)
	 * @param number | null $area_id null if root level
	 * @return array the ids list, empty array if no children
	 */
	public function getAreaAllChildrenFlat($area_id){
		$children = array();
		$to_browse = $this->getAreaChildrenLevelBelow($area_id);
		while(count($to_browse) > 0){
			//Get all the children of the first to_browse id
			$new_children = $this->getAreaChildrenLevelBelow($to_browse[0]);
			//Add this first id to the children array
			array_push($children, $to_browse[0]);
			//Remove this first id from to_browse array
			array_splice($to_browse, 0,1);
			//Push all the new children into to_browse array
			foreach ($new_children as $child)
				array_push($to_browse, $child);
		}
		return $children;
	}
}
?>