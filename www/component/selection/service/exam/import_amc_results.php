<?php 
class service_exam_import_amc_results extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Receive files from Scanners, analyze it, and returns its content"; }
	public function inputDocumentation() { echo "A file"; }
	public function outputDocumentation() { echo "the exams results"; }
	
	public function execute(&$component, $input) {
		$ids = array();
		$names = array();
		$types = array();
		$sizes = array();
		PNApplication::$instance->storage->receive_upload($ids, $names, $types, $sizes, 10*60);
		if (count($ids) == 0)
			return;
		echo "{subjects:[";
		for ($file_index = 0; $file_index < count($ids); $file_index++) {
			if ($file_index > 0) echo ",";
			echo "{";
			echo "filename:".json_encode($names[$file_index]);
			$path = PNApplication::$instance->storage->get_data_path($ids[$file_index]);
			require_once("component/lib_php_excel/PHPExcel.php");
			set_time_limit(300);
			try {
				$reader = PHPExcel_IOFactory::createReaderForFile($path);
				if (get_class($reader) == "PHPExcel_Reader_HTML") throw new Exception();
				$excel = $reader->load($path);
				PNApplication::$instance->storage->remove_data($ids[0]);
			} catch (Exception $e) {
				echo ",error:".json_encode("Invalid file format: ".$e->getMessage());
				echo "}";
				continue;
			}
			// 1- search for the sheet containing the marks, and at which row and column it starts
			$marks_sheet = null;
			foreach ($excel->getWorksheetIterator() as $sheet) {
				// search for titles row
				$found = false;
				$row = 1;
				do {
					$cell = $sheet->getCellByColumnAndRow(0, $row);
					if ($cell == null) break;
					$col = 0;
					do {
						$cell = $sheet->getCellByColumnAndRow($col, $row);
						if ($cell == null) break;
						if ($cell->getValue() == "total") {
							$cell = $sheet->getCellByColumnAndRow($col+1, $row);
							if ($cell <> null && $cell->getValue() == "max") {
								$cell = $sheet->getCellByColumnAndRow($col+2, $row);
								if ($cell <> null && is_string($cell->getValue()) && substr($cell->getValue(),0,1) == "Q") {
									$found = true;
									break;
								}
							}
						}
						$col++;
					} while ($col < 20);
					if ($found) break;
					$row++;
				} while ($row < 10);
				if ($found) { $marks_sheet = $sheet; break; }
			}
			if ($marks_sheet == null) {
				echo ",error:".json_encode("Invalid file: does not comply with AMC Software format");
				echo "}";
				continue;
			}
			// 2- count the number of questions, based on the title rows
			$sheet = $marks_sheet;
			$q1_col = $col+2;
			$titles_row = $row;
			$nb_questions = 0;
			$col = $q1_col;
			do {
				$cell = $sheet->getCellByColumnAndRow($col, $titles_row);
				if ($cell == null) break;
				$val = $cell->getValue();
				if (!is_string($val)) break;
				if (substr($val,0,1) <> "Q") break;
				$nb_questions++;
				$col++;
			} while (true);
			echo ",nb_questions:$nb_questions";
			// 3- search for the row containing the first applicant's results (with a student number)
			$row = $titles_row+1;
			$first_applicant = null;
			do {
				$cell = $sheet->getCellByColumnAndRow($q1_col+$nb_questions, $row);
				if ($cell == null) break;
				$val = $cell->getValue();
				if ($val <> null && $val <> "") { $first_applicant = $row; break; }
				$row++;
			} while ($row - $titles_row < 20);
			if ($first_applicant == null) {
				echo ",error:".json_encode("No applicant result found");
				echo "}";
				continue;
			}
			// 4- get the applicants scores
			echo ",applicants:[";
			$row = $first_applicant;
			do {
				$cell = $sheet->getCellByColumnAndRow($q1_col+$nb_questions, $row);
				if ($cell == null) break;
				$val = $cell->getValue();
				if ($val == null || $val == "") break;
				if ($row > $first_applicant) echo ",";
				echo "{";
				echo "id:".json_encode($val);
				echo ",scores:[";
				for ($i = 0; $i < $nb_questions; $i++) {
					$cell = $sheet->getCellByColumnAndRow($q1_col+$i, $row);
					if ($cell == null) $val = null; else $val = $cell->getValue();
					if ($i > 0) echo ",";
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