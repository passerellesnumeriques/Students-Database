<?php 
class service_todo_list extends Service {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	public function get_output_format($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		$html = "";
		$batches = PNApplication::$instance->curriculum->getBatches();
		foreach ($batches as $batch) {
			$h = "";
			$this->checkBatch($batch, $h);
			if ($h <> "") $html .= "<li>Batch ".htmlentities($batch["name"])."<ul>".$h."</ul></li>";
		}
		if (strlen($html) > 0) $html = "<ul>".$html."</ul>";
		echo $html;
	}
	
	private function checkBatch($batch, &$html) {
		// is there any period ?
		$periods = PNApplication::$instance->curriculum->getAcademicPeriods($batch["id"]);
		if (count($periods) == 0) {
			$html .= "<li>No period yet: <a href='#' onclick='edit_batch({id:".$batch["id"]."});return false;'>Edit</a></li>";
		}
		// is there any student ?
		$students = SQLQuery::create()->select("Student")->whereValue("Student", "batch", $batch["id"])->execute();
		if (count($students) == 0) {
			$html .= "<li>No student yet: <a href='list?batches=".$batch["id"]."' target='students_page'>Go to the list</a></li>";
		}
		foreach ($periods as $period) {
			$h = "";
			$this->checkPeriod($period, $h);
			if ($h <> "") $html .= "<li>Period ".htmlentities($period["name"]).":<ul>".$h."</ul></li>";
		}
	}
	
	private function checkPeriod($period, &$html) {
		// is there any subject ?
		$subjects = PNApplication::$instance->curriculum->getSubjects($period["batch"], $period["id"]);
		if (count($subjects) == 0) {
			$html .= "<li>No subject in the curriculum: <a href='/dynamic/curriculum/page/curriculum?period=".$period["id"]."' target='students_page'>Edit</a></li>";
		}
	}
	
}
?>