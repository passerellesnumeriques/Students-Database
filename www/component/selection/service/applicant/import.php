<?php 
class service_applicant_import extends Service {
	
	public function get_required_rights() {}
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		if(isset($input["data"])){
			SQLQuery::create()->insert_multiple("Applicant",$input["data"],$input["db_lock"]);
			if(PNApplication::has_errors())
				echo "false";
			else
				echo "true";
			PNApplication::$instance->selection->updateAllSteps();
		} else
			echo "false";
	}
	
}
?>