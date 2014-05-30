<?php
require_once "component/selection/SelectionJSON.inc";
class service_exam_export_subject extends Service{
	public function getRequiredRights(){return array("see_exam_subject");}
	public function inputDocumentation(){
		?>
		<ul>
			<li><code>format</code> The format of the exported file:<ul><li>"excel2007"</li><li>"excel5"</li></ul></li>
			<li><code>subject</code> The subject to export:<ul><li>The subject id</li><li>or the subject object</li></ul></li>
			<li><code>clickers</code> Boolean. If true, the exported file format will match with SunVoteETS requirements</li>
		</ul>
		<?php
	}
	public function outputDocumentation(){
		echo "No";
	}
	public function getOutputFormat($input){
		return "application/vnd.ms-excel";
	}
	
	public function documentation(){
		echo "Export an exam subject to the specified Excel format";
	}
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
	
	/**
	 * Get the index of a part within the parts array of a subject
	 * @param array $subject ExamSubject
	 * @param number $part_index part index attribute
	 * @return number index seeked
	 */
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
	
	/**
	 * Get the index of a question within the questions array of a part
	 * @param array $subject ExamSubject
	 * @param number $part_index_in_subject index of the part within parts array of the subject
	 * @param number $question_index question index attribute
	 * @return number index of the question
	 */
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