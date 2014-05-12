<?php 
class page_search extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$q = $_GET["q"];
		require_once("component/data_model/Model.inc");
		foreach (DataModel::get()->getTables() as $table) {
			$display = $table->getDisplayHandler(null);
			if ($display == null) continue;
			// TODO search in each data
			// TODO specify category where to search, and how to display the results
		}
		echo "Search not yet implemented";
	}
	
}
?>