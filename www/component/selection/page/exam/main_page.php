<?php

require_once("/../selection_page.inc");
require_once("component/selection/SelectionJSON.inc");
class page_exam_main_page extends selection_page {
	
	public function get_required_rights() {return array("see_exam_subject");}
	
	public function execute_selection_page(&$page) {
		$page->require_javascript("header_bar.js");
		$page->require_javascript("vertical_layout.js");
		$page->onload("new vertical_layout('container');");
		$page->onload("new header_bar('page_header','toolbar');");
		
		//Rights based on the steps
		$can_see_subject = PNApplication::$instance->user_management->has_right("see_exam_subject",true);
		$can_manage_subject = PNApplication::$instance->selection->canUpdateFromRightAndStep("manage_exam", "manage_exam_subject", "");
		?>
		<div id = "container" style = "width:100%; height:100%">
			<div id = "page_header" icon = "/static/selection/exam/exam_16.png" title = "Entrance Examinations">
				<div class = "button_verysoft" onclick = "location.assign('/dynamic/selection/page/selection_main_page');"><img src = "<?php echo theme::$icons_16['back'];?>"/> Back to selection</div>
				<div class = "button_verysoft" onclick = "location.assign('/dynamic/selection/page/exam/sessions');">Exam Sessions</div>
				<div class = "button_verysoft" onclick = "location.assign('/dynamic/selection/page/exam/results');">Exam Results</div>
			</div>
			<div id = "page_content" style = "overflow:auto" layout = "fill">
				<div id = "exam_content"></div>
				<div id = "eligibility_rules_content"></div>
			</div>
		</div>
		<script type = "text/javascript">
			require(["exam_subject_main_page.js","eligibility_rules_main_page.js"],function(){
				var can_see_subject = <?php echo json_encode($can_see_subject);?>;
				var can_manage_subject = <?php echo json_encode($can_manage_subject);?>;
				
				var all_exams = <?php $exams = PNApplication::$instance->selection->getAllExamSubjects();
						echo "[";
						$first = true;
						foreach($exams as $e){
							if(!$first)
								echo ", ";
							$first = false;
							echo "{name:".json_encode($e["name"]).", id:".json_encode($e["id"])."}";
						}
						echo "];";
					?>
				var all_topics = <?php echo SelectionJSON::getJsonAllTopics();?>;
				//Get all the eligibility rules objects
				//var all_rules = <?php echo SelectionJSON::getJSONAllEligibilityRules();?>;
				var topics_valid = <?php echo json_encode(PNApplication::$instance->selection->validateAllTopicsForEligibilityRules());?>;
				new exam_subject_main_page("exam_content",can_see_subject,can_manage_subject,all_exams);
				new eligibility_rules_main_page("eligibility_rules_content",can_see_subject,can_manage_subject,all_topics,topics_valid);
				});
		</script>
		<?php
	}
}
?>