<?php
function exam_subject(&$page,$container_id,$id,$campaign_id){
	require_once("component/data_model/Model.inc");
	$rights = array();
	$rights["read"] = PNApplication::$instance->user_management->has_right("see_exam_subject",true);
	$rights["edit"] = PNApplication::$instance->user_management->has_right("manage_exam_subject",true);
	$rights["remove"] = PNApplication::$instance->user_management->has_right("manage_exam_subject",true);
	$rights["add"] = PNApplication::$instance->user_management->has_right("manage_exam_subject",true);
	$old_rights = $rights;
	// Update according to steps
	$update_rights = selection::canUpdateStep("manage_exam");
	$pop_error = false;
	if(!$update_rights[0] || !$update_rights[1]){
		if(!$update_rights[0]){
			//we cannot valid this step, so cannot add an exam
			$rights["edit"] = false;
			$rights["remove"] = false;
			$rights["add"] = false;
			$pop_error = true;
		} else if(!$update_rights[1]){
			//we cannot unvalid this step, and the forbidden actions on exam_subject table are given in update_rights
			if(!$update_rights[3]["add"])
				$rights["add"] = false;
			if(!$update_rights[3]["remove"])
				$rights["remove"] = false;
			if(!$update_rights[3]["update"])
				$rights["edit"] = false;
			$pop_error = true;
		}
	}
	// In case the rights were not updated, no need to pop an error message
	foreach($rights as $key => $value){
		if($old_rights[$key] != $value){
			$pop_error = true;
			break;
		}
	}
	
	$config = PNApplication::$instance->selection->get_config();
	
	$current_campaign = PNApplication::$instance->selection->get_campaign_id();
	
	$fct_name = "exam_subject_".$page->generate_id();
	$page->onload($fct_name."();");
?>
<script type = "text/javascript">
	function <?php echo $fct_name."(){";?>
		var subject = null;
		var rights = {};
		rights.read = <?php echo json_encode($rights["read"]);?>;
		rights.edit = <?php echo json_encode($rights["edit"]);?>;
		rights.remove = <?php echo json_encode($rights["remove"]);?>;
		rights.add = <?php echo json_encode($rights["add"]);?>;
		
		var config = null;
		<?php if($config <> null) echo "config = ".json_encode($config).";";?>
		var index_correct_answer = findIndexInConfig(config,"set_correct_answer");
		var index_choices = findIndexInConfig(config,"set_number_choices");
		
		var pop_error = <?php echo json_encode($pop_error);?>;
		var allowed_by_steps = <?php 
			if($update_rights[0] && $update_rights[1])
				echo "true;";
			else
				echo "false;";
			?>
		var restriction_text = <?php echo json_encode($update_rights[2]);?>;
		
		var container = document.getElementById(<?php echo json_encode($container_id); ?>);
		
		var exam_id = <?php echo json_encode($id); ?>;
		var campaign_id = <?php echo json_encode($campaign_id); ?>;
		if((exam_id == -1 || exam_id == "-1")){
			subject = {};
			subject.id = -1;
			subject.name = "New Exam";
			subject.max_score = 0;
			subject.parts = [];
		} else if(typeof(campaign_id) == "string") {
			//create an exam subject from an existing one
			<?php
				if(isset($campaign_id)){
					SQLQuery::set_submodel("SelectionCampaign", $campaign_id);
					echo "subject = ".PNApplication::$instance->selection->get_json_exam_subject_data($id).";";
					//reset the current campaign submodel
					SQLQuery::set_submodel("SelectionCampaign", $current_campaign);
				}
			?>
			//Reset the subject id as -1
			subject.id = -1;
		} else {
			subject = <?php echo PNApplication::$instance->selection->get_json_exam_subject_data($id);?>;
			
		}
		
		//init
		if(!rights.read)
			error_dialog("You are not allowed to access to the exam subjects informations");
		else if((exam_id == -1 || exam_id == "-1") && !rights.add){
			error_dialog("You are not allowed to add an exam subject");
			if(!allowed_by_steps && pop_error)
				error_dialog(restriction_text);
		} else {
			if(!pop_error){ // avoid the popup message
				allowed_by_steps = true;
				restriction_text = "";
			}
			require("manage_exam_subject.js",function(){
				new manage_exam_subject(subject,
										container,
										rights.edit,
										rights.remove,
										rights.add,
										config[index_correct_answer].value,
										config[index_choices].value,
										allowed_by_steps,
										restriction_text
										);
			});
		}
	}
</script>

<?php
}
?>