<?php
/**
 * This abstract class is defined in order to oblige the user to select a selection campaign
 * In case the campaign_id attribute of the selection instance is set as null, the selection page is not executed
 */
abstract class selection_page extends Page{
	//we don't define getRequiredRights this way we will be obliged to define it in selection_page instances
	final public function execute(){
		$this->add_javascript("/static/selection/selection_utils.js");
		$id = PNApplication::$instance->components["selection"]->getCampaignId();
		$can_access_selection_data = null;
		$can_access_selection_data = PNApplication::$instance->components["user_management"]->has_right("can_access_selection_data",true);
		/* Check the user is allowed to read the selection data */
		if(!$can_access_selection_data) echo "<div>You are not allowed to access to the selection data</div>";
		else{
			if($id <> null) $this->execute_selection_page();
			else {
				$campaigns = SQLQuery::create()->select("SelectionCampaign")->execute();
				if (count($campaigns) == 0) {
					?>
					<div style="margin:10px">
					<a href='#' onclick="getIFrameWindow(findFrame('pn_application_frame')).createCampaign();return false;">Create a first selection campaign</a>
					</div>
					<?php 
				} else {
					?>
					<div style="margin:10px">
					Please select a selection campaign:<ul>
					<?php foreach($campaigns as $c) {
						echo "<li><a href='#' onclick=\"getIFrameWindow(findFrame('pn_application_frame')).changeCampaign(".$c["id"].");return false;\">".$c["name"]."</a></li>";
					}?>
					</ul>
					Or <a href='#' onclick="getIFrameWindow(findFrame('pn_application_frame')).createCampaign();return false;">Create a new campaign</a>
					</div>
					<?php 
				}
			}
		}
	}
	
	abstract public function execute_selection_page();
}
?>