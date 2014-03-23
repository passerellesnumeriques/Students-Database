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
				<li><code>center_id</code> The exam center ID if the aim is to export an exam center applicants list or exam session applicants list</li>
				<li><code>room_id</code> The exam center room ID if the aim is to export an exam center room applicants list</li>
				<li><code>session_id</code> The exam session ID if the aim is to export an exam session applicants list</li>
				<li><code>order_by</code> Can be "name" or "applicant_id". By default, the data is ordered by applicant ID</li>
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
		$order_by = @$input["order_by"];
		$EC_id = @$input["center_id"];
		$session_id = @$input["session_id"];
		$room_id = @$input["room_id"];
		//Normalize data
		$EC_id = (is_string($EC_id) && strlen($EC_id) == 0) ? null : $EC_id;
		$session_id = (is_string($session_id) && strlen($session_id) == 0) ? null : $session_id;
		$room_id = (is_string($room_id) && strlen($room_id) == 0) ? null : $room_id;
		$order_by = (is_string($order_by) && strlen($order_by) == 0) ? null : $order_by;
		$data = $component->getApplicantsAssignedToCenterEntity($EC_id,$session_id,$room_id,$order_by);
		if($EC_id <> null && $session_id == null && $room_id == null){
			//Export an exam center list
			if($title == null || $file_name == null){			
				//Get the center name
				$center_name = SQLQuery::create()->bypassSecurity()->select("ExamCenter")->field("ExamCenter","name")->executeSingleValue();
				if($title == null)
					$title = $center_name." Exam Center";
				if($file_name == null)
					$file_name = $center_name." applicants";
			}
		} else if($EC_id <> null && $session_id <> null && $room_id == null){
			//Export an exam session list
			if($title == null || $file_name == null){
				//Get the center name
				$center_name = SQLQuery::create()->bypassSecurity()->select("ExamCenter")->field("ExamCenter","name")->executeSingleValue();
				//Get the event start and end
				$ev = SQLQuery::create()
					->bypassSecurity()
					->select("CalendarEvent")
					->field("CalendarEvent","start")
					->field("CalendarEvent","end")
					->whereValue("CalendarEvent", "id", $input["session_id"])
					->executeSingleRow();
				$start = $ev["start"];
				$end = $ev["end"];				
				if($title == null){
					$session_title = date("m",$start)."/".date("d",$start)."/".date("y",$start)." (".date("H",$start).":".date("i",$start)." to ".date("H",$end).":".date("i",$end).")";
					$title = "Session ".$session_title." in ".$center_name." Exam center";
				}
				if($file_name == null){
					$session_title = date("m",$start)."_".date("d",$start)."_".date("y",$start)." (".date("H",$start).":".date("i",$start)." to ".date("H",$end).":".date("i",$end).")";
					$file_name = "Session ".$session_title." in ".$center_name." applicants";
				}			
			}
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