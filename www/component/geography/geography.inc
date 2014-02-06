<?php

class geography extends Component {
	
	public function getPluginImplementations() {
		require_once("GeographyAdministrationPlugin.inc");
		return array(
			new GeographyAdministrationPlugin()
		);
	}
	
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
	
}
?>