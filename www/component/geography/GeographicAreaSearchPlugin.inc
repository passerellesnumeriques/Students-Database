<?php 
require_once("component/search/SearchPlugin.inc");
class GeographicAreaSearchPlugin implements SearchPlugin {
	
	public function getId() { return "geographic_area"; }
	public function getName() { return "Geographic Areas"; }
	public function getPriority() { return 1000; }
	
	public function getTitle($nb) {
		return $nb." Geographic Area".($nb > 1 ? "s" : "");
	}
	
	public function hasCategories() { return true; }
	public function sortByCategory($results) {
		$res = array();
		foreach ($results as $area) {
			if (!isset($res[$area["country_id"]]))
				$res[$area["country_id"]] = array("title"=>"<img src='/static/geography/flags/".$area["country_code"].".png' style='vertical-align:bottom'/> ".toHTML($area["country_name"]),"categories"=>array());
			if (!isset($res[$area["country_id"]]["categories"][$area["division_id"]]))
				$res[$area["country_id"]]["categories"][$area["division_id"]] = array("title"=>toHTML($area["division_name"]),"rows"=>array());
			array_push($res[$area["country_id"]]["categories"][$area["division_id"]]["rows"], $area);
		}
		return $res;
	}
	
	
	public function genericSearch($s) {
		$words = explode(" ", $s);
		$q = SQLQuery::create()->select("GeographicArea");
		$where = "";
		foreach ($words as $w) {
			if ($where <> "") $where .= " OR ";
			$where .= "`GeographicArea`.`name` LIKE '%".SQLQuery::escape($w)."%'";
		}
		$q->where($where);
		$q->join("GeographicArea","CountryDivision",array("country_division"=>"id"));
		$q->join("CountryDivision","Country",array("country"=>"id"));
		$q->field("GeographicArea","id","area_id");
		$q->field("GeographicArea","name","area_name");
		$q->field("CountryDivision","id","division_id");
		$q->field("CountryDivision","name","division_name");
		$q->field("Country","id","country_id");
		$q->field("Country","code","country_code");
		$q->field("Country","name","country_name");
		return $q->execute();
	}
	
	public function generateResultRow($row) {
		echo "<td>";
		echo toHTML($row["area_name"]);
		echo "</td>";
	}
	
	public function getResultIcon($row) { return "/static/geography/geography_16.png"; }
	public function getResultTitle($row) { return "Geographic Area"; }
	public function getResultUrl($row) { return ""; } // TODO
	
}
?>