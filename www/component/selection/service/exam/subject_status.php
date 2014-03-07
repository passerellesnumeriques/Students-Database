<?php
if(!function_exists("getFigure")){
	function getFigure ($db_array){
		$total = 0;
		if(isset($db_array[0])){
			$total = 0;
			foreach($db_array as $value){
				$fig = intval($value);
				$total = $total + $fig;
			}
		}
		return $total;
	}
}
class service_exam_subject_status extends Service{
	public function get_required_rights(){return array("can_access_selection_data");}
	public function input_documentation(){
		echo "No";
	}
	public function output_documentation(){
//TODO update
	}
	public function documentation(){
		echo "This service returns statistics to be displayed on the selection main page <br/> so anyone who is allowed to access selection data must <br/> be allowed to get these data: to avoid any problem, each request is done using bypass_security";
	}
	public function execute(&$component,$input){
		$number_exams = SQLQuery::create()
			->bypassSecurity()
			->select("ExamSubject")
			->count()
			->executeSingleValue();
			
		echo "{number_exams:".json_encode($number_exams);
		echo "}";
	}
}	
?>