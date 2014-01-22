<?php 
class service_exam_subject_remove extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		if(isset($input["id"])){
			$res = $component->removeExam($input["id"]);
			if($res)
				echo "true";
			else
				echo "false";
		} else echo "false";
	}
	
}
?>