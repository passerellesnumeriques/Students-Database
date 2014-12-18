<?php 
require_once("component/data_model/Model.inc");
class DocumentationDataModel extends DataModel {

	public function getTableDataDisplay($table_name) {
		$display = parent::getTableDataDisplay($table_name);
		if ($display <> null) return $display;
		require_once 'component/data_model/TableDataDisplay.inc';
		require_once 'component/data_model/DataDisplayCategory.inc';
		$category = new datamodel\DataDisplayCategory("fake");
		return new datamodel\TableDataDisplay($table_name, $category);
	}
	
}
?>