<?php 
interface DataModelCustomizationPlugin {
	
	public function getId();
	public function getDisplayName();
	
	public function getForeignTable();
	
	public function getDataDisplayFileToInclude();
	public function getDataDisplay($col_name, $display_name, $sub_model, $can_be_null);
	
}
?>