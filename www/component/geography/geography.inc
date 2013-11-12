<?php
require_once("component/administration/AdministrationPlugin.inc");
class geography extends Component implements AdministrationPlugin {
	
	public function get_geographic_area_text($area_id) {
		$text = "";
		do {
			$area = SQLQuery::create()->select("Geographic_area")->where("id",$area_id)->execute_single_row();
			if (strlen($text) > 0) $text .= ", ";
			$text .= $area["name"];
			$area_id = $area["parent"];
		} while ($area_id <> null);
		return $text;
	}
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage("Geography", "/dynamic/geography/page/admin", "/static/geography/geography_16.png"),
		);
	}
	
}
?>