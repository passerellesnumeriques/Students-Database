<?php 
require_once("/../selection_page.inc");
class page_IS_profile extends selection_page {
	public function get_required_rights() { return array("see_information_session_details"); }
	public function execute_selection_page(){
		
	$name = $this->generateID();
	$this->add_javascript("/static/widgets/vertical_layout.js");
	$this->onload("new vertical_layout('IS_profile_container');");
	if(!isset($_GET["id"]))
		$id = -1;
	else if($_GET["id"] == "-1")
		$id = -1;
	else
		$id = $_GET["id"];
	$read_only = @$_GET["readonly"];
	$hide_back = @$_GET["hideback"];
	?>
		<div id = "IS_profile_container" style = "width:100%; height:100%">			
			<div id = "page_header">
				<?php if($hide_back != "true"){?>
				<div class = "button_verysoft" onclick = "location.assign('/dynamic/selection/page/IS/main_page');"><img src = '<?php echo theme::$icons_16['back'];?>'/> Back to list</div>
				<?php }?>
				<div class = "button_verysoft" id = "save_IS_button"><img src = '<?php echo theme::$icons_16["save"];?>' /> <b>Save</b></div>
				<div class = "button_verysoft" id = "remove_IS_button"><img src = '<?php echo theme::$icons_16["remove"];?>' /> Remove Information Session</div>
				<?php if($id <> -1){?>
				<div class = "button_verysoft" onclick = "popup_frame('/static/people/people_list_16.png','Applicants','/dynamic/selection/page/applicant/list',{filters:[{category:'Selection',name:'Information Session',data:{value:<?php echo $id;?>}}]},95,95);"><img src = '/static/people/people_list_16.png'/> Applicants List</div>
				<?php }?>
			</div>			
			<div id='IS_profile_<?php echo $name; ?>' style = "overflow:auto" layout = "fill"></div>
		</div>
		
	<?php
		$this->IS_profile("IS_profile_".$name,$id,"save_IS_button","remove_IS_button",$read_only);
	}
	
	/**
	 * The rights of the user is taken into account to set this page, and updated by the steps
	 * @param object $this the page object where the content will be generated
	 * @param number $id the id of the information session
	 * @param string $save_IS_button the id of the save button (must have been added to the page header before calling this function)
	 * @param string $remove_IS_button the id of the remove button (must have been added to the page header before calling this function)
	 * @param boolean $read_only true if the page must be set in uneditable mode
	 */
	public function IS_profile($container_id,$id,$save_IS_button, $remove_IS_button, $read_only){
		$this->add_javascript("/static/widgets/header_bar.js");
		$this->onload("var header = new header_bar('page_header','toolbar'); header.setTitle('/static/selection/IS/IS_16.png', 'Information Session Profile');");
		require_once("component/selection/SelectionJSON.inc");
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
		$config = PNApplication::$instance->selection->getConfig();
		$calendar_id = PNApplication::$instance->selection->getCalendarId();
		$campaign_id = PNApplication::$instance->selection->getCampaignId();
	
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
		?>
		
		<script type='text/javascript'>
			require("IS_profile.js",function(){
				<?php echo "var config = ".json_encode($config).";";?>
				<?php echo "var calendar_id = ".json_encode($calendar_id).";";?>
				<?php echo "var can_edit = ".json_encode($can_edit).";"; ?>
				<?php echo "var can_add = ".json_encode($can_add).";"; ?>
				<?php echo "var can_remove = ".json_encode($can_remove).";"; ?>
				var container = document.getElementById(<?php echo json_encode($container_id); ?>);
				var id = <?php echo json_encode($id).";"; ?>
				var campaign_id = <?php echo json_encode($campaign_id);?>;
				var data, partners_contacts_points, all_duration;
				<?php $all_configs = include("component/selection/config.inc");
				echo "all_duration = ".json_encode($all_configs["default_duration_IS"][2]).";";
				echo "\n";
				$IS_data = SelectionJSON::InformationSessionFromID($id);
				echo "data = ".$IS_data.";";
				echo "\n";
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
					echo "partners_contacts_points = ".get_json_contact_points_no_address($partners).";";
				else
					echo "partners_contacts_points = [];";
				?>
				var save_IS_button = <?php echo json_encode($save_IS_button);?>;
				var remove_IS_button = <?php echo json_encode($remove_IS_button);?>;
				if((id == -1 || id == "-1") && !can_add){
					// This is a creation so check that the current user is allowed to add an IS
					error_dialog("You are not allowed to create an Information Session");
				} else new IS_profile(id, config, calendar_id, can_add, can_edit, can_remove, container, data, partners_contacts_points,all_duration,campaign_id,save_IS_button,remove_IS_button,<?php echo json_encode($db_lock);?>);
			});
		</script>
		<?php
	
	}
}