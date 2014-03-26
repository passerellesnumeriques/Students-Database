<?php 
class service_application_menu_builder extends Service {
	
	public function get_required_rights() { return array("can_access_selection_data"); }
	
	public function documentation() { echo "Provides JavaScript to build the application menu"; }
	public function input_documentation() { echo "No"; }
	public function output_documentation() { echo "The JavaScript that builds the menu"; }
	
	public function get_output_format($input) { return "text/javascript"; }
	
	public function execute(&$component, $input) {
		$rights = array();
		$rights["read"] = PNApplication::$instance->components["user_management"]->has_right("can_access_selection_data",true);
		$rights['manage'] = PNApplication::$instance->components["user_management"]->has_right("manage_selection_campaign",true);
		$campaigns = PNApplication::$instance->selection->getCampaigns();
		?>
		resetAllMenus();
		function selectCampaignHeader (first, can_add, campaigns, init_id, container){
			var t = this;
			/**
			 * Start the process
			 * Set the select element with the good value
			 */
			t._init = function(){
				t.select.getHTMLElement().style.backgroundColor = "#FFFFFF";
				t.select.getHTMLElement().style.marginLeft = "5px";
				t.select.add(0,"<i><center>Not selected</center></i>");
				if(can_add)
					t.select.add("add","<img style = 'vertical-align:bottom' src = '"+theme.icons_16.add+"'/> <i>Create campaign</i>");
				for(var i = 0; i < campaigns.length; i++){
					t.select.add(campaigns[i].id, "<span style='padding: 0px 2px 0px 2px'>"+campaigns[i].name+"</span>");
				}
				if(!init_id)
					init_id = 0;
				t.select.select(init_id);
					t.select.onbeforechange = t._confirmChangeCampaign;
					t.select.onchange = t._selectCampaign;
			}
			
			/**
			 * Method called onbeforechange by the select object
			 * @param {String} old_value
			 * @param {String} new_value
			 * @param {Function} fire_change, method given by select#select method when calling onbeforechange
			 */
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
				if(id != "add")
					service.json("selection","set_campaign_id",{campaign_id:id},function(res){
						if(!res) return;
						/* Reload the page */
						window.frames["pn_application_content"].location.reload();
						populateMenu("/dynamic/selection/service/application_menu_builder");
					});
		
			};
			
			/**
			 * @param {String} name the name to set
			 * @return {Boolean} true if the name passed the test
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
			 * Popup an input dialog to create a campaign
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
										var div_locker = lock_screen(null,"Creation of the new selection campaign...");
										t._addCampaign(text.uniformFirstLetterCapitalized(), div_locker);
									}
								}
					);
				}
			}
			
			/**
			 * calls the service create_campaign and then reload the page
			 * @param {String} name the new campaign name
			 * @param {String} div_locker id of the screen locker
			 */
			t._addCampaign = function (name,div_locker){
				service.json("selection","create_campaign",{name:name},function(res){
					unlock_screen(div_locker);
					if(!res) return;
					window.frames["pn_application_content"].location.assign("/dynamic/selection/page/selection_main_page");
					populateMenu("/dynamic/selection/service/application_menu_builder");
				});
			}
			
			require("select.js",function(){
				t.select = new select(container);
				t._init();
			});
		}
		<?php
		if($rights["read"]){ 
			$current = PNApplication::$instance->components["selection"]->getCampaignId();
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
			echo "var select_element = document.createElement('div');";
			echo "var select_campaign = new selectCampaignHeader(".$first.", ".json_encode($rights["manage"]).", ".$json_all_campaign.", ".json_encode($current).", select_element);";
			echo "addLeftControl(select_element);";
		
			/* All the other buttons need the campaign id to be set */
			$campaign_id = PNApplication::$instance->selection->getCampaignId();
			if($campaign_id <> null){
				echo "addMenuItem('".theme::$icons_16["dashboard"]."','Dashboard',null,'/dynamic/selection/page/selection_main_page');";
				echo "addRightControl(\"<a class='button_verysoft' href='/dynamic/selection/page/organizations_for_selection' target='pn_application_content'><img src='/static/contact/directory_32.png'/></a>\",'See organizations partners for the selection process');";
				if($rights["manage"]){
					echo "addMenuItem('".theme::$icons_16["config"]."','Configuration','Configure how your selection process works and how the application will behave','/dynamic/selection/page/config/manage');";
				}
				if(PNApplication::$instance->user_management->has_right("see_information_session_details",true))
					echo "addMenuItem('/static/selection/IS/IS_16.png','Information Sessions','List information sessions already planned, create new ones','/dynamic/selection/page/IS/main_page');";
				if(PNApplication::$instance->user_management->has_right("see_exam_subject",true))
					echo "addMenuItem('/static/selection/exam/exam_16.png','Exams','Written exam subjects, eligibility rules, exam sessions planned, import results','/dynamic/selection/page/exam/main_page');";
				echo "addMenuItem('/static/curriculum/batch_16.png','Applicants','Applicants lists, import applicants, create applicants','/dynamic/selection/page/applicant/main_page');";
			}
		}
	}
	
}
?>