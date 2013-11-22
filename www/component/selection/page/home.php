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
		$rights['manage'] = PNApplication::$instance->components["user_management"]->has_right("manage_selection_campaign",true);
		$rights["read"] = PNApplication::$instance->components["user_management"]->has_right("can_access_selection_data",true);
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
			if($rights["manage"]){
				echo "<a class = 'button' href='/dynamic/selection/page/manage_config' target='selection_page_content'><img src = '/static/theme/default/icons_16/config.png' /> Configuration<span>";
			}
			if($rights["read"]) echo "<a class = 'button' href='/dynamic/selection/page/IS_home' target='selection_page_content'><img src='/static/selection/IS_16.png'/> Information Sessions<span>";
			?>
			
		</div>
		<?php
	}

}
?>