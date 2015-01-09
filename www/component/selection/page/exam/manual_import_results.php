<?php
require_once 'component/selection/page/SelectionPage.inc'; 
class page_exam_manual_import_results extends SelectionPage {
	
	public function getRequiredRights() { return array("edit_exam_results"); }
	
	public function executeSelectionPage() {
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
	$versions = array();
	for ($i = 0; $i < count($subjects); $i++) {
		$val = @$_POST["exam_".$subjects[$i]["id"]];
		if ($val == null || $val == "0" || $val == "off") {
			array_splice($subjects,$i,1);
			$i--;
		} else {
			$versions[$subjects[$i]["id"]] = SQLQuery::create()->select("ExamSubjectVersion")->whereValue("ExamSubjectVersion","exam_subject",$subjects[$i]["id"])->execute();
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
	if (count($versions[$subject["id"]]) > 1)
		echo "Which column contains the exam version ? <input type='text' value='' name='version_".$subject["id"]."'/><br/>";
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
} else {
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
	
	$fields = array();
	$applicants = array();
	$versions = array();
	foreach ($subjects as $subject) {
		$versions[$subject["id"]] = SQLQuery::create()->select("ExamSubjectVersion")->whereValue("ExamSubjectVersion","exam_subject",$subject["id"])->execute();
		$parts = SQLQuery::create()->select("ExamSubjectPart")->whereValue("ExamSubjectPart","exam_subject",$subject["id"])->orderBy("ExamSubjectPart","index")->execute();
		if ($_POST["results_type"] == "parts_marks") {
			$columns = array();
			foreach ($parts as $part) {
				$column = strtoupper(trim($_POST["column_part_".$part["id"]]));
				$fields["column_part_".$part["id"]] = $_POST["column_part_".$part["id"]];
				array_push($columns, $column);
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
		}
		$fields["sheet_".$subject["id"]] = $_POST["sheet_".$subject["id"]];
		$fields["applicant_id_".$subject["id"]] = $_POST["applicant_id_".$subject["id"]];
		if (count($versions[$subject["id"]]) > 1)
			$fields["version_".$subject["id"]] = $_POST["version_".$subject["id"]];
		$sheet = $excel->getSheet($_POST["sheet_".$subject["id"]]);
		$colname = strtoupper(trim($_POST["applicant_id_".$subject["id"]]));
		if (count($versions[$subject["id"]]) > 1)
			$versioncolname = strtoupper(trim($_POST["version_".$subject["id"]]));
		$row = 1;
		while ($sheet->cellExists($colname.$row)) {
			$id = $sheet->getCell($colname.$row)->getValue();
			if (!is_numeric($id)) {
				if (!ctype_digit($id)) {
					$row++;
					continue;
				}
				$id = intval($id);
			}
			if (!isset($applicants[$id])) $applicants[$id] = array();
			if (isset($applicants[$id][$subject["id"]])) {
				echo "The applicant ID $id is present several times for subject ".$subject["name"]."</div>";
				return;
			}
			$info = array("row"=>$row-1);
			if (count($versions[$subject["id"]]) > 1) {
				$cell = $sheet->getCell($versioncolname.$row);
				if ($cell == null) $val = null;
				else $val = $cell->getValue();
				if ($val == null) {
					echo "Invalid or missing exam version for applicant ID $id for subject ".$subject["name"]."</div>";
					return;
				}
				$val = ord(strtoupper($val))-ord("A");
				if ($val < 0 || $val >= count($versions[$subject["id"]])) {
					echo "Invalid exam version for applicant ID $id for subject ".$subject["name"]."</div>";
					return;
				}
				$info["version"] = $versions[$subject["id"]]["id"];
			}
			if ($_POST["results_type"] == "parts_marks") {
				$total = 0;
				$has_result = false;
				$info["parts_score"] = array();
				for ($i = 0; $i < count($parts); $i++) {
					$cell = $sheet->getCell($columns[$i].$row);
					$val = null;
					if ($cell <> null) {
						$val = $cell->getValue();
						if ($val !== null) {
							if (!is_numeric($val)) $val = null;
							else {
								$val = floatval($val);
								if ($val > $parts[$i]["max_score"]) {
									echo "Score for part ".($i+1)." - ".$parts[$i]["name"]." of applicant ID $id is greater than the maximum score of ".$parts[$i]["max_score"]."</div>";
									return;
								}
							}
						}
					}
					if ($val === null)
						$info["parts_score"][$parts[$i]["id"]] = 0;
					else {
						$info["parts_score"][$parts[$i]["id"]] = $val;
						$has_result = true;
						$total += $val;
					}
				}
				if (!$has_result) unset($info["parts_score"]);
				else $info["total_score"] = $total;
			} else {
				$has_result = false;
				$info["total_score"] = 0;
				$info["parts_score"] = array();
				foreach ($parts as $part) $info["parts_score"][$part["id"]] = 0;
				$info["questions"] = array();
				for ($q = 0; $q < count($questions); $q++) {
					$question = $questions[$q];
					$cell = $sheet->getCellByColumnAndRow($first_col_index+$q, $row);
					if ($cell == null) $val = null;
					else $val = $cell->getValue();
					if ($_POST["results_type"] == "questions_marks") {
						if (!is_numeric($val)) $val = null;
						else {
							$val = flotval($val);
							if ($val > $question["max_score"]) {
								echo "Score for question ".($q+1)." of applicant ID $id is greater than the maximum score for this question (".$question["max_score"].")</div>";
								return;
							}
						}
						if ($val === null) {
							$info["questions"][$question["id"]] = 0;
						} else {
							$info["questions"][$question["id"]] = $val;
							$info["parts_score"][$question["exam_subject_part"]] += $val;
							$info["total_score"] += $val;
							$has_result = true;
						}
					} else {
						// TODO correct answer
					}
				}
				if (!$has_result) {
					unset($info["total_score"]);
					unset($info["parts_score"]);
					unset($info["questions"]);
				}
			}
			$applicants[$id][$subject["id"]] = $info;
			$row++;
		}
	}
	
	// check applicant ids are valid
	$in_db = SQLQuery::create()->select("Applicant")->whereIn("Applicant","applicant_id",array_keys($applicants))->field("applicant_id")->executeSingleField();
	foreach ($applicants as $id=>$subjects_infos) {
		if (!in_array($id, $in_db)) {
			unset($applicants[$id]);
			if ($_POST["action"] == "test")
				echo "Applicant ID $id found in the file does not exist: this row will be ignored.<br/>";
		}
	}
	if (count($applicants) == 0) {
		echo "No valid applicant ID found, nothing can be imported.</div>";
		return;
	}
	
	// check if some applicants have partial, or empty results
	$applicants_attendance = array();
	$nb_yes = 0;
	foreach ($applicants as $id=>$subjects_infos) {
		if (count($subjects_infos) == 0) {
			$applicants_attendance[$id] = "No";
			if ($_POST["action"] == "test")
				echo "Applicant ID $id does not have any result: we will mark this applicant as absent and it will be excluded.<br/>";
			continue;
		}
		foreach ($subjects as $subject) {
			if (!isset($subjects_infos[$subject["id"]])) {
				$applicants_attendance[$id] = "Partially";
				if ($_POST["action"] == "test")
					echo "Applicant ID $id was not found for subject ".$subject["name"].": we will set its attendance to Partially, and this applicant will be excluded.<br/>";
			} else if (!array_key_exists("total_score", $subjects_infos[$subject["id"]])) {
				$applicants_attendance[$id] = "Partially";
				if ($_POST["action"] == "test")
					echo "Applicant ID $id was found for subject ".$subject["name"]." but without any result: we will set its attendance to Partially, and this applicant will be excluded.<br/>";
			}
		}
		if (!isset($applicants_attendance[$id])) {
			$applicants_attendance[$id] = "Yes";
			$nb_yes++;
		}
	}
	
	if ($_POST["action"] == "test") {
		echo "Finally, $nb_yes applicants have all the results and will be imported with attendance set to Yes.<br/>";
		echo "<br/>";
		echo "<form method='POST'>";
		echo "<input type='hidden' name='action' value='import'/>";
		echo "<input type='hidden' name='file_id' value='".$_POST["file_id"]."'/>";
		echo "<input type='hidden' name='results_type' value='".$_POST["results_type"]."'/>";
		foreach ($fields as $name=>$val) echo "<input type='hidden' name='$name' value='$val'/>";
		echo "<input type='submit' value='Confirm Import'/>";
		echo "</form>";
		echo "</div>";
		return;
	}
	
	$extracts_list = SQLQuery::create()->select("ExamSubjectExtract")->execute();
	$extracts_parts = array();
	foreach ($extracts_list as $e)
		$extracts_parts[$e["id"]] = SQLQuery::create()->select("ExamSubjectExtractParts")->whereValue("ExamSubjectExtractParts","extract",$e["id"])->field("part")->executeSingleField();
	
	// get database ids
	$list = SQLQuery::create()->select("Applicant")->whereIn("Applicant","applicant_id",array_keys($applicants))->field("people")->field("applicant_id")->execute();
	$applicants_ids = array();
	foreach ($list as $a) $applicants_ids[$a["applicant_id"]] = $a["people"];
	
	SQLQuery::startTransaction();
	// applicants attendance
	$update_applicants = array();
	$ids_absent = array();
	$ids_partial = array();
	$ids_ok = array();
	foreach ($applicants_attendance as $id=>$attendance)
		if ($attendance == "Yes")
			array_push($ids_ok, $applicants_ids[$id]);
		else if ($attendance == "No")
			array_push($ids_absent, $applicants_ids[$id]);
		else
			array_push($ids_partial, $applicants_ids[$id]);
	if (count($ids_absent) > 0)
		array_push($update_applicants, array(
			$ids_absent,
			array(
				"exam_attendance"=>"No",
				"exam_passer"=>0,
				"interview_center"=>null,
				"interview_session"=>null,
				"interview_passer"=>0,
				"excluded"=>1,
				"automatic_exclusion_step"=>"Written Exam",
				"automatic_exclusion_reason"=>"Attendance"
			)
		));
	if (count($ids_partial) > 0)
		array_push($update_applicants, array(
			$ids_partial,
			array(
				"exam_attendance"=>"Partially",
				"exam_passer"=>0,
				"interview_center"=>null,
				"interview_session"=>null,
				"interview_passer"=>0,
				"excluded"=>1,
				"automatic_exclusion_step"=>"Written Exam",
				"automatic_exclusion_reason"=>"Attendance"
			)
		));
	if (count($ids_ok) > 0)
		array_push($update_applicants, array(
			$ids_ok,
			array(
				"exam_attendance"=>"Yes"
			)
		));
	SQLQuery::create()->updateByKeys("Applicant", $update_applicants);
	// remove previous results
	$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamSubject")->whereIn("ApplicantExamSubject","applicant",array_values($applicants_ids))->execute();
	if (count($rows) > 0)
		SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamSubject", $rows);
	$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamSubjectPart")->whereIn("ApplicantExamSubjectPart","applicant",array_values($applicants_ids))->execute();
	if (count($rows) > 0)
		SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamSubjectPart", $rows);
	$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamAnswer")->whereIn("ApplicantExamAnswer","applicant",array_values($applicants_ids))->execute();
	if (count($rows) > 0)
		SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamAnswer", $rows);
	$rows = SQLQuery::create()->bypassSecurity()->select("ApplicantExamExtract")->whereIn("ApplicantExamExtract","applicant",array_values($applicants_ids))->execute();
	if (count($rows) > 0)
		SQLQuery::create()->bypassSecurity()->removeRows("ApplicantExamExtract", $rows);

	$exam_subject = array();
	$exam_subject_part = array();
	$exam_answer = array();
	$exam_extract = array();
	foreach ($applicants as $id=>$subjects_infos) {
		$people_id = $applicants_ids[$id];
		foreach ($subjects_infos as $subject_id=>$infos) {
			if (!array_key_exists("total_score", $infos)) continue;
			if (count($versions[$subject_id]) == 1)
				$version = $versions[$subject_id][0]["id"];
			else
				$version = $versions[$infos["version"]]["id"];
			array_push($exam_subject, array("applicant"=>$people_id,"exam_subject"=>$subject_id,"exam_subject_version"=>$version,"score"=>$infos["total_score"]));
			foreach ($infos["parts_score"] as $part_id=>$part_score) {
				array_push($exam_subject_part, array("applicant"=>$people_id,"exam_subject_part"=>$part_id,"score"=>$part_score));
			}
			if (array_key_exists("questions", $infos))
				foreach ($infos["questions"] as $qid=>$qscore) {
					array_push($exam_answer, array("applicant"=>$people_id,"exam_subject_question"=>$qid,"score"=>$qscore));
					// TODO answer
				}
		}
		foreach ($extracts_parts as $eid=>$parts_ids) {
			$score = 0;
			foreach ($parts_ids as $part_id) {
				foreach ($subjects_infos as $subject_id=>$infos) {
					if (array_key_exists("parts_score", $infos) && array_key_exists($part_id, $infos["parts_score"])) {
						$score += $infos["parts_score"][$part_id];
						break;
					}
				}
			}
			array_push($exam_extract, array("applicant"=>$people_id,"exam_extract"=>$eid,"score"=>$score));
		}
	}
	SQLQuery::create()->bypassSecurity()->insertMultiple("ApplicantExamSubject", $exam_subject);
	SQLQuery::create()->bypassSecurity()->insertMultiple("ApplicantExamSubjectPart", $exam_subject_part);
	if (count($exam_answer) > 0)
		SQLQuery::create()->bypassSecurity()->insertMultiple("ApplicantExamAnswer", $exam_answer);
	if (count($exam_extract) > 0)
		SQLQuery::create()->bypassSecurity()->insertMultiple("ApplicantExamExtract", $exam_extract);
	// apply rules
	PNApplication::$instance->selection->applyExamEligibilityRules();
	if (!PNApplication::hasErrors()) {
		SQLQuery::commitTransaction();
		echo "Results successfully imported.";
	}
	
	echo "</div>";
}

	}
	
}
?>