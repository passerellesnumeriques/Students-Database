<?php
require_once 'component/selection/page/SelectionPage.inc'; 
class page_exam_manual_import_results extends SelectionPage {
	
	public function getRequiredRights() { return array("edit_exam_results"); }
	
	public function executeSelectionPage() {
		echo "This functionality is not yet ready... It will be available in few days...";
		return;
if (!isset($_POST["action"])) {
?>
<div style='background-color:white;padding:5px'>
To import results from an Excel file, it must contain the ID of the applicants.<br/>
<br/>
<form method='POST' enctype='multipart/form-data'>
<input type='hidden' name='action' value='upload'/>
Select the file to import: <input type='file' name='excel'/><br/>
<br/>
<?php 
$subjects = SQLQuery::create()->select("ExamSubject")->execute();
foreach ($subjects as $s) {
	echo "<input type='hidden' name='exam_".$s["id"]."' value='on'/>";
}
?>
Which kind of results does it contain ? 
<select name='results_type'>
	<option></option>
	<?php if (PNApplication::$instance->selection->getOneConfigAttributeValue("set_correct_answer")) { ?>
	<option value='answers'>The answer of the applicants for every question</option>
	<?php } ?>
	<option value='questions_marks'>The mark of the applicants for every question</option>
	<option value='parts_marks'>Only the grade of each part of the exams</option>
</select>
<br/>
<br/>
<input type='submit' value='Start'/>
</form>
</div>
<?php 
} else if ($_POST["action"] == "upload") {
	if (!isset($_FILES['excel'])) {
		PNApplication::error("No file uploaded");
		return;
	}
	if ($_FILES['excel']['error'] <> 0) {
		PNApplication::error(PNApplication::$instance->storage->getUploadError($_FILES['excel']));
		return;
	}
	$subjects = SQLQuery::create()->select("ExamSubject")->execute();
	for ($i = 0; $i < count($subjects); $i++) {
		$val = @$_POST["exam_".$subjects[$i]["id"]];
		if ($val == null || $val == "0" || $val == "off") {
			array_splice($subjects,$i,1);
			$i--;
		}
	}
	if (count($subjects) == 0) {
		PNApplication::error("No subject selected");
		return;
	}
	$results_type = @$_POST["results_type"];
	if ($results_type <> "answers" && $results_type <> "questions_marks" && $results_type <> "parts_marks") {
		PNApplication::error("You didn't select which kind of results the file contain");
		return;
	}
	$path = $_FILES["excel"]["tmp_name"];
	require_once("component/lib_php_excel/PHPExcel.php");
	set_time_limit(300);
	try {
		$reader = PHPExcel_IOFactory::createReaderForFile($path);
		if (get_class($reader) == "PHPExcel_Reader_HTML") throw new Exception();
		$excel = $reader->load($path);
	} catch (Exception $e) {
		PNApplication::error("Invalid file format: ".$e->getMessage());
		return;
	}
	// everything seems fine, let's save the file
	$id = PNApplication::$instance->storage->storeTempFile($path, 60*60);
?>
<div style='background-color:white;padding:5px'>
<form method='POST'>
<input type='hidden' name='action' value='test'/>
<input type='hidden' name='file_id' value='<?php echo $id;?>'/>
<input type='hidden' name='results_type' value='<?php echo $_POST["results_type"];?>'/>
<?php 
/* @var $excel PHPExcel */
foreach ($subjects as $subject) {
	echo "For ".toHTML($subject["name"]).":<br/>";
	echo "<div style='margin-left:20px;'>";
	echo "Which sheet contains results ? <select name='sheet_".$subject["id"]."'>";
	for ($i = 0; $i < $excel->getSheetCount(); $i++) {
		$sheet = $excel->getSheet($i);
		echo "<option value='$i'>".toHTML($sheet->getTitle())."</option>";
	}
	echo "</select><br/>";
	echo "Which column contains the applicant ID ? <input type='text' value='A' name='applicant_id_".$subject["id"]."'/><br/>";
	$parts = SQLQuery::create()->select("ExamSubjectPart")->whereValue("ExamSubjectPart","exam_subject",$subject["id"])->orderBy("ExamSubjectPart","index")->execute();
	if ($_POST["results_type"] == "parts_marks") {
		foreach ($parts as $part) {
			echo "Which column contains the grades for the part ".$part["index"]." (".toHTML($part["name"]).") ? <input type='text' name='column_part_".$part["id"]."'/><br/>";
		}
	} else {
		$parts_ids = array();
		foreach ($parts as $p) array_push($parts_ids, $p["id"]);
		$questions = SQLQuery::create()->select("ExamSubjectQuestion")->whereIn("ExamSubjectQuestion","exam_subject_part",$parts_ids)->execute();
		echo "The sheet must contain one column by question (total of ".count($questions)." questions)<br/>";
		echo "Which column contains the first question ? <input type='text' name='column_first_question_".$subject["id"]."'/><br/>";
	}
	echo "</div>";
}
?>
<input type='submit' value='Continue'/>
</form>
</div>
<?php
} else if ($_POST["action"] == "test") {
	// extend expiration
	PNApplication::$instance->storage->set_expire($_POST["file_id"], 60*60);
	// read Excel file
	require_once("component/lib_php_excel/PHPExcel.php");
	set_time_limit(300);
	$path = PNApplication::$instance->storage->get_data_path($_POST["file_id"]);
	$reader = PHPExcel_IOFactory::createReaderForFile($path);
	$excel = $reader->load($path);
	// get subjects
	$subjects = SQLQuery::create()->select("ExamSubject")->execute();
	echo "<div style='background-color:white;padding:5px'>";
	// TODO refaire, pour prendre les resultats globalement et non pas par sujet !
	$fields = array();
	foreach ($subjects as $subject) {
		//if (!isset($_POST["sheet_".$subject["id"]])) continue;
		$fields["sheet_".$subject["id"]] = $_POST["sheet_".$subject["id"]];
		$fields["applicant_id_".$subject["id"]] = $_POST["applicant_id_".$subject["id"]];
		echo "Subject ".toHTML($subject["name"]).":";
		echo "<div style='margin-left:20px'>";
		$sheet = $excel->getSheet($_POST["sheet_".$subject["id"]]);
		$colname = strtoupper(trim($_POST["applicant_id_".$subject["id"]]));
		$ids_in_file = array();
		$row_numbers = array();
		$row = 1;
		$duplicate_ids = array();
		while ($sheet->cellExists($colname.$row)) {
			$id = $sheet->getCell($colname.$row)->getValue();
			$row++;
			if (!is_numeric($id)) {
				if (!ctype_digit($id))
					continue;
				$id = intval($id);
			}
			if (in_array($id, $ids_in_file)) {
				array_push($duplicate_ids, $id);
				continue;
			}
			array_push($ids_in_file, $id);
			array_push($row_numbers, $row-1);
		}
		if (count($ids_in_file) == 0)
			$in_db = array();
		else
			$in_db = SQLQuery::create()->select("Applicant")->whereIn("Applicant","applicant_id",$ids_in_file)->field("applicant_id")->executeSingleField();
		$unknown_ids = array();
		for ($i = count($ids_in_file)-1; $i >= 0; $i--) {
			if (!in_array($ids_in_file[$i], $in_db)) {
				array_push($unknown_ids, $ids_in_file[$i]);
				array_splice($ids_in_file, $i, 1);
				array_splice($row_numbers, $i, 1);
			}
		}
		if (count($unknown_ids) > 0) {
			echo "The following applicants ID are invalid and won't be imported:<ul>";
			foreach ($unknown_ids as $id) echo "<li>$id</li>";
			echo "</ul>";
		}
		if (count($ids_in_file) == 0) {
			echo "No valid applicant ID found. Nothing can be imported.";
			continue;
		}
		echo count($ids_in_file)." valid applicants ID found.<br/>";
		if (count($duplicate_ids) > 0) {
			echo "We've found ".count($duplicate_ids)." ID several times, we cannot import the file. Here are the duplicate ID:<ul>";
			foreach ($duplicate_ids as $id) echo "<li>$id</li>";
			echo "</ul>";
			continue;
		}
		
		$parts = SQLQuery::create()->select("ExamSubjectPart")->whereValue("ExamSubjectPart","exam_subject",$subject["id"])->orderBy("ExamSubjectPart","index")->execute();
		$has_results = array();
		foreach ($ids_in_file as $id) array_push($has_results, false);
		$errors = array();
		if ($_POST["results_type"] == "parts_marks") {
			foreach ($parts as $part) {
				$column = strtoupper(trim($_POST["column_part_".$part["id"]]));
				$fields["column_part_".$part["id"]] = $_POST["column_part_".$part["id"]];
				for ($i = 0; $i < count($row_numbers); $i++) {
					if ($has_results[$i]) continue;
					$cell = $sheet->getCell($column.$row_numbers[$i]);
					if ($cell == null) continue;
					$val = $cell->getValue();
					if ($val === null) continue;
					if (is_numeric($val)) {
						$has_results[$i] = true;
						$val = floatval($val);
						if ($val > $part["max_score"])
							array_push($errors, "Score for part ".$part["name"]." of applicant ID ".$ids_in_file[$i]." is invalid: greater than the maximum score of ".$part["max_score"]);
					}
				}
			}
		} else {
			$parts_ids = array();
			foreach ($parts as $p) array_push($parts_ids, $p["id"]);
			$questions = SQLQuery::create()->select("ExamSubjectQuestion")
				->whereIn("ExamSubjectQuestion","exam_subject_part",$parts_ids)
				->join("ExamSubjectQuestion","ExamSubjectPart",array("exam_subject_part"=>"id"))
				->orderBy("ExamSubjectPart","index")
				->orderBy("ExamSubjectQuestion","index")
				->execute();
			$first_col = strtoupper(trim($_POST["column_first_question_".$subject["id"]]));
			$fields["column_first_question_".$subject["id"]] = $_POST["column_first_question_".$subject["id"]];
			$first_col_index = PHPExcel_Cell::columnIndexFromString($first_col);
			for ($i = 0; $i < count($row_numbers); $i++) {
				for ($q = 0; $q < count($questions); $q++) {
					$cell = $sheet->getCellByColumnAndRow($first_col_index+$q, $row_numbers[$i]);
					if ($cell == null) continue;
					$val = $cell->getValue();
					if ($val === null) continue;
					if (is_numeric($val)) {
						$has_results[$i] = true;
						if ($_POST["results_type"] == "questions_marks") {
							$val = floatval($val);
							if ($val > $questions[$q]["max_score"])
								array_push($errors, "Score for question ".($q+1)." of applicant ID ".$ids_in_file[$i]." is invalid: greater than the maximum score of ".$questions[$q]["max_score"]);
						}
						break;
					}
				}
			}
		}
		if (count($errors) > 0) {
			echo "We encountered some errors:<ul>";
			foreach ($errors as $e) echo "<li>".toHTML($e)."</li>";
			echo "</ul>";
			return;
		}
		$ok = array();
		$not_ok = array();
		for ($i = 0; $i < count($ids_in_file); $i++)
			if ($has_results[$i])
				array_push($ok, $ids_in_file[$i]);
			else
				array_push($not_ok, $ids_in_file[$i]);
		if (count($not_ok) == 0)
			echo "We've found results for the ".count($ok)." applicants.<br/>";
		else {
			echo "We've found results for ".count($ok)." applicants.<br/>";
			echo "But the following applicants have no result and will be marked as absent:<ul>";
			foreach ($not_ok as $id) echo "<li>$id</li>";
				echo "</ul>";
		}
		// TODO check applicants who already have their attendance/results
		
		echo "</div>";
	}
	echo "<br/>";
?>
<form method='POST'>
<input type='hidden' name='action' value='import'/>
<input type='hidden' name='file_id' value='<?php echo $_POST["file_id"];?>'/>
<input type='hidden' name='results_type' value='<?php echo $_POST["results_type"];?>'/>
<?php foreach ($fields as $name=>$val) echo "<input type='hidden' name='$name' value='$val'/>";?>
<input type='submit' value='Confirm Import'/>
</form>
<?php 
	echo "</div>";
} else if ($_POST["action"] == "import") {
	// extend expiration
	PNApplication::$instance->storage->set_expire($_POST["file_id"], 60*60);
	// read Excel file
	require_once("component/lib_php_excel/PHPExcel.php");
	set_time_limit(300);
	$path = PNApplication::$instance->storage->get_data_path($_POST["file_id"]);
	$reader = PHPExcel_IOFactory::createReaderForFile($path);
	$excel = $reader->load($path);
	// get subjects
	$subjects = SQLQuery::create()->select("ExamSubject")->execute();
	$subject_results = array();
	$subject_part_results = array();
	$subject_question_results = array();
	foreach ($subjects as $subject) {
		//if (!isset($_POST["sheet_".$subject["id"]])) continue;
		$sheet = $excel->getSheet($_POST["sheet_".$subject["id"]]);
		$colname = strtoupper(trim($_POST["applicant_id_".$subject["id"]]));
		$ids_in_file = array();
		$row_numbers = array();
		$row = 1;
		$duplicate_ids = array();
		while ($sheet->cellExists($colname.$row)) {
			$id = $sheet->getCell($colname.$row)->getValue();
			$row++;
			if (!is_numeric($id)) {
				if (!ctype_digit($id))
					continue;
				$id = intval($id);
			}
			if (in_array($id, $ids_in_file)) {
				continue;
			}
			array_push($ids_in_file, $id);
			array_push($row_numbers, $row-1);
		}
		if (count($ids_in_file) == 0)
			$in_db = array();
		else
			$in_db = SQLQuery::create()->select("Applicant")->whereIn("Applicant","applicant_id",$ids_in_file)->field("applicant_id")->executeSingleField();
		for ($i = count($ids_in_file)-1; $i >= 0; $i--) {
			if (!in_array($ids_in_file[$i], $in_db)) {
				array_splice($ids_in_file, $i, 1);
				array_splice($row_numbers, $i, 1);
			}
		}
	
		$parts = SQLQuery::create()->select("ExamSubjectPart")->whereValue("ExamSubjectPart","exam_subject",$subject["id"])->orderBy("ExamSubjectPart","index")->execute();
		/*
		$has_results = array();
		foreach ($ids_in_file as $id) array_push($has_results, false);
		if ($_POST["results_type"] == "parts_marks") {
			for ($i = 0; $i < count($row_numbers); $i++) {
				$total = null;
				foreach ($parts as $part) {
					$column = strtoupper(trim($_POST["column_part_".$part["id"]]));
					$cell = $sheet->getCell($column.$row_numbers[$i]);
					if ($cell == null) continue;
					$val = $cell->getValue();
					if ($val === null) continue;
					if (is_numeric($val)) {
						$has_results[$i] = true;
						$val = floatval($val);
						if ($total === null) $total = $val; else $total += $val;
					}
				}
			}
		} else {
			$parts_ids = array();
			foreach ($parts as $p) array_push($parts_ids, $p["id"]);
			$questions = SQLQuery::create()->select("ExamSubjectQuestion")
			->whereIn("ExamSubjectQuestion","exam_subject_part",$parts_ids)
			->join("ExamSubjectQuestion","ExamSubjectPart",array("exam_subject_part"=>"id"))
			->orderBy("ExamSubjectPart","index")
			->orderBy("ExamSubjectQuestion","index")
			->execute();
			$first_col = strtoupper(trim($_POST["column_first_question_".$subject["id"]]));
			$fields["column_first_question_".$subject["id"]] = $_POST["column_first_question_".$subject["id"]];
			$first_col_index = PHPExcel_Cell::columnIndexFromString($first_col);
			for ($i = 0; $i < count($row_numbers); $i++) {
				for ($q = 0; $q < count($questions); $q++) {
					$cell = $sheet->getCellByColumnAndRow($first_col_index+$q, $row_numbers[$i]);
					if ($cell == null) continue;
					$val = $cell->getValue();
					if ($val === null) continue;
					if (is_numeric($val)) {
						$has_results[$i] = true;
						$val = floatval($val);
						if ($val > $part["max_score"])
							array_push($errors, "Score for question ".($q+1)." of applicant ID ".$ids_in_file[$i]." is invalid: greater than the maximum score of ".$questions[$q]["max_score"]);
						break;
					}
				}
			}
		}
		if (count($errors) > 0) {
			echo "We encountered some errors:<ul>";
			foreach ($errors as $e) echo "<li>".toHTML($e)."</li>";
			echo "</ul>";
			return;
		}
		$ok = array();
		$not_ok = array();
		for ($i = 0; $i < count($ids_in_file); $i++)
			if ($has_results[$i])
				array_push($ok, $ids_in_file[$i]);
				else
					array_push($not_ok, $ids_in_file[$i]);
					if (count($not_ok) == 0)
						echo "We've found results for the ".count($ok)." applicants.<br/>";
					else {
						echo "We've found results for ".count($ok)." applicants.<br/>";
						echo "But the following applicants have no result and will be marked as absent:<ul>";
						foreach ($not_ok as $id) echo "<li>$id</li>";
						echo "</ul>";
					}
					// TODO check applicants who already have their attendance/results
	
					echo "</div>";
					*/
	}
}

	}
	
}
?>