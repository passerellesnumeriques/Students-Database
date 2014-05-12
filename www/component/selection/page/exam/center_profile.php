<?php 
require_once("/../selection_page.inc");
class page_exam_center_profile extends selection_page {
	public function getRequiredRights() { return array("see_exam_center_detail"); }
	public function execute_selection_page(){
	/**
	 * Create a page with two sections: one for the exam center caracteristics, and one for the applicants assignment into this exam center
	 */
	$name = $this->generateID();
	$this->add_javascript("/static/widgets/vertical_layout.js");
	$this->add_javascript("/static/widgets/section/section.js");
	$this->onload("var s_detail = section_from_html('center_detail_section');");
	$this->onload("s_detail.addToolBottom('<div class = \"button_verysoft\" id = \"save_center_button\"><img src =\"'+theme.icons_16.save+'\"/> <b>Save</b></div>');");
	$this->onload("s_detail.addToolBottom('<div class = \"button_verysoft\" id = \"remove_center_button\"><img src =\"'+theme.icons_16.remove+'\"/> <b>Remove Exam Center</b></div>');");
	$this->onload("section_from_html('center_applicants');");
	$this->onload("new vertical_layout('exam_center_profile_container');");
	$this->onload('onupdateroom = new Custom_Event();');
	$this->onload('onupdateapplicants = new Custom_Event();');
	if(!isset($_GET["id"]))
		$id = -1;
	else if($_GET["id"] == "-1")
		$id = -1;
	else
		$id = $_GET["id"];
	$read_only = @$_GET["readonly"];
	$hide_back = @$_GET["hideback"];
	?>
		<div id = exam_center_profile_container style = "width:100%; height:100%">
			<div id = "page_header">
			<?php if($hide_back != "true"){?>
				<div class = "button_verysoft" onclick = "location.assign('/dynamic/selection/page/exam/center_main_page');"><img src = '<?php echo theme::$icons_16['back'];?>'/> Back to list</div>
			<?php }?>
			</div>
			<div style = "overflow:auto" layout = "fill">
				<div id = "center_detail_section" title='Exam Centers Caracteristics' collapsable='true'style='margin:10px;'>
					<div id='exam_center_<?php echo $name; ?>' ></div>
				</div>
				<div id = "center_applicants" title='Exam Centers Applicants' collapsable='true' style='margin:10px;'>
					<div id = "exam_center_applicants_container"></div>				
				</div>
			</div>
		</div>
		
	<?php
		$this->exam_center_caracteristics("exam_center_".$name,$id,"save_center_button","remove_center_button",$read_only);
		if($id != -1)
			$this->exam_applicants("exam_center_applicants_container", $id,$read_only);
	}
	
