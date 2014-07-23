<?php 
require_once("/../SelectionPage.inc");
class page_exam_subject extends SelectionPage {
	public function getRequiredRights() { return array("see_exam_subject"); }
	public function executeSelectionPage(){
		$id = null;
		if(!isset($_GET["id"]))
			$id = -1;
		else if($_GET["id"] == "-1")
			$id = -1;
		else
			$id = $_GET["id"];
		$campaign_id = null;
		if(isset($_GET["campaign_id"]))
			$campaign_id = $_GET["campaign_id"];
		if(isset($_GET["readonly"]))
			$read_only = $_GET["readonly"];
		else
			$read_only = false;
		
		echo "<div style='background-color:white;height:100%'><div id = 'exam_subject_container' style='background-color:white;'></div></div>";

		require_once("component/data_model/Model.inc");
		require_once("component/selection/SelectionJSON.inc");
		if(!PNApplication::$instance->user_management->has_right("see_exam_subject",true))
			return;
		//Get rights from steps
		$from_steps = PNApplication::$instance->selection->getRestrictedRightsFromStepsAndUserManagement("manage_exam", "manage_exam_subject", "manage_exam_subject", "manage_exam_subject");
		if($from_steps[1])
			PNApplication::warning($from_steps[2]);
		$can_add = $from_steps[0]["add"];
		$can_remove = $from_steps[0]["remove"];
		$can_edit = $from_steps[0]["edit"];
	
		$config = PNApplication::$instance->selection->getConfig();
	
		$current_campaign = PNApplication::$instance->selection->getCampaignId();
	
		$fct_name = "exam_subject_".$this->generateID();
	
		$db_lock = null;
		if(!$read_only && $id != -1 && $id != "-1"){
			$locked_by = null;
			$db_lock = $this->performRequiredLocks("ExamSubject",$id,null,$current_campaign, $locked_by);
			if($db_lock == null)
				return;
		}
		
		$this->requireJavascript("exam_objects.js");
		?>
		<script type = "text/javascript">
			var subject = null;
			var can_edit = <?php echo json_encode($can_edit);?>;
			var can_remove = <?php echo json_encode($can_remove);?>;
			var can_add = <?php echo json_encode($can_add);?>;
			
			var config = null;
			<?php if($config <> null) echo "config = ".json_encode($config).";";?>
			var index_correct_answer = findIndexInConfig(config,"set_correct_answer");
			var index_versions = findIndexInConfig(config,"exam_multiple_versions");
			
			var container = document.getElementById("exam_subject_container");
			
			var exam_id = <?php echo json_encode($id); ?>;
			var campaign_id = <?php echo json_encode($campaign_id); ?>;
			var read_only = <?php echo json_encode($read_only);?>;
			var db_lock = <?php echo json_encode($db_lock);?>;
			if((exam_id == -1 || exam_id == "-1")){
				subject = new ExamSubject(-1,"New Exam",0,[]);
			} else if(typeof(campaign_id) == "string") {
				//create an exam subject from an existing one
				<?php
					if(isset($campaign_id)){
						SQLQuery::setSubModel("SelectionCampaign", $campaign_id);
	// 					
						echo "subject = ".SelectionJSON::ExamSubjectFromID($id).";";
						//reset the current campaign submodel
						SQLQuery::setSubModel("SelectionCampaign", $current_campaign);
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
				//subject.name = "New Exam";
			} else
				subject = <?php echo SelectionJSON::ExamSubjectFromID($id);?>;
			
			//init
			if((exam_id == -1 || exam_id == "-1") && !can_add){
				error_dialog("You are not allowed to add an exam subject");
			} else {
				require("manage_exam_subject.js",function(){
					var current_campaign_id = <?php echo $current_campaign;?>;
					window.exam_subject_manager = new manage_exam_subject(subject,
											container,
											can_edit,
											can_remove,
											can_add,
											config[index_correct_answer].value,
											config[index_versions].value,
											false,
											current_campaign_id,
											read_only,
											db_lock
											);
				});
			}
		</script>
		<?php
	}
}

