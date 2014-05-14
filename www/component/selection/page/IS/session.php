<?php
require_once("component/selection/page/SelectionPage.inc");
class page_IS_session extends SelectionPage {
	
	public function getRequiredRights() { return array("see_information_session_details"); }
	
	public function executeSelectionPage(){
		$id = @$_GET["id"];
		if ($id <> null && $id <= 0) $id = null;
		if ($id <> null) {
			$q = SQLQuery::create()
				->select("InformationSession")
				->whereValue("InformationSession", "id", $id)
				;
			PNApplication::$instance->geography->joinGeographicArea($q, "InformationSession", "geographic_area");
			require_once("component/geography/GeographyJSON.inc");
			GeographyJSON::GeographicAreaTextSQL($q);
			$session = $q->execute();
		} else
			$session = null;
		$editable = $id == null || PNApplication::$instance->user_management->has_right("manage_information_session");
		?>
		<a href='#' onclick='location.reload();return false;'>Reload</a>
		<div style='display:inline-block;margin:10px;'>
		</div>
		<div style='display:inline-block;margin:10px;' id='location_and_partners'>
		<?php
		require_once("component/selection/page/common_centers/location_and_partners.inc");
		locationAndPartners($this, $id, "InformationSession", $session <> null ? GeographyJSON::GeographicAreaText($session) : "null", $editable); 
		?>
		</div>
		<?php 
	}
	
}
?>