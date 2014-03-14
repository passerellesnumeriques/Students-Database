<?php
require_once("/../selection_page.inc");
require_once("component/selection/SelectionJSON.inc");
class page_exam_convert_IS_into_center extends selection_page {
	
	public function get_required_rights() {return array("see_exam_center_detail","see_information_session_details");}
	
	public function execute_selection_page(&$page) {
		//Get the locks if already exist
		$lock_exam_center = @$_GET["lockec"];
		$lock_information_session = @$_GET["lockis"];
		$lock_ECIS = @$_GET["lockecis"];
		//Perform the required actions
		$EC_id = @$_GET["ec"];
		$IS_ids = @$_GET["is"];//this is an array
		$host_to_add = @$_GET["ishost"];//ID of the information session from which the host data would be copied
		$remove_IS_from_center = @$_GET["remove"];
		if(isset($remove_IS_from_center) || isset($IS_ids) || isset($host_to_add)){
			if($remove_IS_from_center){
				//remove the IS from the ExamCenterInformationSession table
				PNApplication::$instance->selection->unlinkISfromEC($EC_id,$IS_ids[0]);
			} else {
				if($EC_id == null && isset($host_to_add)){
					//Exam center must be created from the host
					$EC_id = PNApplication::$instance->selection->createExamCenterFromISWithHost($host_to_add,null,$lock_exam_center);
					//Link the IS_host to the exam center
					if($EC_id != false)
						PNApplication::$instance->selection->linkIStoEC($EC_id, $host_to_add,$lock_ECIS);
				}
				//Once the exam center is created, we can add the other IS
				if(isset($IS_ids))
					//add the IS to the ExamCenterInformationSession table
					PNApplication::$instance->selection->linkIStoEC($EC_id, $IS_ids,$lock_ECIS);
			}
		}
		
		
		
		//generate the page
		$page->require_javascript("vertical_layout.js");
		$page->onload("new vertical_layout('assign_container');");
		?>
		<div id = "assign_container" style = "width:100%; height:100%; overflow:hidden;">
			<div id = "info_header"layout = "fixed" class = "info_header">
				<img style = "vertical-align:bottom;" src = "<?php echo theme::$icons_16["info"];?>"/> Convert informations sessions into exam centers. You can create an exam center from any informations sessions, or create a new center and then link any information session.<br/><i>Note: only the informations sessions with a host partner are displayed in the list</i>
			</div>
			<div id = "sections_container" layout = "fill"></div>
		</div>
		<?php
		/** Check the rights from user management and steps*/
		$from_step_exam_center = PNApplication::$instance->selection->getRestrictedRightsFromStepsAndUserManagement("exam_center", "manage_exam_center", "manage_exam_center", "manage_exam_center");
		if($from_step_exam_center[1])
			PNApplication::warning($from_step_exam_center[2]);
		$can_add_exam_center = $from_step_exam_center[0]["add"];
		$can_remove_exam_center = $from_step_exam_center[0]["remove"];//No use on this page
		$can_edit_exam_center = $from_step_exam_center[0]["edit"];
		//Maybe the user can still add / edit an exam center, but cannot remove it anymore
		if(!$can_add_exam_center && !$can_edit_exam_center)//Nothing can be done
			return;
		//Lock the IS and the EC tables, if not already the case
		require_once("component/data_model/DataBaseLock.inc");
		$sm = PNApplication::$instance->selection->getCampaignId();
		if(!isset($lock_exam_center))
			$lock_exam_center = $page->performRequiredLocks("ExamCenter",null,null,$sm);//Make sure the EC list is up to date
		else //script is handled by the page#performRequiredLocks method
			DataBaseLock::generateScript($lock_exam_center);
		if(!isset($lock_information_session))
			$lock_information_session = $page->performRequiredLocks("InformationSession",null,null,$sm);//Make sure the IS list is up to date
		else
			DataBaseLock::generateScript($lock_information_session);
		if(!isset($lock_ECIS))
			$lock_ECIS = $page->performRequiredLocks("ExamCenterInformationSession",null,null,$sm);//This lock is the one used to save the data
		else
			DataBaseLock::generateScript($lock_ECIS);
		
		if($lock_exam_center == null || $lock_information_session == null || $lock_ECIS == null){
			?>
			<script type = 'text/javascript'>
			error_dialog("Database is busy so the operation cannot be well processed. Please try again later.");
			</script>
			<?php
			return;
		}
		//Get the data
		$all_free_IS = SelectionJSON::getJSONAllISWithHostAssignedNotLinkedToEC();
		$all_EC = SelectionJSON::getJSONAllExamCenters();
		$all_IS_names = SelectionJSON::getJSONAllInformationsSessionsNames();
		?>
		<script type='text/javascript'>
			require("convert_IS_into_center.js",function(){
				new convert_IS_into_center(
						"sections_container",
						<?php echo json_encode($can_add_exam_center);?>,
						<?php echo json_encode($can_edit_exam_center);?>,
						<?php echo $all_free_IS;?>,
						<?php echo $all_IS_names;?>,
						<?php  echo $all_EC;?>,
						{EC:<?php echo json_encode($lock_exam_center);?>,IS:<?php echo json_encode($lock_information_session)?>,ECIS:<?php echo json_encode($lock_ECIS);?>}
						);
			});
		
		</script>
		<?php
	}
	
}
?>