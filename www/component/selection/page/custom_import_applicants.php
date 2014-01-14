<?php
require_once("selection_page.inc");
class page_custom_import_applicants extends selection_page {
	
	public function get_required_rights() {}
	
	public function execute_selection_page(&$page) {
		require_once("custom_import_applicants.inc");
		/* Check the rights */
		if(!PNApplication::$instance->user_management->has_right("create_applicant",true)){
			echo "<div style='font-color:red;'>You are not allowed to create any applicant</div>";
		} else {
			$generate_id = PNApplication::$instance->selection->getOneConfigAttributeValue("generate_applicant_id");
			$check_date = PNApplication::$instance->selection->getOneConfigAttributeValue("forbid_too_old_applicants");
			$limit_date = null;
			$fixed_data = array();
			$prefilled_data = array();
			if($generate_id)
				/* Fix the applicant id */
				array_push($fixed_data,array("category"=>"Applicant Information","name"=>"Applicant ID","data"=>"0"));
			else
				array_push($prefilled_data,array("category"=>"Applicant Information","name"=>"Applicant ID","data"=>"1"));
				
			if($check_date)
				$limit_date = PNApplication::$instance->selection->getOneConfigAttributeValue("limit_date_of_birth");
			
			/* Get all the current applicant ids before the Applicant table gets locked by the page */
			$all_ids = PNApplication::$instance->selection->externalGetAllApplicantsIds();
			
			custom_import_applicants($page,$fixed_data,$prefilled_data,$all_ids,$generate_id,$check_date,$limit_date);
		}
	}
	
}
?>