	/**
	 * Create the section containing the exam center caracteristics (location, partners, name, rooms)
	 * @param string $container_id
	 * @param number $id exam center ID
	 * @param string $save_exam_center_button save exam center button ID (onclick implemented by the exam_center_profile script)
	 * @param string $remove_exam_center_button remove exam center button ID (onclick implemented by the exam_center_profile script)
	 * @param boolean $read_only indicates if the page shall be in read only mode (user rights restricted)
	 */
	private function exam_center_caracteristics($container_id,$id,$save_exam_center_button, $remove_exam_center_button,$read_only){
		$this->add_javascript("/static/widgets/header_bar.js");
		$this->onload("var header = new header_bar('page_header','toolbar'); header.setTitle('', 'Exam Center Profile');");
		require_once("component/selection/SelectionJSON.inc");
		$can_read = PNApplication::$instance->user_management->has_right("see_exam_center_detail",true);
		if(!$can_read)
			return;
		if($read_only == "true"){
			$can_add = false;
			$can_edit = false;
			$can_remove = false;
		} else {
			//Get rights from steps
			$from_steps = PNApplication::$instance->selection->getRestrictedRightsFromStepsAndUserManagement("exam_center", "manage_exam_center", "manage_exam_center", "manage_exam_center");
			if($from_steps[1])
				PNApplication::warning($from_steps[2]);
			$can_add = $from_steps[0]["add"];
			$can_remove = $from_steps[0]["remove"];
			$can_edit = $from_steps[0]["edit"];
		}	
		$config_name = PNApplication::$instance->selection->getOneConfigAttributeValue("give_name_to_exam_center");
		$config_name_room = PNApplication::$instance->selection->getOneConfigAttributeValue("give_name_to_exam_center_room");
		$campaign_id = PNApplication::$instance->selection->getCampaignId();
	
		//lock the row if id != -1
		$db_lock = null;
		if($id != -1){
			$db_lock = $this->performRequiredLocks("ExamCenter",$id,null,$campaign_id);
			//if db_lock = null => read only
			if($db_lock == null){
				$can_add = false;
				$can_edit = false;
				$can_remove = false;
			}
		}
		?>
		
		<script type='text/javascript'>
			require("exam_center_profile.js",function(){
				<?php echo "var config_name = ".json_encode($config_name).";";?>
				<?php echo "var config_name_room = ".json_encode($config_name).";";?>
				<?php echo "var can_edit = ".json_encode($can_edit).";"; ?>
				<?php echo "var can_add = ".json_encode($can_add).";"; ?>
				<?php echo "var can_remove = ".json_encode($can_remove).";"; ?>
				var container = document.getElementById(<?php echo json_encode($container_id); ?>);
				var id = <?php echo json_encode($id).";"; ?>
				var campaign_id = <?php echo json_encode($campaign_id);?>;
				var data, partners_contacts_points;
				<?php
				echo "\n";
				$center_data = SelectionJSON::ExamCenterFromID($id);
				echo "data = ".$center_data.";";
				echo "\n";
				//Select all the partners IDs
				$partners = array();
				if($id != -1 && $id != "-1")
					$partners = SQLQuery::create()
						->select("ExamCenterPartner")
						->field("organization")
						->whereValue("ExamCenterPartner","exam_center",$id)
						->executeSingleField();
				require_once("component/contact/service/get_json_contact_points_no_address.inc");
				echo "\n";
				if($id != -1 && $id != "-1")
					echo "partners_contacts_points = ".get_json_contact_points_no_address($partners).";";
				else
					echo "partners_contacts_points = [];";
				?>
				var save_exam_center_button = <?php echo json_encode($save_exam_center_button);?>;
				var remove_exam_center_button = <?php echo json_encode($remove_exam_center_button);?>;
				if((id == -1 || id == "-1") && !can_add){
					// This is a creation so check that the current user is allowed to add an exam center
						error_dialog("You are not allowed to create an Exam Center");
				} else new exam_center_profile(id, config_name, can_add, can_edit, can_remove, container, data,partners_contacts_points,campaign_id,save_exam_center_button,remove_exam_center_button,<?php echo json_encode($db_lock);?>,config_name_room,onupdateroom,onupdateapplicants);
			});
		</script>
		<?php
	
	}
	
	/**
	 * Create the exam applicants section: contains all the data related to the applicant assignment for the center (assigned to center, session, room...)
	 * @param string $container_id
	 * @param number $center_id exam center ID
	 * @param boolean $read_only indicates if the page shall be in read only mode (user rights restricted)
	 */
	private function exam_applicants($container_id, $center_id, $read_only){
		$can_manage_applicants = PNApplication::$instance->user_management->has_right("manage_applicant");
		if($read_only)
			$can_manage_applicants = false;
		$EC_name = SQLQuery::create()
			->select("ExamCenter")
			->field("ExamCenter","name")
			->whereValue("ExamCenter", "id", $center_id)
			->executeSingleValue();
		$this->add_javascript("/static/selection/exam/center_exam_sessions_planned.js");
		$this->onload("new center_exam_sessions_planned('".$container_id."',".json_encode($center_id).",".json_encode($EC_name).",".json_encode($can_manage_applicants).",onupdateroom,onupdateapplicants);");
	}
}