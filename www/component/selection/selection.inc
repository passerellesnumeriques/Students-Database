<?php
require_once("component/contact/OrganizationPlugin.inc");
class selection extends Component implements OrganizationPlugin {
	
	private $campaign_id = null;
	
	/**
	 * When set, config is an array [["name"=>, "value"],[...]]
	 */
	private $config = null;
	
	public function init_request() {
		if ($this->campaign_id == null) {
			if (isset($_COOKIE["selection_campaign"])) {
				if (SQLQuery::create()->select("SelectionCampaign")->where("id",$_COOKIE["selection_campaign"])->execute_single_row() <> null)
					$this->campaign_id = $_COOKIE["selection_campaign"];
			}
		}
		if ($this->campaign_id <> null)
			SQLQuery::set_submodel("SelectionCampaign", $this->campaign_id);
	}
	
	public function get_campaign_id(){
		return $this->campaign_id;
	}
	
	public function set_campaign_id($id){
		$this->campaign_id = $id;
		/* Reset the config */
		$this->config = null;
		setcookie("selection_campaign",$id,time()+365*24*60*60,"/dynamic/selection/");
	}
	
	public function get_config(){
		if($this->config <> null) return $this->config;
		else{
			/* Check if it is set in the database */
			$q = SQLQuery::create()
				->select("Selection_campaign_config")
				->field("name")
				->field("value");
			$conf = $q->execute();
			$this->config = array();
			if(isset($conf[0]["name"])){
			/* Set: update the config attribute */
				$i = 0;
				foreach($conf as $c){
					$this->config[$i] = array("name" => $c["name"],"value" => $c["value"]);
					$i++;
				}
				return $this->config;
			} else { /* Unset: set the default values */
				/* Get all the possible configs */
				$all_configs = include("config.inc");
				$j = 0;
				foreach($all_configs as $all_config){
					if($all_config[2] <> null){
						$this->config[$j] = array("name" => $all_config[0], "value" => $all_config[2]);
						$j++;
					}
				}
				return $this->config;
			}
		}
	}
	
	public function getOrganizationCreator() {
		return "Selection";
	}
	public function canReadOrganization() {
		return PNApplication::$instance->user_management->has_right("can_access_selection_data",true);
	}
	public function canWriteOrganization() {
		// TODO put the correct right
		return PNApplication::$instance->user_management->has_right("manage_selection_campaign",true);
	}
	public function canInsertOrganization() {
		// TODO put the correct right
		return PNApplication::$instance->user_management->has_right("manage_selection_campaign",true);
	}
	public function canRemoveOrganization() {
		// TODO put the correct right
		return PNApplication::$instance->user_management->has_right("manage_selection_campaign",true);
	}
	
}
?>