<?php 
require_once("/../selection_page.inc");
require_once("component/selection/SelectionJSON.inc");

class page_eligibility_rules_manage extends selection_page {
	public function get_required_rights() { return array("see_exam_subject"); }
	public function execute_selection_page(&$page){
		?>
		<div id = "test"></div>
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
			
			//Lock the DB
			//TODO
			
			//Get all the eligibility rules objects
			$all_rules = SelectionJSON::getJSONAllEligibilityRules();
			//Get all the topics
			$all_topics = SelectionJSON::getJsonAllTopics();
			?>
			<script type = "text/javascript">
			require("diagram_display_manager.js",function(){
				var d = new diagram_display_manager("test");
				d.createStartNode("title","coucou","title");
				d.createEndNode("<img src = '"+theme.icons_16.left+"'/> fin","c est la fin","end");
				d.createChildNode('test','test number 1 avec très bcp de texte pour que ça soit grosrbzebgzgegefgerggergggggggggggggg',"test1");
				d.createChildNode('test','test number 222222222222222222',"test2");
				d.createChildNode('test','test number 222222222222222222',"test2");
				d.show();
			});
			</script>
			<?php
		}
	}
	
}