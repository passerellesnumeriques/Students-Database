<?php
/**
 * This abstract class is defined in order to oblige the user to select a selection campaign
 * In case the campaign_id attribute of the selection instance is set as null, the selection page is not executed
 */
abstract class SelectionPage extends Page{
	//we don't define getRequiredRights this way we will be obliged to define it in SelectionPage instances
	final public function execute(){
		$this->addJavascript("/static/selection/selection_utils.js");
		theme::css($this, "selection.css");
		$id = PNApplication::$instance->components["selection"]->getCampaignId();
		$can_access_selection_data = PNApplication::$instance->components["user_management"]->has_right("can_access_selection_data",true);
		/* Check the user is allowed to read the selection data */
		if(!$can_access_selection_data) echo "<div>You are not allowed to access to the selection data</div>";
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
						echo "<div style='margin:10px'>There is no selection campaign yet</div>"; 
				} else {
					?>
					<div style="margin:10px">
					Please select a selection campaign:<ul>
					<?php foreach($campaigns as $c) {
						echo "<li><a href='#' onclick=\"getIFrameWindow(findFrame('pn_application_frame')).changeCampaign(".$c["id"].");return false;\">".$c["name"]."</a></li>";
					}?>
					</ul>
					<?php if (PNApplication::$instance->user_management->has_right("manage_selection_campaign")) { ?>
					Or <a href='#' onclick="getIFrameWindow(findFrame('pn_application_frame')).createCampaign();return false;">Create a new campaign</a>
					<?php } ?>
					</div>
					<?php 
				}
			}
		}
	}
	
	abstract public function executeSelectionPage();
}
?>