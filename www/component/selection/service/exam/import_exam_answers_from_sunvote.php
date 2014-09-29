<?php 
class service_exam_import_exam_answers_from_sunvote extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Receive a file from Clickers, analyze it, and returns its content"; }
	public function inputDocumentation() { echo "A file"; }
	public function outputDocumentation() { echo "the answers"; }
	
	public function execute(&$component, $input) {
		$ids = array();
		$names = array();
		$types = array();
		$sizes = array();
		PNApplication::$instance->storage->receive_upload($ids, $names, $types, $sizes, 5*60);
		if (count($ids) == 0)
			return;
		$path = PNApplication::$instance->storage->get_data_path($ids[0]);
		require_once("component/lib_php_excel/PHPExcel.php");
		set_time_limit(300);
		try {
			$reader = PHPExcel_IOFactory::createReaderForFile($path);
			if (get_class($reader) == "PHPExcel_Reader_HTML") throw new Exception();
			$excel = $reader->load($path);
			PNApplication::$instance->storage->remove_data($ids[0]);
		} catch (Exception $e) {
			PNApplication::error("Invalid file format: ".$e->getMessage());
			return;
		}
		$found_sheet = false;
		foreach ($excel->getWorksheetIterator() as $sheet) {
			$cell = $sheet->getCellByColumnAndRow(0, 1);
			if ($cell == null || $cell->getValue() <> "No.") continue;
			$cell = $sheet->getCellByColumnAndRow(1, 1);
			if ($cell == null || $cell->getValue() <> "Topic content") continue;
			$cell = $sheet->getCellByColumnAndRow(2, 1);
			if ($cell == null || $cell->getValue() <> "Correct answer") continue;
			$cell = $sheet->getCellByColumnAndRow(3, 1);
			if ($cell == null || $cell->getValue() <> "Score") continue;
			$cell = $sheet->getCellByColumnAndRow(4, 1);
			if ($cell == null || $cell->getValue() <> "Options") continue;
			$found_sheet = true;
			$row = 2;
			echo "{questions:[";
			do {
				$cell = $sheet->getCellByColumnAndRow(2,$row);
				if ($cell == null) break;
				$answer = $cell->getValue();
				$cell = $sheet->getCellByColumnAndRow(3,$row);
				if ($cell == null) break;
				$score = $cell->getValue();
				$cell = $sheet->getCellByColumnAndRow(4,$row);
				if ($cell == null) break;
				$options = $cell->getValue();
				if ($answer == null && $score == null && $options == null) break;
				if ($row > 2) echo ",";
				echo "{";
				echo "answer:".json_encode($answer);
				echo ",max_score:".json_encode($score);
				echo ",nb_choices:".json_encode($options);
				echo "}";
				$row++;
			} while ($row < 500);
			echo "]}";
		}
		if (!$found_sheet) {
			PNApplication::error("This file does not seem to be from the Clickers System, we cannot import it.");
			return;
		}
	}
	
}
?>