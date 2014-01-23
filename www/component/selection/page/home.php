<?php
/**
 * This page is the only selection page which is not an extension of the selection_page class,
 * because this page is used to select the selection campaign
 */
class page_home extends Page {

	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('selection_page');");
		$rights = array();
		$rights["read"] = PNApplication::$instance->components["user_management"]->has_right("can_access_selection_data",true);
		$rights['manage'] = PNApplication::$instance->components["user_management"]->has_right("manage_selection_campaign",true);
		$campaigns = PNApplication::$instance->selection->get_campaigns();
		?>
		<script type='text/javascript'>
			function selectCampaignHeader (first, can_add, campaigns, init_id){
				var t = this;
				
				t._init = function(){
					t.select.add(0,"<i><center>Not selected</center></i>");
					if(can_add)
						t.select.add("add","<img style = 'vertical-align:bottom' src = '"+theme.icons_16.add+"'/> <i>Create campaign</i>");
					for(var i = 0; i < campaigns.length; i++){
						t.select.add(campaigns[i].id, campaigns[i].name);
					}
					if(!init_id)
						init_id = 0;
						
					t.select.select(init_id);
						t.select.onbeforechange = t._confirmChangeCampaign;
						t.select.onchange = t._selectCampaign;
				}
				
				
				t._confirmChangeCampaign = function (old_value, new_value, fire_change){
					if(new_value == "add"){
						t._dialogAddCampaign();
					} else {
						if(first)
							fire_change();
						else if(!first) confirm_dialog("Are you sure you want to change the selection campaign?<br/><i>You will be redirected</i>",function(res){
								if(res == true) fire_change();
							});
					}
				};
				
				/**
				 * This function calls the service set_campaign_id
				 */
				t._selectCampaign = function (){
					id = t.select.getSelectedValue();
					if(id != 0 && id != "add")
						service.json("selection","set_campaign_id",{campaign_id:id},function(res){
							if(!res) return;
							/* Reload the page */
							location.reload();
						});
				};
				
				/**
				 * @method _checkCampaignName
				 * @param name {string} the name to set
				 * @return {boolean} true if the name passed the test
				 */
				t._checkCampaignName = function (name){
					var is_unique = true;
					for(var i = 0; i < campaigns.length; i++){
						if(campaigns[i].name.toLowerCase() == name.toLowerCase()){
							is_unique = false;
							break;
						}
					}
					return is_unique;
				}
				 
				/**
				 * function _dialogAddCampaign
				 * popup an input dialog to create a campaign
				 * After submitting, the _addCampaign function is called
				 */
				t._dialogAddCampaign = function (){
					if(!can_add){
						error_dialog("You are not allowed to manage the selections campaigns");
						// t._resetSelectOption();
					} else {
						input_dialog(theme.icons_16.question,
									"Create a selection campaign",
									"Enter the name of the new selection campaign.<br/><i>You will be redirected after submitting</i>",
									'',
									50,
									function(text){
										if(!text.checkVisible()) return "You must enter at least one visible caracter";
										else {
											if(!t._checkCampaignName(text)) return "A campaign is already set as " + text.uniformFirstLetterCapitalized();
											else return;
										}
									},
									function(text){
										if(text){
											var div_locker = lock_screen();
											t._addCampaign(text.uniformFirstLetterCapitalized(), div_locker);
										}
									}
						);
					}
				}
				
				/**
				 * function _addCampaign
				 * calls the service createCampaign and then reload the page
				 */
				t._addCampaign = function (name,div_locker){
					service.json("selection","create_campaign",{name:name},function(res){
						unlock_screen(div_locker);
						if(!res) return;
						location.reload();
					});
				}
				
				require("select.js",function(){
					t.select = new select("select_campaign_header");
					t._init();
				});
			}
			
		</script>
		<div id='selection_page'
			icon='/static/selection/selection_32.png' 
			title='Selection'
			page='/dynamic/selection/page/selection_main_page'>
			<div class = "button" onclick = "location.assign('/dynamic/selection/page/home');"><img src = '<?php echo theme::$icons_16["home"];?>'/> Home</div>
			<?php
			if($rights["read"]){
				echo "<div style = 'vertical-align:bottom' id ='select_campaign_header'></div>"; 
				$current = PNApplication::$instance->components["selection"]->get_campaign_id();
				$first = ($current <> null) ? "false" : "true";
				$json_all_campaign = "[";
				if(isset($campaigns[0]["id"])){
					$first_camp = true;
					foreach($campaigns as $campaign){
						if(!$first_camp)
							$json_all_campaign .=  ", ";
						$first_camp = false;
						$json_all_campaign .=  "{id:".json_encode($campaign['id']).", name:".json_encode($campaign["name"])."}";
					}
				}
				$json_all_campaign .= "]";
				$this->onload("new selectCampaignHeader(".$first.", ".json_encode($rights["manage"]).", ".$json_all_campaign.", ".json_encode($current).");");
			}
			?>
			
