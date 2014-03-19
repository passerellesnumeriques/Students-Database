<?php
require_once "component/selection/SelectionJSON.inc";
class service_applicant_export_list extends Service{
	public function get_required_rights(){return array("can_access_selection_data");}
	public function input_documentation(){
		?>Mandatory fields:
			<ul>
				<li><code>format</code> The format of the exported file:<ul><li>"excel2007"</li><li>"excel5"</li></ul></li>				
			</ul>
			Optional fields:
			<ul>
				<li><code>title</code> The title to set to the List</li>
				<li><code>center_id</code> The exam center ID if the aim is to export an exam center applicants list</li>
				<li><code>room_id</code> The exam center room ID if the aim is to export an exam center room applicants list</li>
				<li><code>session_id</code> The exam session ID if the aim is to export an exam session applicants list</li>
				<li><code>order_by</code> Can be "name" or "applicant_id". By default, the data is ordered by name</li>
				<li><code>file_name</code> The name to set to the exported file</li>
			</ul>
		<?php
	}
	public function output_documentation(){
		echo "No";
	}
	public function get_output_format($input){
		return "application/vnd.ms-excel";
	}
	
	public function documentation(){
		echo "Export an applicant list to Excel.";
	}
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component,$input){
		$format = $input["format"];
		$file_name = @$input["file_name"];
		$title = @$input["title"];
		$data = null;
		if(isset($input["center_id"])){
			//Export an exam center list
			if(isset($input["order_by"]))
				$order_by = $input["order_by"];
			else
				$order_by = "name";
			//Get the center name
			$center_name = SQLQuery::create()->bypassSecurity()->select("ExamCenter")->field("ExamCenter","name")->executeSingleValue();
			if($title == null)
				$title = $center_name." Exam Center Applicants";
			if($file_name == null)
				$file_name = $center_name." applicants";
			$data = $component->getApplicantsAssignedToEC($input["center_id"],$order_by);
		} else {
			echo "false";
			return;
		}
		require_once("component/lib_php_excel/PHPExcel.php");
		$excel = new PHPExcel();
		$excel->createSheet();
		//Set the headers
		$excel->getActiveSheet()->setCellValueByColumnAndRow(0,1, $title);
		$excel->getActiveSheet()->setCellValueByColumnAndRow(0,2, "Applicant ID");
		$excel->getActiveSheet()->setCellValueByColumnAndRow(1,2, "Last Name");
		$excel->getActiveSheet()->setCellValueByColumnAndRow(2,2, "Middle Name");
		$excel->getActiveSheet()->setCellValueByColumnAndRow(3,2, "First Name");
		$excel->getActiveSheet()->setCellValueByColumnAndRow(4,2, "Sex");
		$excel->getActiveSheet()->setCellValueByColumnAndRow(5,2, "Birth");
		//Set the content
		if($data <> NULL){
			$index = 3;
			foreach($data as $applicant){
				$excel->getActiveSheet()->setCellValueByColumnAndRow(0,$index, $applicant["applicant_id"]);
				$excel->getActiveSheet()->setCellValueByColumnAndRow(1,$index, $applicant["last_name"]);
				$excel->getActiveSheet()->setCellValueByColumnAndRow(2,$index, $applicant["middle_name"]);
				$excel->getActiveSheet()->setCellValueByColumnAndRow(3,$index, $applicant["first_name"]);
				$excel->getActiveSheet()->setCellValueByColumnAndRow(4,$index, $applicant["sex"]);
				$excel->getActiveSheet()->setCellValueByColumnAndRow(5,$index, $applicant["birthdate"]);
				$index++;
			}
		}
		$file_end = $format == "excel5" ? ".xls" : ".xlsx";
		header('Content-Disposition: attachment;filename="'.$file_name.$file_end.'"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($excel, $format);
		$objWriter->save('php://output');
	}

}	
?>