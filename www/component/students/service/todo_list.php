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
		foreach ($batches as $batch) $this->checkBatch($batch, $html);
		if (strlen($html) > 0) $html = "<ul>".$html."</ul>";
		echo $html;
	}
	
	private function checkBatch($batch, &$html) {
		// is there any period ?
		$periods = PNApplication::$instance->curriculum->getAcademicPeriods($batch["id"]);
		if (count($periods) == 0) {
			$html .= "<li>Batch ".htmlentities($batch["name"])." doesn't have any period yet: <a href='#' onclick='edit_batch({id:".$batch["id"]."});return false;'>Edit</a></li>";
		} else {
			// TODO continue
		}
	}
	
}
?>