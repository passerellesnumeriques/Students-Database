<?php

class geography extends Component {
	
	private $local_country = null;
	private $local_country_divisions = null;
	
	public function init() {
		PNApplication::$instance->application->domain_changed->listen($this, "domainChanged");
	}
	
	public function getLocalCountry() { 
		if ($this->local_country == null) {
			$d = PNApplication::$instance->getDomainDescriptor();
			$d = $d["geography"];
			$this->local_country = SQLQuery::create()->bypassSecurity()->select("Country")->whereValue("Country", "code", strtoupper($d["country_code"]))->executeSingleRow();
		}
		return $this->local_country;
	}
	public function getLocalDivisions() {
		if ($this->local_country_divisions == null) {
			$lc = $this->getLocalCountry();
			$this->local_country_divisions = $this->getCountryDivisions($lc["id"]);
		}
		return $this->local_country_divisions;
	}
	
	public function domainChanged() {
		$this->local_country = null;
		$this->local_country_divisions = null;
	}
	
	public function getPluginImplementations() {
		require_once("GeographyAdministrationPlugin.inc");
		require_once("GeographicAreaSearchPlugin.inc");
		require_once("GeographicAreaType.inc");
		return array(
			new GeographyAdministrationPlugin(),
			new GeographicAreaSearchPlugin(),
			new GeographicAreaType()
		);
	}
	
	public function getCountryId($country_code) {
		return SQLQuery::create()->bypassSecurity()->select("Country")->whereValue("Country", "code", strtoupper($country_code))->field("id")->executeSingleField();
	}
	
	public function joinGeographicArea(&$q, $table_from, $table_from_foreign_key, $force_join = true) {
		if (!$force_join) {
			$area_alias = $q->getTableAlias("GeographicArea");
			if ($area_alias <> null) return;
		}
		$area_alias = $q->generateTableAlias();
		$q->join($table_from, "GeographicArea", array($table_from_foreign_key=>"id"), $area_alias);
		return $area_alias;
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
			if ($area == null) { PNApplication::error("Invalid geographic area"); return null; }
			if (strlen($text) > 0) $text .= ", ";
			$text .= $area["name"];
			$area_id = $area["parent"];
		} while ($area_id <> null);
		return $text;
	}
	
	public function getGeographicAreasText($areas_ids) {
		$texts = array();
		foreach ($areas_ids as $id) array_push($texts, "");
		$list = $areas_ids;
		do {
			$areas = SQLQuery::create()->select("GeographicArea")->whereIn("id",$areas_ids)->execute();
			$parents = array();
			$parents_ids = array();
			for ($i = 0; $i < count($texts); ++$i) {
				if ($list[$i] == null) {
					// we are already done for this one
					array_push($parents, null);
					continue;
				}
				$area = null;
				foreach ($areas as $a) if ($a["id"] == $list[$i]) { $area = $a; break; }
				if ($area == null) $area = array("name"=>"Invalid geographic area", "parent"=>null);
				if (strlen($texts[$i])>0) $texts[$i] .= ", ";
				$texts[$i] .= $area["name"];
				array_push($parents, $area["parent"]);
				if ($area["parent"] <> null) array_push($parents_ids, $area["parent"]);
			}
			$areas_ids = $parents_ids;
			$list = $parents;
		} while (count($areas_ids)>0);
		return $texts;
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
		->field("Country","north","north")
		->field("Country","west","west")
		->field("Country","south","south")
		->field("Country","east","east")
		->orderBy("Country", "name", true)
		;
		$countries = $q->execute();
		
		// put the countries of PN domains first
		$PN_countries = array();
		foreach (PNApplication::$instance->getDomains() as $domain=>$descr) {
			$code = $descr["geography"]["country_code"];
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
		$local_code = PNApplication::$instance->getDomainDescriptor();
		$local_code = strtolower($local_code["geography"]["country_code"]);
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
	 * Get all the children (until highest level) of a geographic area in a flat array (no hierarchy between the ids)
	 * @param number | null $area_id null if root level
	 * @return array the ids list, empty array if no children
	 */
	public function getAreaAllChildrenFlat($area_id, $area_division_index, $country_divisions){
		if ($area_division_index >= count($country_divisions)-1) return array(); // last division => no children
		$list = array($area_id);
		$prev_division = array($area_id);
		for ($i = $area_division_index+1; $i < count($country_divisions); $i++) {
			$children = SQLQuery::create()
				->select("GeographicArea")
				->whereIn("GeographicArea", "parent", $prev_division)
				->field("GeographicArea", "id")
				->executeSingleField();
			if (count($children) == 0) break;
			$list = array_merge($list, $children);
			$prev_division = $children;
		}
		return $list;
	}
	
	public function getCountryDivisions($country_id) {
		$rows = SQLQuery::create()->bypassSecurity()->select("CountryDivision")->whereValue("CountryDivision", "country", $country_id)->execute();
		$divisions = array();
		$parent = null;
		while (count($rows) > 0) {
			for ($i = 0; $i < count($rows); $i++) {
				if ($rows[$i]["parent"] == $parent) {
					array_push($divisions, $rows[$i]);
					$parent = $rows[$i]["id"];
					array_splice($rows, $i, 1);
					break;
				}
			}
		}
		return $divisions;
	}
	
	public function getCountryDivisionsFromArea($area) {
		$division = SQLQuery::create()->select("CountryDivision")->whereValue("CountryDivision","id",$area["country_division"])->executeSingleRow();
		return $this->getCountryDivisions($division["country"]);
	}
	
	public function getArea($id) {
		return SQLQuery::create()->select("GeographicArea")->whereValue("GeographicArea","id",$id)->executeSingleRow();
	}
	
	public function getAllAreas($country_id, $division_id) {
		return SQLQuery::create()->select("GeographicArea")->whereValue("GeographicArea", "country_division", $division_id)->execute();
	}
}
?>