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
		$campaigns = PNApplication::$instance->components["selection"]->get_campaigns();
		?>
		<script type='text/javascript'>
			
			/**
			 * init_id = the id of the campaign before updating
			 */
			var init_id = null;
			
			/**
			 * campaigns = array of objects {id: ,name:} for all the campaigns already set in the database
			 */
			var campaigns = <?php echo(PNApplication::$instance->components["selection"]->get_json_campaigns().";"); ?>
			
			/**
			 * rights = object rights about selection campaign table
			 */
			var rights = {};
			rights.manage = <?php echo json_encode($rights["manage"]).";"; ?>
			rights.read = <?php echo json_encode($rights["read"]).";"; ?>
			
			/**
			 * function confirmChangeCampaign
			 * Will ask the user to confirm he wants to change the current campaign
			 * If no campaign selected (case first = 'true'), the confirmation step is skipped
			 * If the user uses 'cancel button', the selected option becomes the one selected before change (using init_id)
			 */
			function confirmChangeCampaign(id, first, select){
				if(first != "false") confirm_dialog("Are you sure you want to change the selection campaign?<br/><i>You will be redirected</i>",function(text){
					if(text == true) selectCampaign(id);
					else{
						for(var i = 0; i < select.options.length; i++){
							if(select.options[i].value == init_id) select.options[i].selected = true;
						}
					}
				});
				else selectCampaign(id);
			};
			
			/**
			 * This function calls the service set_campaign_id
			 * The variable init_id is also updated
			 */
			function selectCampaign(id){
				init_id = id;
				service.json("selection","set_campaign_id",{campaign_id:id},function(res){
					if(!res) return;
					/* Reload the page */
					location.reload();
				});
			};
			
			/**
			 * function checkCampaignName
			 * @param name {string} the name to set
			 * @return {boolean} true if the name passed the test
			 */
			function checkCampaignName(name){
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
			 * function dialogAddCampaign
			 * popup an input dialog to create a campaign
			 * After submitting, the addCampaign function is called
			 */
			function dialogAddCampaign(){
				if(!rights.manage){
					error_dialog("You are not allowed to manage the selections campaigns");
				
				} else {
					input_dialog(theme.icons_16.question,
								"Create a selection campaign",
								"Enter the name of the new selection campaign.<br/><i>You will be redirected after submitting</i>",
								'',
								50,
								function(text){
									if(!text.checkVisible()) return "You must enter at least one visible caracter";
									else {
										if(!checkCampaignName(text)) return "A campaign is already set as " + text.uniformFirstLetterCapitalized();
										else return;
									}
								},
								function(text){
									if(text){
										var div_locker = lock_screen();
										addCampaign(text.uniformFirstLetterCapitalized(), div_locker);
									}
								}
					);
				}
			}
			
			/**
			 * function addCampaign
			 * calls the service createCampaign and then reload the page
			 */
			function addCampaign(name,div_locker){
				service.json("selection","create_campaign",{name:name},function(res){
					unlock_screen(div_locker);
					if(!res) return;
					location.reload();
				});
			}
			
		</script>
		<div id='selection_page'
			icon='/static/selection/selection_32.png' 
			title='Selection'
			page='/dynamic/selection/page/selection_main_page'>
			<?php
			if($rights["manage"]) echo "<span class = 'button' onclick = 'dialogAddCampaign();'><img style = \"vertical-align:'bottom'\"src = '/static/theme/default/icons_16/add.png'></img> New campaign</span>";
			if($rights["read"]){
				echo "Campaign <select onchange = \"";
				echo "var first =";  
				$current = PNApplication::$instance->components["selection"]->get_campaign_id();
				$first = ($current <> null) ? "false" : "true";
				echo $first.";";
				echo "confirmChangeCampaign(this.options[this.selectedIndex].value,first,this);\">";
				echo "<option value = \"\"></option>";
			
				if(isset($campaigns[0]["id"])){
					foreach($campaigns as $campaign){
						$selected = ($current == $campaign["id"]) ? true : false;
						if($selected) echo "<script type='text/javascript'> init_id = '".$campaign["id"]."';</script>";
						$selected_to_echo = ($selected == true) ? "selected='selected'" : null;
						echo "<option value = '".$campaign["id"]."'".$selected_to_echo.">".$campaign["name"]."</option>";
					}
				}
			}
			?>
			
			</select>
		<?php
		/* All the other buttons need the campaign id to be set */
		$campaign_id = PNApplication::$instance->selection->get_campaign_id();
		if($campaign_id <> null){
			if($rights["manage"]){
				echo "<a class = 'button' href='/dynamic/selection/page/manage_config' target='selection_page_content'><img src = '/static/theme/default/icons_16/config.png' /> Configuration</a>";
			}
			if($rights["read"]) echo "<a class = 'button' href='/dynamic/selection/page/IS_home' target='selection_page_content'><img src='/static/selection/IS_16.png'/> Information Sessions</a>";
			if(PNApplication::$instance->user_management->has_right("see_exam_subject",true))
				echo "<span onclick = 'new examMenu(this);'class = 'button'><img src = '/static/selection/exam_16.png'> Exams</span>";
			?>
			<script type = "text/javascript">
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
							t.menu.addIconItem(theme.icons_16.add, 'Create', function() {location.assign("/dynamic/selection/page/create_exam_subject");});
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
							location.assign("/dynamic/selection/page/exam_subject?id="+item.id+"&readonly=true");
						});
						temp_menu.addIconItem('/static/data_model/excel_16.png', 'Export to Excel 2007 (.xlsx)', function() { t._export_subject('excel2007',false,item.id); });
						temp_menu.addIconItem('/static/data_model/excel_16.png', 'Export to Excel 5 (.xls)', function() { t._export_subject('excel5',false,item.id); });
						temp_menu.addIconItem('/static/selection/sunvote_16.png', 'Export to SunVote ETS compatible format', function() { t._export_subject('excel2007',true,item.id); });
						temp_menu.showBelowElement(item);
					}
					
					t._export_subject = function(format,compatible_clickers,exam_id){
						var form = document.createElement('form');
						form.action = "/dynamic/selection/service/export_exam_subject";
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
			<?php
			// get the steps
			$steps = selection::getSteps();
			if($steps["manage_exam"]){
				if(PNApplication::$instance->user_management->has_right("see_exam_subject",true))
				echo "<span onclick = 'new topicMenu(this);'class = 'button'><img src = '/static/selection/rules_16.png'> Eligibility Rules</span>";
			}
			?>
			<script type = "text/javascript">
				function topicMenu(button){
					var t = this;
					t.can_see = <?php echo json_encode(PNApplication::$instance->user_management->has_right("see_exam_subject",true));?>;
					t.can_manage = <?php echo json_encode(PNApplication::$instance->user_management->has_right("manage_exam_subject",true));?>;
					t._init = function(){
						if(t.can_see){
							t.menu = new context_menu();
							t.menu.addTitleItem("","Eligibility Rules");
							t._setMenu();
						}
						t.menu.showBelowElement("button");
					}
					
					t._setMenu = function(){
						if(t.can_manage){
							t.menu.addIconItem(theme.icons_16.add, 'Create', function() {});
						}
						/* check the step */
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
		</div>
		<?php
		}
	}

}
?>