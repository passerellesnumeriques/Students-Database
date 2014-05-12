<?php 
require_once "component/selection/SelectionJSON.inc";
class service_exam_get_subject extends Service {
	public function getRequiredRights() { return array("see_exam_subject"); }
	public function documentation() { echo "Get the exam subject data"; }
	public function inputDocumentation() { 
		echo "<code>id</code>: id of the exam subject to get";
	}
	public function outputDocumentation() { echo "return subject object on success"; }
	public function execute(&$component, $input) {
		if(isset($input['id'])){
			try {
				$subject = SelectionJSON::ExamSubjectFromID($input["id"]);
			} catch (Exception $e){
				PNApplication::error($e);
			}
			if(PNApplication::hasErrors())	
				echo "false";
			else
				echo "{subject:".$subject."}";
		} else echo "false";
	}
}
?>