		<?php
		/* All the other buttons need the campaign id to be set */
		$campaign_id = PNApplication::$instance->selection->get_campaign_id();
		if($campaign_id <> null){
			if($rights["manage"]){
				echo "<div class = 'button' onclick=\"window.frames['selection_page_content'].location.href='/dynamic/selection/page/config/manage'\"><img src = '/static/theme/default/icons_16/config.png' /> Configuration</div>";
			}
			if($rights["read"]) echo "<div class = 'button' onclick=\"window.frames['selection_page_content'].location.href='/dynamic/selection/page/IS/main_page'\"><img src='/static/selection/IS/IS_16.png'/> Information Sessions</div>";
			if(PNApplication::$instance->user_management->has_right("see_exam_subject",true))
				// echo "<span onclick = 'new examMenu(this);'class = 'button'><img src = '/static/selection/exam_subject/exam_16.png'> Exams</span>";
				echo "<span onclick = \"window.frames['selection_page_content'].location.href='/dynamic/selection/page/exam/main_page'\" class = 'button'><img src = '/static/selection/exam/exam_16.png'/> Exams</span>";

			// get the steps
			$steps = selection::getSteps();
			// if($steps["manage_exam"]){
				// if(PNApplication::$instance->user_management->has_right("see_exam_subject",true))
			// }
			?>
		</div>
		<script type = "text/javascript">
			var select_campaign = document.getElementById("select_campaign");
			var steps = <?php echo PNApplication::$instance->selection->getJsonSteps();?>;
			function examMenu(button){		
				var t = this;
				/* Check the rights */
				t.can_manage_exam = <?php
						$can_manage = PNApplication::$instance->selection->canManageExamSubjectQuestions();
						echo json_encode($can_manage[0]).";";?>	
				t._init = function(){
					t.menu = new context_menu();
					t.menu.removeOnClose = true;
					t.menu.addTitleItem(null, "Entrance Examination");
					if(t.can_manage_exam){																			
						t.menu.addIconItem(theme.icons_16.add, 'Create', function() {location.assign("/dynamic/selection/page/exam/create");});
					}
					/* check that any exam already exist */
					if(getStepValue(steps,"manage_exam")){
						t._addExamList();
					}
					
					t.menu.showBelowElement(button);
				}
				
				t._addExamList = function(){
					/* Add a separator */
					if(t.can_manage_exam)
						t.menu.addSeparator(); //else nothing above
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
					for(var i = 0; i < all_exams.length; i++){
						var div_exam = document.createElement("div");
						div_exam.innerHTML = all_exams[i].name;
						div_exam.className = "context_menu_item";
						div_exam.id = all_exams[i].id;							
						div_exam.onclick = function(){t._addManageExamMenu(this)};
						t.menu.addItem(div_exam,true);
					}
					
				}
				
				t._addManageExamMenu = function(item){
					var temp_menu = new context_menu();
					if(t.can_manage_exam)
						temp_menu.addIconItem(theme.icons_16.edit,"Edit subject",function(){
							location.assign("/dynamic/selection/page/exam_subject?id="+item.id);
						});
					temp_menu.addIconItem(theme.icons_16.search,"See subject",function(){
						location.assign("/dynamic/selection/page/exam_subject/exam_subject?id="+item.id+"&readonly=true");
					});
					temp_menu.addIconItem('/static/data_model/excel_16.png', 'Export to Excel 2007 (.xlsx)', function() { t._export_subject('excel2007',false,item.id); });
					temp_menu.addIconItem('/static/data_model/excel_16.png', 'Export to Excel 5 (.xls)', function() { t._export_subject('excel5',false,item.id); });
					temp_menu.addIconItem('/static/selection/exam_subject/sunvote_16.png', 'Export to SunVote ETS compatible format', function() { t._export_subject('excel2007',true,item.id); });
					temp_menu.showBelowElement(item);
				}
				
				t._export_subject = function(format,compatible_clickers,exam_id){
					var form = document.createElement('form');
					form.action = "/dynamic/selection/service/exam_subject/export";
					form.method = "POST";
					var input = document.createElement("input");
					input.type = "hidden";
					input.name = "format";
					input.value = format;
					form.appendChild(input);
					var input2 = document.createElement("input");
					input2.type = "hidden";
					input2.value = exam_id;
					input2.name = "id";
					form.appendChild(input2);
					if(compatible_clickers){
						var input3 = document.createElement("input");
						input3.type = "hidden";
						input3.value = "true";
						input3.name = "clickers";
						form.appendChild(input3);
					}
					document.body.appendChild(form);
					form.submit();
				}
				
				require(["context_menu.js","selection_utils.js"],function(){
					t._init();
				});
			}
		</script>
		<script type = "text/javascript">
			function topicMenu(button){
				var t = this;
				t.can_see = <?php echo json_encode(PNApplication::$instance->user_management->has_right("see_exam_subject",true));?>;
				<?php
				$restricted_from_steps = PNApplication::$instance->selection->getRestrictedRightsFromStepsAndUserManagement(
					"define_topic_for_eligibility_rules",
					"manage_exam_subject",
					"manage_exam_subject",
					"manage_exam_subject"
				);
				$can_add = $restricted_from_steps[0]["add"];
				$can_edit = $restricted_from_steps[0]["edit"];
				$can_remove = $restricted_from_steps[0]["remove"];
				echo "t.can_add = ".json_encode($can_add).";";
				echo "\n";
				echo "t.can_edit = ".json_encode($can_edit).";";
				echo "\n";
				echo "t.can_remove = ".json_encode($can_remove).";";
				echo "\n";
				
				?>
				t._init = function(){
					if(t.can_see){
						t.menu = new context_menu();
						t.menu.addTitleItem("","Eligibility Rules");
						t._setMenu();
					}
					t.menu.showBelowElement(button);
				}
				
				t._setMenu = function(){
					if(t.can_add){
						t.menu.addIconItem(theme.icons_16.add, 'Create', function() {});
					}
					/* check that some topics already exist */
					if(getStepValue(steps,"define_topic_for_eligibility_rules"))
						t._addTopicsList();
				}
				
				t._addTopicsList = function(){
					//TODO
					
				}
				
				require(["context_menu.js","selection_utils.js"],function(){
					t._init();
				});
			}
		</script>
		<?php
		}
	}

}
?>