<?php 
class service_get_exam_subject extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Get the exam subject data"; }
	public function input_documentation() { 
		echo "<code>id</code>: id of the exam subject to get";
	}
	public function output_documentation() { echo "return subject object on success"; }
	public function execute(&$component, $input) {
		if(isset($input['id'])){
			try {
				$subject = PNApplication::$instance->selection->get_json_exam_subject_data($input["id"]);
			} catch (Exception $e){
				PNApplication::error($e);
			}
			if(PNApplication::has_errors())	
				echo "false";
			else
				echo "{subject:".$subject."}";
		} else echo "false";
	}
}
?>