<?php 
class page_map extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		// get students list
		if (isset($_GET["batches"])) {
			$batches_ids = array();
			if ($_GET["batches"] == "current") {
				$list = PNApplication::$instance->curriculum->getCurrentBatches();
				foreach ($list as $b) array_push($batches_ids, $b["id"]);
			} else if ($_GET["batches"] == "alumni") {
				$list = PNApplication::$instance->curriculum->getAlumniBatches();
				foreach ($list as $b) array_push($batches_ids, $b["id"]);
			}
			$students_ids = SQLQuery::create()->select("Student")->whereIn("Student","batch",$batches_ids)->field("people")->executeSingleField();
		} else if (isset($_GET["class"])) {
			$students_ids = SQLQuery::create()->select("StudentClass")->whereValue("StudentClass","class",$_GET["class"])->field("people")->executeSingleField();
		} else if (isset($_GET["specialization"])) {
			$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($_GET["period"], $_GET["specialization"]);
			$classes_ids = array();
			foreach($classes as $c) array_push($classes_ids, $c["id"]);
			$students_ids = SQLQuery::create()->select("StudentClass")->whereIn("StudentClass","class",$classes_ids)->field("people")->executeSingleField();
		} else if (isset($_GET["period"])) {
			$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($_GET["period"]);
			$classes_ids = array();
			foreach($classes as $c) array_push($classes_ids, $c["id"]);
			$students_ids = SQLQuery::create()->select("StudentClass")->whereIn("StudentClass","class",$classes_ids)->field("people")->executeSingleField();
		} else if (isset($_GET["batch"])) {
			$students_ids = SQLQuery::create()->select("Student")->whereValue("Student","batch",$_GET["batch"])->field("people")->executeSingleField();
		} else {
			$students_ids = SQLQuery::create()->select("Student")->field("people")->executeSingleField();
		}
		$this->requireJavascript("contact_map.js");
?>
<div id='container' style='width:100%;height:100%;'>
</div>
<script type='text/javascript'>
new contact_map('container','Map of Students','people',<?php echo json_encode($students_ids);?>,['Family']);
</script>
<?php 
	}
	
}
?>