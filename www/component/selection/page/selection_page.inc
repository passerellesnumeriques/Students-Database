<?php
/**
 * This abstract class is defined in order to oblige the user to select a selection campaign
 * In case the campaign_id attribute of the selection instance is set as null, the selection page is not executed
 */
abstract class selection_page extends Page{
	//we don't define get_required_rights this way we will be obliged to define it in selection_page instances
	final public function execute(){
		$this->add_javascript("/static/selection/selection_utils.js");
		$id = PNApplication::$instance->components["selection"]->getCampaignId();
		$can_access_selection_data = null;
		$can_access_selection_data = PNApplication::$instance->components["user_management"]->has_right("can_access_selection_data",true);
		/* Check the user is allowed to read the selection data */
		if(!$can_access_selection_data) echo "<div>You are not allowed to access to the selection data</div>";
		else{
			if($id <> null) $this->execute_selection_page($this);
			else echo "<div>You must select a selection campaign</div>";
		}
	}
	
	abstract public function execute_selection_page(&$page);
}
?>