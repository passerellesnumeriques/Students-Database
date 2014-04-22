<?php 
require_once("/../selection_page.inc");
require_once("component/selection/SelectionJSON.inc");

class page_eligibility_rules_manage extends selection_page {
	public function get_required_rights() { return array("see_exam_subject"); }
	public function execute_selection_page(){
		$this->require_javascript("vertical_layout.js");
		$this->onload("new vertical_layout('to_vertical_layout');");
		?>
		<div id = "to_vertical_layout" style = "width:100%; height:100%">
			<div id = "manage_rules_container" layout = "fill"></div>
			<div id = "manage_rules_footer" style = "height:10%; width:100%;"></div>
		</div>
		<?php
		/* Check the rights */
		if(!PNApplication::$instance->user_management->has_right("manage_exam_subject",true)){
			?>
			<script type = "text/javascript">
			error_dialog("You are not allowed to manage the eligibility rules");
			</script>
			<?php
		} else {
			//initialize all the rights to true because manage_exam_subject is true
			$can_add = true;
			$can_edit = true;
			$can_remove = true;
			$restricted = PNApplication::$instance->selection->updateRightsFromStepsDependenciesRestrictions("define_eligibility_rules",$can_add,$can_remove,$can_edit);
			if($restricted[0]){
				?>
				<script type = "text/javascript">
				var error_text = <?php echo json_encode($restricted[1]);?>;
				error_dialog(error_text);
				</script>
				<?php
			}
			if(!$can_edit) //Nothing can be done
				return;
			$sm = PNApplication::$instance->selection->getCampaignId();
			//Lock the DB
			$lock_eligibility_rules = $this->performRequiredLocks("EligibilityRule",null,null,$sm);
			$lock_exam_topics = $this->performRequiredLocks("ExamTopicForEligibilityRule",null,null,$sm);//required by eligibilityrule
			$lock_exam_subjects = $this->performRequiredLocks("ExamSubject",null,null,$sm);//required by examtopics
			if(PNApplication::has_errors())//locks not performed well
				return;
			
			//Get all the eligibility rules objects
			$all_rules = SelectionJSON::getJSONAllEligibilityRules();
			//Get all the topics
			$all_topics = SelectionJSON::getJsonAllTopics();
			?>
			<script type = "text/javascript">

			require("manage_rules.js",function(){
 				new manage_rules("manage_rules_container",<?php echo $all_rules;?>,<?php echo $all_topics;?>,true,<?php echo json_encode($lock_eligibility_rules);?>,"manage_rules_footer");
			});
			</script>
			<?php
		}
	}
	
}