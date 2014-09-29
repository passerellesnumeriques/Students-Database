<?php 
class service_exam_export_exam_answers_to_sunvote extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Generate an Excel file that can be imported in SunVote system"; }
	public function inputDocumentation() { echo "The subject"; }
	public function outputDocumentation() { echo "The file"; }
	public function getOutputFormat($input) {
		return "application/vnd.ms-excel";
	}
	
	public function execute(&$component, $input) {
		$input = $input["input"];
		$subject_id = $input["subject"];
		$version_index = $input["version_index"];
		
		$subject = SQLQuery::create()->select("ExamSubject")->whereValue("ExamSubject","id",$subject_id)->executeSingleRow();
		$versions = SQLQuery::create()->select("ExamSubjectVersion")->whereValue("ExamSubjectVersion","exam_subject",$subject_id)->field("id")->orderBy("ExamSubjectVersion","id")->execute();
		$parts = SQLQuery::create()->select("ExamSubjectPart")->whereValue("ExamSubjectPart", "exam_subject", $subject_id)->orderBy("ExamSubjectPart", "index")->execute();
		$parts_ids = array(); foreach ($parts as $p) array_push($parts_ids, $p["id"]);
		$questions = SQLQuery::create()->select("ExamSubjectQuestion")->whereIn("ExamSubjectQuestion", "exam_subject_part", $parts_ids)->orderBy("ExamSubjectQuestion", "exam_subject_part")->orderBy("ExamSubjectQuestion", "index")->execute();
		$answers = SQLQuery::create()->select("ExamSubjectAnswer")->whereValue("ExamSubjectAnswer","exam_subject_version",$versions[$version_index]["id"])->execute();
		
		error_reporting(E_ERROR | E_PARSE);
		require_once("component/lib_php_excel/PHPExcel.php");
		$excel = new PHPExcel();
		$sheet = new PHPExcel_Worksheet($excel, "Answers");
		$excel->addSheet($sheet);
		$excel->removeSheetByIndex(0);
		// column headers
		$sheet->setCellValueByColumnAndRow(0, 1, "No.");
		$sheet->setCellValueByColumnAndRow(1, 1, "Topic content");
		$sheet->setCellValueByColumnAndRow(2, 1, "Correct answer");
		$sheet->setCellValueByColumnAndRow(3, 1, "Score");
		$sheet->setCellValueByColumnAndRow(4, 1, "Options");
		// questions and answers
		for ($i = 0; $i < count($questions); $i++) {
			$sheet->setCellValueByColumnAndRow(0,$i+2,$i+1);
			$sheet->setCellValueByColumnAndRow(1,$i+2,"");
			$sheet->setCellValueByColumnAndRow(3,$i+2,$questions[$i]["max_score"]);
			$sheet->setCellValueByColumnAndRow(4,$i+2,$questions[$i]["type_config"]);
			foreach ($answers as $a)
				if ($a["exam_subject_question"] == $questions[$i]["id"]) {
					$sheet->setCellValueByColumnAndRow(2,$i+2,$a["answer"]);
					break;
				}
		}
		
		header("Content-Disposition: attachment; filename=\"".$subject['name']."Answers.xlsx\"");
		$writer = new PHPExcel_Writer_Excel2007($excel);
		$writer->save('php://output');
	}
	
}
?>