<?php require_once("/../selection_page.inc");require_once("component/selection/SelectionJSON.inc");class page_eligibility_rules_manage_exam_topic extends selection_page {	public function get_required_rights() { return array("see_exam_subject"); }	public function execute_selection_page(&$page){		/* Check the rights */		if(!PNApplication::$instance->user_management->has_right("manage_exam_subject",true)){			?>			<script type = "text/javascript">			error_dialog("You are not allowed to manage the exam topics for eligibility rules");			</script>			<?php		} else {			//initialize all the rights to true because manage_exam_subject is true			$can_add = true;			$can_edit = true;			$can_remove = true;			$restricted = PNApplication::$instance->selection->updateRightsFromStepsDependenciesRestrictions("define_topic_for_eligibility_rules",$can_add,$can_remove,$can_edit);			if($restricted[0]){				?>				<script type = "text/javascript">				var error_text = <?php echo json_encode($restricted[1]);?>;				error_dialog(error_text);				</script>				<?php			}				$id = null;				if(isset($_GET["id"]))					$id = $_GET["id"];				else					$id = -1;				$container_id = $page->generateID();				$topic = null;				$other_topics = null;				if($id ==  -1 || $id == "-1"){					if(!$can_add || !$can_edit){ //Nothing can be done						PNApplication::error("You are not allowed to add an exam topic");						return;					}					//initialize topic object					$topic = "{id:-1,name:null,max_score:0,number_questions:0,subjects:[]}";				} else {					require_once 'component/selection/SelectionJSON.inc';					$topic = SelectionJSON::ExamTopicForEligibilityRulesFromID($id);				}				$other_topics = SelectionJSON::getJsonAllTopics($id);				$json_all_parts = SelectionJSON::getJsonAllParts();								$page->add_javascript("/static/widgets/vertical_layout.js");				$page->onload("new vertical_layout('manage_topic_container');");				?>			<div id = "manage_topic_container" style = "width:100%; height:100%">				<div id = "page_header"></div>				<div id='<?php echo $container_id; ?>' style = "overflow:auto" layout = "fill"></div>			</div>			<?php			//Get the read only attribute			$read_only = false;			if(isset($_GET["read_only"]) && $_GET["read_only"] == true)				$read_only = true;						$db_lock = array();			if(!$read_only){				//Lock the tables to have the last version of the other topics				array_push($db_lock,$page->performRequiredLocks("ExamTopicForEligibilityRule",null,null,PNApplication::$instance->selection->getCampaignId()));				//Also lock the ExamSubjectPart table				array_push($db_lock,$page->performRequiredLocks("ExamSubjectPart",null,null,PNApplication::$instance->selection->getCampaignId()));				if(in_array(null, $db_lock))//some lock couldn't be performed					return;			} else {				$db_lock[0] = null;				$db_lock[1] = null;				}						?>			<script type = "text/javascript">			require(["manage_exam_topic_for_eligibility_rules.js","page_header.js"],function(){				var can_add = <?php echo json_encode($can_add);?>;				var can_edit = <?php echo json_encode($can_edit);?>;				var can_remove = <?php echo json_encode($can_remove);?>;				var container_id = <?php echo json_encode($container_id);?>;				var topic = <?php echo $topic;?>;				var other_topics = <?php echo $other_topics;?>;				var all_parts = <?php echo $json_all_parts;?>;				var db_lock = <?php echo "[".json_encode($db_lock[0]).", ".json_encode($db_lock[1])."]";?>;				var save_button = "save_topic_button";				var remove_button = "remove_topic_button";				var header = new page_header('page_header',true);				var campaign = <?php echo json_encode(PNApplication::$instance->selection->getCampaignId());?>;				header.setTitle("<img src = '/static/selection/eligibility_rules/rules_16.png'/> Manage exam topic");				var read_only = <?php echo json_encode($read_only);?>;				new manage_exam_topic_for_eligibility_rules(topic, container_id, can_add, can_edit, can_remove, other_topics, all_parts, db_lock, header, campaign, read_only);			});			</script>			<?php		}	}	}