<?php 
require_once("/../selection_page.inc");
require_once("component/selection/SelectionJSON.inc");

class page_eligibility_rules_manage extends selection_page {
	public function get_required_rights() { return array("see_exam_subject"); }
	public function execute_selection_page(&$page){
		?>
		<div id = "test" style = "height:100%; width:100%;"></div>
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
			$lock_eligibility_rules = $page->performRequiredLocks("EligibilityRule",null,null,$sm);
// 			$lock_exam_topics = $page->performRequiredLocks("ExamTopicForEligibilityRule",null,null,$sm);//required by eligibilityrule
// 			$lock_exam_subjects = $page->performRequiredLocks("ExamSubject",null,null,$sm);//required by examtopics
			if(PNApplication::has_errors())//locks not performed well
				return;
			
			//Get all the eligibility rules objects
			$all_rules = SelectionJSON::getJSONAllEligibilityRules();
			//Get all the topics
			$all_topics = SelectionJSON::getJsonAllTopics();
			?>
			<script type = "text/javascript">
// 			require("diagram_display_manager.js",function(){
// 				var d = new diagram_display_manager("test");
// 				d.createStartNode("title","coucou","title");
// 				d.createEndNode("<img src = '"+theme.icons_16.left+"'/> fin","c est la ","end");
// 				d.createChildNode('test','test number 1 avec très bcp tttttttttttttttttt ttttttttttttttttttttt tttttttttttttttttttttt',"test1");
// 				d.createChildNode('test','test number 222222222222222222',"test2");
// 				d.createChildNode('test','test number 222222222222222222',"test3");
// 				d.createChildNode('test','test number 222222222222222222',"test4");
// 				d.show();
// 			});

// 			require("manage_rule.js",function(){
// 				var rule = {topics:[{coefficient:null,expected:null,topic:{id:1,name:"Toto",max_score:10}},{coefficient:2,expected:10,topic:{id:2,name:"Titi",max_score:10}}]};
// 				var all = [{id:1,name:"Toto",max_score:10},{id:2,name:"Toto",max_score:10},{id:3,name:"Toto",max_score:10}];
// 				new manage_rule("test",rule,all,true);
// 			});

			require("manage_rules.js",function(){
				var rules = [{id:1, parent:null, topics:[{coefficient:null,expected:null,topic:{id:1,name:"Math and logic",max_score:10}},{coefficient:2,expected:2,topic:{id:2,name:"Speed and accuracy",max_score:10}}]},
				             {id:2, parent:"root", topics:[{coefficient:2,expected:null,topic:{id:1,name:"Math and logic",max_score:10}},{coefficient:2,expected:3,topic:{id:2,name:"Speed and accuracy",max_score:10}}]},
				             {id:3, parent:"root", topics:[{coefficient:2,expected:null,topic:{id:1,name:"Math and logic",max_score:10}},{coefficient:2,expected:3,topic:{id:2,name:"Speed and accuracy",max_score:10}}]},
				             {id:4, parent:"root", topics:[{coefficient:2,expected:null,topic:{id:1,name:"Math and logic",max_score:10}},{coefficient:2,expected:3,topic:{id:2,name:"Speed and accuracy",max_score:10}}]}
							];
				rules = [];
 				var all_topics = [{id:1,name:"Math and logic",max_score:10},{id:2,name:"Speed and accuracy",max_score:10},{id:3,name:"English",max_score:10}];
 				new manage_rules("test",rules,all_topics,true,<?php echo json_encode($lock_eligibility_rules);?>);
			});
			</script>
			<?php
		}
	}
	
}