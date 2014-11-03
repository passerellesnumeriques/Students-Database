<?php
/**
 * This abstract class is defined in order to oblige the user to select a selection campaign
 * In case the campaign_id attribute of the selection instance is set as null, the selection page is not executed
 */
abstract class SelectionPage extends Page{
	//we don't define getRequiredRights this way we will be obliged to define it in SelectionPage instances
	final public function execute(){
#DEV
		$this->addJavascript("/static/selection/selection_utils.js");
		theme::css($this, "selection.css");
		$id = PNApplication::$instance->components["selection"]->getCampaignId();
		$can_access_selection_data = PNApplication::$instance->components["user_management"]->has_right("can_access_selection_data",true);
		/* Check the user is allowed to read the selection data */
		if(!$can_access_selection_data) echo "<div style='padding:5px'><div class='error_box'><img src='".theme::$icons_16["error"]."' style='vertical-align:bottom'/> You are not allowed to access to the selection data</div></div>";
		else{
			if($id <> null) $this->executeSelectionPage();
			else {
				$campaigns = SQLQuery::create()->select("SelectionCampaign")->execute();
				if (count($campaigns) == 0) {
					if (PNApplication::$instance->user_management->has_right("manage_selection_campaign")) {
					?>
					<div style="margin:10px">
					<button class='action' onclick="getIFrameWindow(findFrame('pn_application_frame')).createCampaign();return false;">Create a first selection campaign</button>
					</div>
					<?php
					} else
						echo "<div style='padding:5px'><div class='info_box'><img src='".theme::$icons_16["info"]."' style='vertical-align:bottom'/> There is no selection campaign yet</div></div>"; 
				} else {
					theme::css($this, "section.css");
					?>
					<div style='width:100%;height:100%;overflow:auto'>
						<div style='min-height:100%;display:flex;flex-direction:row;align-items:center;justify-content:center;'>
							<div class='section' style='background-color:white;border:1px solid #A0A0A0;margin:5px;'>
								<div style='padding:5px;'>
									<div style='font-weight:bold'>
										<img src='<?php echo theme::$icons_16["question"];?>' style='vertical-align:bottom'/>
										Please select a selection campaign:
									</div>
									<ul style='margin-top:5px'>
										<?php foreach($campaigns as $c) {
											echo "<li><a class='black_link' href='#' onclick=\"getIFrameWindow(findFrame('pn_application_frame')).changeCampaign(".$c["id"].");return false;\">".$c["name"]."</a></li>";
										}?>
									</ul>
								</div>
								<?php if (PNApplication::$instance->user_management->has_right("manage_selection_campaign")) { ?>
								<div class='footer'>
								Or <button class='action green' onclick="getIFrameWindow(findFrame('pn_application_frame')).createCampaign();return false;">Create a new campaign</button>
								</div>
								<?php } ?>
							</div>
						</div>
					</div>
					<?php 
				}
			}
		}
#END
#PROD
#echo "Selection is still under development. This section is disabled for production.";
#END
	}
	
	abstract public function executeSelectionPage();
}
?>