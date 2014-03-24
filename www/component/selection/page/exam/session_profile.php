<?php 
require_once("/../selection_page.inc");
class page_exam_session_profile extends selection_page {
	public function get_required_rights() { return array("see_exam_center_detail"); }
	public function execute_selection_page(&$page){
		$id = $_GET["id"];
	?>
		<div id = exam_session_profile_container style = "width:100%; height:100%">
			<div>
				<div id = "date_container" style = "display:inline-block;"></div>
				<div id = "supervisors_container" style = "display:inline-block;"></div>
			</div>
			<div id = "applicants_list_container"></div>
		</div>
		
	<?php
		//Get rights from steps
		$from_steps = PNApplication::$instance->selection->getRestrictedRightsFromStepsAndUserManagement("exam_center", "manage_exam_center", "manage_exam_center", "manage_exam_center");
		if($from_steps[1])
			PNApplication::warning($from_steps[2]);
		$can_add = $from_steps[0]["add"];
		$can_remove = $from_steps[0]["remove"];
		$can_edit = $from_steps[0]["edit"];
		$campaign_id = PNApplication::$instance->selection->getCampaignId();
		//Lock
		$db_lock = $page->performRequiredLocks("ExamSession",$id,null,$campaign_id);
		//if db_lock = null => read only
		if($db_lock == null){
			$can_add = false;
			$can_edit = false;
			$can_remove = false;
		}
		require_once 'component/selection/SelectionJSON.inc';
		$session = SelectionJSON::ExamSessionFromID($id);
		?>
		<script type = "text/javascript">
		require("exam_session_profile.js",function(){
			new exam_session_profile(
					"exam_session_profile_container",
					"date_container",
					"supervisors_container",
					"applicants_list_container",
					<?php echo $session;?>,
					<?php echo json_encode($can_edit);?>
				);
		});		 
		</script>
		<?php
	}
	
	
	
}