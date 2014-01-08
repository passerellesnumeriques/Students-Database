<?php 
class service_import_applicants extends Service {
	
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
		} else
			echo "false";
	}
	
}
?>