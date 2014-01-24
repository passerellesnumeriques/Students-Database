<?php
require_once '/../../SelectionJSON.inc';
class service_exam_export_subject extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){
	}
	public function output_documentation(){
	}
	public function get_output_format($input){
		return "application/vnd.ms-excel";
	}
	
	public function documentation(){}//TODO
	public function execute(&$component,$input){
		$format = $input["format"];
		$subject = null;
		if(isset($input["subject"])){
			if(is_string($input["subject"]))
				$subject = json_decode($input["subject"], true);
			else 
				$subject = $input["subject"];
		} else
			$subject = json_decode(json_normalize(SelectionJSON::ExamSubjectFromID($input["id"])),true);
		require_once("component/lib_php_excel/PHPExcel.php");
		$excel = new PHPExcel();
		$excel->createSheet();
		if(isset($input["clickers"])){
			/* Export with the clickers format */
			$excel->getActiveSheet()->setCellValueByColumnAndRow(0,1, "No.");
			$excel->getActiveSheet()->setCellValueByColumnAndRow(1,1, "Topic content");
			$excel->getActiveSheet()->setCellValueByColumnAndRow(2,1, "Correct answer");
			$excel->getActiveSheet()->setCellValueByColumnAndRow(3,1, "Score");
			$excel->getActiveSheet()->setCellValueByColumnAndRow(4,1, "Options");
			$index = 1;
			for($i = 1; $i <= count($subject["parts"]); $i++){
				$part_index_in_subject = $this->getPartIndexInSubject($subject, $i);
				for($j = 1; $j <= count($subject["parts"][$part_index_in_subject]["questions"]); $j++){
					$question_index_in_subject = $this->getQuestionIndexInSubject($subject, $part_index_in_subject, $j);
					$excel->getActiveSheet()->setCellValueByColumnAndRow(0,$index+1, $index);
					$excel->getActiveSheet()->setCellValueByColumnAndRow(2,$index+1, strtoupper($subject["parts"][$part_index_in_subject]["questions"][$question_index_in_subject]["correct_answer"]));
					$excel->getActiveSheet()->setCellValueByColumnAndRow(3,$index+1, $subject["parts"][$part_index_in_subject]["questions"][$question_index_in_subject]["max_score"]);
					$excel->getActiveSheet()->setCellValueByColumnAndRow(4,$index+1, $subject["parts"][$part_index_in_subject]["questions"][$question_index_in_subject]["choices"]);
					$index++;
				}
			}
		} else {
			$excel->getActiveSheet()->setCellValueByColumnAndRow(0,1, "No.");
			$excel->getActiveSheet()->setCellValueByColumnAndRow(1,1, "Correct answer");
			$excel->getActiveSheet()->setCellValueByColumnAndRow(2,1, "Score");
			$excel->getActiveSheet()->setCellValueByColumnAndRow(3,1, "Options");
			$index = 1;
			for($i = 1; $i <= count($subject["parts"]); $i++){
				$part_index_in_subject = $this->getPartIndexInSubject($subject, $i);
				for($j = 1; $j <= count($subject["parts"][$part_index_in_subject]["questions"]); $j++){
					$question_index_in_subject = $this->getQuestionIndexInSubject($subject, $part_index_in_subject, $j);
					$excel->getActiveSheet()->setCellValueByColumnAndRow(0,$index+1, $index);
					$excel->getActiveSheet()->setCellValueByColumnAndRow(1,$index+1, $subject["parts"][$part_index_in_subject]["questions"][$question_index_in_subject]["correct_answer"]);
					$excel->getActiveSheet()->setCellValueByColumnAndRow(2,$index+1, $subject["parts"][$part_index_in_subject]["questions"][$question_index_in_subject]["max_score"]);
					$excel->getActiveSheet()->setCellValueByColumnAndRow(3,$index+1, $subject["parts"][$part_index_in_subject]["questions"][$question_index_in_subject]["choices"]);
					$index++;
				}
			}
		}
		header('Content-Disposition: attachment;filename="'.$subject["name"].'.xlsx"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($excel, $format);
		$objWriter->save('php://output');
	}
	
	public function getPartIndexInSubject($subject,$part_index){
		$index = null;
		for($i = 0; $i < count($subject["parts"]); $i++){
			if($subject["parts"][$i]["index"] == $part_index){
				$index = $i;
				break;
			}
		}
		return $index;
	}
	
	public function getQuestionIndexInSubject($subject, $part_index_in_subject, $question_index ){
		$index = null;
		for($i = 0; $i < count($subject["parts"][$part_index_in_subject]["questions"]); $i++){
			if($subject["parts"][$part_index_in_subject]["questions"][$i]["index"] == $question_index){
				$index = $i;
				break;
			}
		}		
		return $index;
	}
}	
?>