<?php
require_once("/../selection_page.inc");
require_once("component/selection/SelectionJSON.inc");
class page_exam_main_page extends selection_page {
	
	public function get_required_rights() {return array("see_exam_subject");}
	
	public function execute_selection_page() {
		$this->require_javascript("header_bar.js");
		$this->require_javascript("vertical_layout.js");
		$this->onload("new vertical_layout('container');");
		$this->onload("new header_bar('page_header','toolbar');");	
		
		//Rights based on the steps
		$can_see_subject = PNApplication::$instance->user_management->has_right("see_exam_subject",true);
		$can_manage_subject = PNApplication::$instance->selection->canUpdateFromRightAndStep("manage_exam", "manage_exam_subject", "");
		?>
		<div id = "container" style = "width:100%; height:100%">
			<div id = "page_header" icon = "/static/selection/exam/exam_16.png" title = "Entrance Examinations">
				<a class = "button_verysoft" href = "/dynamic/selection/page/selection_main_page"><img src = "<?php echo theme::$icons_16['back'];?>"/> Back to selection</a>
				<a class = "button_verysoft" href = "/dynamic/selection/page/exam/center_main_page"><img src = "/static/selection/exam/exam_center_16.png" style = "vertical-align:bottom;"/> Exam centers</a>				
				<a class = "button_verysoft" href = "/dynamic/selection/page/exam/sessions">Exam Sessions</a>
				<a class = "button_verysoft" href = "/dynamic/selection/page/exam/results">Exam Results</a>
			</div>
			<div id = "page_content" style = "overflow:auto" layout = "fill">
				<div id = "exam_content"></div>
				<div id = "eligibility_rules_content"></div>
			</div>
		</div>
		<a href = "/dynamic/selection/page/test_functionalities">test</a>
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