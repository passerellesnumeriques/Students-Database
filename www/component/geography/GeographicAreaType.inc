<?php 
require_once("component/data_model/DataModelCustomizationPlugin.inc");
class GeographicAreaType implements DataModelCustomizationPlugin {
	
	public function getId() { return "geographic_area"; }
	public function getDisplayName() { return "Geographic Area"; }

	public function getForeignTable() { return "GeographicArea"; }

	public function getDataDisplayFileToInclude() { return "component/geography/GeographicAreaDataDisplay.inc"; }
	public function getDataDisplay($col_name, $display_name, $sub_model, $can_be_null) {
		return "new GeographicAreaDataDisplay(\"$col_name\",\"$display_name\",".($can_be_null ? "true" : "false").")";
	}
	
}
?>