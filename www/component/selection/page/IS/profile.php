<?php 
require_once("/../SelectionPage.inc");
class page_IS_profile extends SelectionPage {
	public function getRequiredRights() { return array("see_information_session_details"); }
	public function executeSelectionPage(){
		require_once("component/selection/SelectionJSON.inc");
		$config = PNApplication::$instance->selection->getConfig();
		$calendar_id = PNApplication::$instance->selection->getCalendarId();
		$campaign_id = PNApplication::$instance->selection->getCampaignId();
		
		if(!isset($_GET["id"]))
			$id = -1;
		else if($_GET["id"] == "-1")
			$id = -1;
		else
			$id = $_GET["id"];
		$read_only = @$_GET["readonly"] == "true";
	
		$can_read = PNApplication::$instance->user_management->has_right("see_information_session_details",true);
		if(!$can_read)
			return;
		if($read_only == "true"){
			$can_add = false;
			$can_edit = false;
			$can_remove = false;
		} else {
			//Get rights from steps
			$from_steps = PNApplication::$instance->selection->getRestrictedRightsFromStepsAndUserManagement("information_session", "manage_information_session", "manage_information_session", "edit_information_session");
			if($from_steps[1])
				PNApplication::warning($from_steps[2]);
			$can_add = $from_steps[0]["add"];
			$can_remove = $from_steps[0]["remove"];
			$can_edit = $from_steps[0]["edit"];
		}
		
		//lock the row if id != -1
		$db_lock = null;
		if($id != -1){
			$db_lock = $this->performRequiredLocks("InformationSession",$id,null,$campaign_id);
			//if db_lock = null => read only
			if($db_lock == null){
				$can_add = false;
				$can_edit = false;
				$can_remove = false;
			}
		}
		
		$this->requireJavascript("IS_profile.js");
		?>
		<div id = "IS_profile_container" style = "width:100%; height:100%">			
		</div>
		<script type='text/javascript'>
		var config = <?php echo json_encode($config);?>;
		var calendar_id = <?php echo json_encode($calendar_id);?>;
		var can_edit = <?php echo json_encode($can_edit);?>;
		var can_add = <?php echo json_encode($can_add);?>;
		var can_remove = <?php echo json_encode($can_remove);?>;
		var container = document.getElementById("IS_profile_container");
		var id = <?php echo json_encode($id).";"; ?>
		var campaign_id = <?php echo json_encode($campaign_id);?>;
		<?php $all_configs = include("component/selection/config.inc"); ?>
		var all_duration = <?php echo json_encode($all_configs["default_duration_IS"][2]);?>;
		var data = <?php echo SelectionJSON::InformationSessionFromID($id);?>;
		<?php
		//Select all the partners IDs
		$partners = array();
		if($id != -1 && $id != "-1")
			$partners = SQLQuery::create()
			->select("InformationSessionPartner")
			->field("organization")
			->whereValue("InformationSessionPartner","information_session",$id)
			->executeSingleField();
		require_once("component/contact/service/get_json_contact_points_no_address.inc");
		echo "\n";
		if($id != -1 && $id != "-1")
			echo "var partners_contacts_points = ".get_json_contact_points_no_address($partners).";";
		else
			echo "var partners_contacts_points = [];";
		?>
		var popup = window.parent.get_popup_window_from_frame(window);
		if((id == -1 || id == "-1") && !can_add){
			// This is a creation so check that the current user is allowed to add an IS
			error_dialog("You are not allowed to create an Information Session");
		} else { 
			var is = new IS_profile(id, config, calendar_id, can_add, can_edit, can_remove, container, data, partners_contacts_points,all_duration,campaign_id,<?php echo json_encode($db_lock);?>);
			<?php if ($can_edit) {?>
			popup.addIconTextButton(theme.icons_16.save, "Save", "save", function() {
				popup.freeze();
				is.save(function(ok) {
					popup.unfreeze();
				});
			});
			<?php } ?>
			<?php if ($can_remove && $id <> -1) {?>
			popup.addIconTextButton(theme.icons_16.remove, "Remove this session", "remove", function() {
				confirm_dialog("Remove this information session and all the linked data?",function(res){
					if(res){
						popup.freeze();
						service.json("selection","IS/remove",{id:data.id},function(r){
							popup.unfreeze();
							if(r) {
								popup.close();
							} else
								error_dialog("An error occured");
						});
					}
				});
			});
			<?php } ?>
			<?php if($id <> -1){?>
			popup.addIconTextButton('/static/people/people_list_16.png', "See Applicants List", "applicants", function() {
				popup_frame('/static/people/people_list_16.png','Applicants','/dynamic/selection/page/applicant/list',{filters:[{category:'Selection',name:'Information Session',data:{value:<?php echo $id;?>}}]},95,95);
			});
			<?php }?>
		}
		popup.addCloseButton();
		</script>
		<?php
	
	}
}