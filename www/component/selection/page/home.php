<?php
class page_home extends Page {

	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('selection_page');");

		?>
		<script type='text/javascript'>
			/**
			 * init_id = the id of the campaign before updating
			 */
			var init_id = null;
			
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
			 * function dialogAddCampaign
			 * popup an input dialog to create a campaign
			 * After submitting, the addCampaign function is called
			 */
			function dialogAddCampaign(){
				input_dialog(theme.icons_16.question,
							"Create a selection campaign",
							"Enter the name of the new selection campaign.<br/><i>You will be redirected after submitting</i>",
							'',
							50,
							function(text){
								if(text.checkVisible()) return;
								else return "You must enter at least one visible caracter";
							},
							function(text){
								if(text) addCampaign(text.uniformFirstLetterCapitalized());
							}
				);
			}
			
			/**
			 * function addCampaign
			 * calls the service createCampaign and then reload the page
			 */
			function addCampaign(name){
				service.json("selection","create_campaign",{name:name},function(res){
					if(!res) return;
					location.reload();
				});
			}
		</script>
		<div id='selection_page'
			icon='/static/selection/selection_32.png' 
			title='Selection'
			page='test_selection'>
			<span class = "button" onclick = "dialogAddCampaign();"> New campaign
			<img style = "vertical-align:'bottom'"src = "/static/theme/default/icons_16/add.png"></img>
			</span>
			
			Campaign <select onchange = "
				var first = <?php 
								$current = PNApplication::$instance->components["selection"]->get_campaign_id();
								$first = ($current <> null) ? "false" : "true";
								echo $first.";";?>
				confirmChangeCampaign(this.options[this.selectedIndex].value,first,this);">
			<?php
			
			$campaigns = SQLQuery::create()->select("SelectionCampaign")->field("id")->field("name")->order_by("name")->execute();
			if(isset($campaigns[0]["id"])){
				foreach($campaigns as $campaign){
					$selected = ($current == $campaign["id"]) ? "true" : "false";
					if($selected) echo "<script type='text/javascript'> init_id = '".$campaign["id"]."';</script>";
					echo "<option value = '".$campaign["id"]."' selected = '".$selected."'>".$campaign["name"]."</option>";
				}
			}
			?>
			
			</select>
		</div>
		
		<?php
		//TODO add campaign
		//TODO if campaign_id unset
	}

}
?>