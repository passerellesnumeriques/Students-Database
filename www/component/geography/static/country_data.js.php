<?php
require_once("SQLQuery.inc");
require_once("component/PNApplication.inc");
PNApplication::$instance = new PNApplication();
PNApplication::$instance->init();
$country_id = $_GET["id"];
$div = SQLQuery::create()
	->select("CountryDivision")
	->whereValue("CountryDivision", "country", $country_id)
	->field("CountryDivision", "id", "division_id")
	->field("CountryDivision", "name", "division_name")
	->field("CountryDivision", "parent", "division_parent")
	->execute();
if (count($div) == 0) { echo "[]"; return; }
// sort divisions
$divisions = array();
$parent = null;
while (count($div) > 0) {
	for ($i = 0; $i < count($div); $i++)
		if ($div[$i]["division_parent"] == $parent) {
			unset($div[$i]["division_parent"]);
			$parent = $div[$i]["division_id"];
					array_push($divisions, $div[$i]);
					array_splice($div, $i, 1);
					break;
		}
}
for ($i = 0; $i < count($divisions); $i++) {
	$divisions[$i]["areas"] = SQLQuery::create()
		->select("GeographicArea")
		->whereValue("GeographicArea", "country_division", $divisions[$i]["division_id"])
		->field("GeographicArea", "id", "area_id")
		->field("GeographicArea", "name", "area_name")
		->field("GeographicArea", "parent", "area_parent_id")
		->field("GeographicArea", "west", "west")
		->field("GeographicArea", "east", "east")
		->field("GeographicArea", "north", "north")
		->field("GeographicArea", "south", "south")
		->orderBy("area_name")
		->execute();
	for ($j = count($divisions[$i]["areas"])-1; $j >= 0; $j--) {
		if ($divisions[$i]["areas"][$j]["north"] <> null) {
			$divisions[$i]["areas"][$j]["north"] = floatval($divisions[$i]["areas"][$j]["north"]);
			$divisions[$i]["areas"][$j]["south"] = floatval($divisions[$i]["areas"][$j]["south"]);
			$divisions[$i]["areas"][$j]["west"] = floatval($divisions[$i]["areas"][$j]["west"]);
			$divisions[$i]["areas"][$j]["east"] = floatval($divisions[$i]["areas"][$j]["east"]);
		}
	}
}
echo json_encode($divisions);
?>