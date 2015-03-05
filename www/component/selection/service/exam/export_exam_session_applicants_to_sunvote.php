<?php 
class service_exam_export_exam_session_applicants_to_sunvote extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Generate an Excel file that can be imported in SunVote system"; }
	public function inputDocumentation() { echo "The session and the room"; }
	public function outputDocumentation() { echo "The file"; }
	public function getOutputFormat($input) {
		return "application/vnd.ms-excel";
	}
	
	public function execute(&$component, $input) {
		$input = $input["input"];
		$session_id = $input["session"];
		$room_id = $input["room"];

		$room = SQLQuery::create()->select("ExamCenterRoom")->whereValue("ExamCenterRoom","id",$room_id)->executeSingleRow();
		$session = PNApplication::$instance->calendar->getEvent(PNApplication::$instance->selection->getCalendarId(), $session_id);
		$center = SQLQuery::create()->select("ExamCenter")->whereValue("ExamCenter","id",$room["exam_center"])->executeSingleRow();
		
		$q = SQLQuery::create()->select("Applicant")->whereValue("Applicant","exam_session", $session_id)->whereValue("Applicant","exam_center_room",$room_id)->orderBy("Applicant","applicant_id");
		PNApplication::$instance->people->joinPeople($q, "Applicant", "people", false);
		$q->field("Applicant", "applicant_id", "applicant_id");
		$applicants = $q->execute();
		
		error_reporting(E_ERROR | E_PARSE);
		require_once("component/lib_php_excel/PHPExcel.php");
		$excel = new PHPExcel();
		$sheet = new PHPExcel_Worksheet($excel, "Answers");
		$excel->addSheet($sheet);
		$excel->removeSheetByIndex(0);
		// column headers
		$sheet->setCellValueByColumnAndRow(0, 1, "Keypad ID");
		$sheet->setCellValueByColumnAndRow(1, 1, "Student ID");
		$sheet->setCellValueByColumnAndRow(2, 1, "Student Name");
		// applicants
		for ($i = 0; $i < count($applicants); $i++) {
			//$sheet->setCellValueByColumnAndRow(0,$i+2,$i+1); // changed to applicant ID as asked by PNC
			$sheet->setCellValueByColumnAndRow(0,$i+2,$applicants[$i]["applicant_id"]);
			$sheet->setCellValueByColumnAndRow(1,$i+2,$applicants[$i]["applicant_id"]);
			$sheet->setCellValueByColumnAndRow(2,$i+2,$applicants[$i]["first_name"]." ".$applicants[$i]["last_name"]);
		}
		// replacement keypads
		for ($j = 0; $j < 10; $j++) {
			$sheet->setCellValueByColumnAndRow(0,$i+$j+2,$i+$j+1);
			$sheet->setCellValueByColumnAndRow(1,$i+$j+2,99001+$j);
			$sheet->setCellValueByColumnAndRow(2,$i+$j+2,99001+$j);
		}
		
		header("Content-Disposition: attachment; filename=\"".$center['name']."_Session_".date("Ymd_hia",$session["start"])."_Room_".$room["name"]."_Applicants_List.xlsx\"");
		$writer = new PHPExcel_Writer_Excel2007($excel);
		$writer->save('php://output');
	}
	
}
?>