<?php
function exam_subject(&$page,$container_id,$id,$campaign_id,$read_only){
	require_once("component/data_model/Model.inc");
	$rights = array();
	$pop_error = false;
	$update_rights = PNApplication::$instance->selection->canUpdateStep("manage_exam");
	$rights["read"] = PNApplication::$instance->user_management->has_right("see_exam_subject",true);
	$rights["edit"] = PNApplication::$instance->user_management->has_right("manage_exam_subject",true);
	$rights["remove"] = PNApplication::$instance->user_management->has_right("manage_exam_subject",true);
	$rights["add"] = PNApplication::$instance->user_management->has_right("manage_exam_subject",true);
	// Update according to steps				
	$restricted = PNApplication::$instance->selection->updateRightsFromStepsDependenciesRestrictions("manage_exam",$rights["add"],$rights["remove"],$rights["edit"]);
	// In case the rights were not updated, no need to pop an error message
	$pop_error = $restricted[0];
	
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

		var restriction_text = <?php if(isset($restricted)) echo json_encode($restricted[1]);
			else echo json_encode(false);
		?>;
		
		var container = document.getElementById(<?php echo json_encode($container_id); ?>);
		
		var exam_id = <?php echo json_encode($id); ?>;
		var campaign_id = <?php echo json_encode($campaign_id); ?>;
		var read_only = <?php echo json_encode($read_only);?>;
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
			//Reset the questions and parts ids as -1
			for(var i = 0; i < subject.parts.length; i++){
				for(var j = 0; j < subject.parts[i].questions.length; j++)
					subject.parts[i].questions[j].id = -1;
				subject.parts[i].id = -1;
			}
			//Reset the name
			subject.name = "New Exam";
		} else
			subject = <?php echo PNApplication::$instance->selection->get_json_exam_subject_data($id);?>;
		
		//init
		if(!rights.read)
			error_dialog("You are not allowed to access to the exam subjects informations");
		else if((exam_id == -1 || exam_id == "-1") && !rights.add){
			error_dialog("You are not allowed to add an exam subject");
			if(pop_error)
				error_dialog(restriction_text);
		} else {
			if(pop_error)
				error_dialog(restriction_text);
			require("manage_exam_subject.js",function(){
				var current_campaign_id = <?php echo $current_campaign;?>;
				new manage_exam_subject(subject,
										container,
										rights.edit,
										rights.remove,
										rights.add,
										config[index_correct_answer].value,
										config[index_choices].value,
										false,
										current_campaign_id,
										read_only
										);
			});
		}
	}
</script>

<?php
}
?>