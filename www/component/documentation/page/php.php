<?php 
class page_php extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		require_once("PHPDoc.inc");
		$this->add_stylesheet("/static/documentation/CodeDoc.css");
		$this->add_stylesheet("/static/documentation/style.css");
		
		if (isset($_GET["general"]))
			$this->general($_GET["general"]);
		else if (isset($_GET["class"]))
			PHPDoc::class_doc($_GET["class"], $_GET["file"]);
	}
	
	private function general($page) {
		if ($page == "database") {
			include("component/documentation/static/general/php/database.html");
			PHPDoc::generate_doc(array("DataBase.inc", "DataBaseSystem.inc", "DataBaseSystem_MySQL.inc", "SQLQuery.inc"));
		}
		if ($page == "app") {
			//include("component/documentation/static/general/php/database.html");
			PHPDoc::generate_doc(array("component/PNApplication.inc", "component/Component.inc", "component/Page.inc", "component/Service.inc"));
		}
	}
	
}
?>