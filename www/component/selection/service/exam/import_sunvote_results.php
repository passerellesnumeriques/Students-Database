<?php 
class service_exam_import_sunvote_results extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Receive a file from Clickers, analyze it, and returns its content"; }
	public function inputDocumentation() { echo "A file"; }
	public function outputDocumentation() { echo "the exams results"; }
	
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
		echo "{subjects:[";
		$first_subject = true;
		foreach ($excel->getWorksheetIterator() as $sheet) {
			$cell = $sheet->getCellByColumnAndRow(1, 2);
			if ($cell->getValue() <> "Score") {
				PNApplication::warning("Excel file contains a sheet which is not from SunVote Clickers system: ".$sheet->getTitle());
				continue;
			}
			$col = 2;
			do {
				$cell = $sheet->getCellByColumnAndRow($col, 2);
				if ($cell == null) break;
				$val = $cell->getValue();
				if ($val == null) break;
				if ($val == "") break;
				$col++;
			} while (true);
			$nb_questions = $col-2;
			if ($first_subject) $first_subject = false; else echo ",";
			echo "{";
			echo "name:".json_encode($sheet->getTitle());
			echo ",nb_questions:".$nb_questions;
			echo ",applicants:[";
			$first_app = true;
			$row = 3;
			do {
				$cell = $sheet->getCellByColumnAndRow(0, $row);
				if ($cell == null) break;
				$val = $cell->getValue();
				if ($val == null) break;
				if ($val == "") break;
				if ($first_app) $first_app = false; else echo ",";
				echo "{";
				echo "id:".json_encode($cell->getValue());
				echo ",answers:[";
				for ($q = 0; $q < $nb_questions; $q++) {
					if ($q > 0) echo ",";
					$cell = $sheet->getCellByColumnAndRow($q+2, $row);
					$val = $cell <> null ? $cell->getValue() : null;
					if ($val === "") $val = null;
					echo json_encode($val);
				}
				echo "]";
				echo "}";
				$row++;
			} while (true);
			echo "]";
			echo "}";
		}
		echo "]}";
	}
	
}
?>