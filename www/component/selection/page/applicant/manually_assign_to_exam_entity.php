<?php
require_once("/../selection_page.inc");
require_once("component/selection/SelectionJSON.inc");
class page_applicant_manually_assign_to_exam_entity extends selection_page {
	
	public function get_required_rights() {return array("manage_applicant");}
	
	public function execute_selection_page(&$page) {		
		$lock = @$_GET["lock"];//Get the lock if already exist
		$mode = $_GET["mode"];
		$applicants = @$_GET["a"];//people id of the selected applicants
		$target = @$_GET["target"];//id of the selected target
		//Perform the required actions
		if(isset($applicants) && isset($target)){
			if($mode == "center"){
				PNApplication::$instance->selection->assignApplicantsToEC($applicants, $target);
			}
		}
	
		//generate the page
		$page->require_javascript("vertical_layout.js");
		$page->onload("new vertical_layout('assign_container');");
		?>
		<div id = "assign_container" style = "width:100%; height:100%; overflow:hidden;">
			<div id = "sections_container" layout = "fill"></div>
		</div>
		<?php
		//Lock the Applicant table
		require_once("component/data_model/DataBaseLock.inc");
		$sm = PNApplication::$instance->selection->getCampaignId();
		if(!isset($lock))
			$lock = $page->performRequiredLocks("Applicant",null,null,$sm);
		else //script is handled by the page#performRequiredLocks method
			DataBaseLock::generateScript($lock);
		
		if($lock == null){
			?>
			<script type = 'text/javascript'>
			error_dialog("Database is busy so the operation cannot be well processed. Please try again later.");
			</script>
			<?php
			return;
		}
		//Get the data from the matching provider
		if($mode == "center"){
			$data = $this->getJSONDataForAssigningApplicantToCenter();
		}
		?>
		<script type='text/javascript'>
			require("applicant_manually_assign_to_entity.js",function(){
				new applicant_manually_assign_to_entity(
						"sections_container",
						<?php echo $data[0];?>,
						<?php echo $data[1];?>,
						<?php echo json_encode($mode);?>,
						<?php  echo $lock;?>
						);
			});
		
		</script>
		<?php
	}
	
	/**
	 * Provider for the center assignment mode, after checking the user rights
	 * @return array <ul><li>0: json all free applicants</li><li>1: json all exam centers</li></ul>
	 */
	private function getJSONDataForAssigningApplicantToCenter(){
		$all_free_applicants = PNApplication::$instance->selection->getApplicantsNotAssignedToAnyEC();
		$json_all_free_applicants = "[";
		if($all_free_applicants <> null){
			$first = true;
			foreach ($all_free_applicants as $applicant){
				if(!$first) $json_all_free_applicants .= ", ";
				$first = false;
				$json_all_free_applicants .= SelectionJSON::Applicant(null, $applicant);
			}
		}
		$json_all_free_applicants .= "]";
		$centers = SelectionJSON::getJSONAllExamCenters();
		return array($json_all_free_applicants, $centers);
	}
	
}
?>