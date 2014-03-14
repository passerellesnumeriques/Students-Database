<?php 
class page_pictures extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		require_once("component/people/PeopleJSON.inc");
		$classes_ids = null;
		if (isset($_GET["batches"])) {
			$batches = explode(",", $_GET["batches"]);
			$q = SQLQuery::create()->select("Student")->whereIn("Student", "batch", $batches);
			PNApplication::$instance->people->joinPeople($q, "Student", "people");
		} else if (isset($_GET["period"]) && isset($_GET["spe"])) {
			$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($_GET["period"], $_GET["spe"]);
			$classes_ids = array();
			foreach ($classes as $c) array_push($classes_ids, $c["id"]);
		} else if (isset($_GET["period"])) {
			$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($_GET["period"]);
			$classes_ids = array();
			foreach ($classes as $c) array_push($classes_ids, $c["id"]);
		} else if (isset($_GET["class"])) {
			$classes_ids = array($_GET["class"]);
		} else {
			$q = SQLQuery::create()->select("Student");
			PNApplication::$instance->people->joinPeople($q, "Student", "people");
		}
		if ($classes_ids <> null) {
			$q = SQLQuery::create()->select("StudentClass")->whereIn("StudentClass", "class", $classes_ids);
			PNApplication::$instance->people->joinPeople($q, "StudentClass", "people");
		}
		PeopleJSON::PeopleSQL($q);
		$q->limit(0, 1000);
		$rows = $q->execute();
		$this->add_javascript("/static/people/pictures_list.js");
		$id = $this->generateID();
		echo "<div id='$id' style='width:100%;height:100%'></div>";
		echo "<script type='text/javascript'>";
		echo "peoples = ".PeopleJSON::Peoples($q, $rows).";";
		echo "new pictures_list('$id',peoples);";
		echo "</script>";
	}
	
}
?>