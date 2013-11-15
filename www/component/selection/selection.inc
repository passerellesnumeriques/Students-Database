<?php
require_once("component/contact/OrganizationPlugin.inc");
class selection extends Component implements OrganizationPlugin {
	
	private $campaign_id = null;
	
	/**
	 * When set, config is an array [["name"=>"value"],[...]]
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
		SQLQuery::set_submodel("SelectionCampaign", $this->campaign_id);
	}
	
	public function create_campaign($name){
		$key = null;
		$fields = array();
		$fields["name"] = $name;
		$all_configs = include("config.inc");
		$key = SQLQuery::create()->insert("SelectionCampaign",$fields);
		$this->set_campaign_id($key);
		/* Insert the default config in the database */
		foreach($all_configs as $all_config){
			SQLQuery::create()->insert("Selection_campaign_config",array("name"=>$all_config[0],"value"=>json_encode($all_config[2])));
		}
		return $key;
	}
	
	public function remove_campaign($id){
		SQLQuery::create()->remove_key("SelectionCampaign",$id);
		/* Set the campaign id to null */
		$this->campaign_id = null;
	}
	
	public function rename_campaign($id, $name){
		SQLQuery::create()->update_by_key("SelectionCampaign",$id,array("name"=>$name));
	}
	
	public function get_config(){
		if($this->config <> null) {return $this->config;}
		else{
			/* Check if it is set in the database */
			try{
				$q = SQLQuery::create()
					->select("Selection_campaign_config")
					->field("name")
					->field("value");
				$conf = $q->execute();
				$this->config = array();
			} catch (Exception $e){
				PNApplication::error($e);
				$this->config = null;
				return $this->config;
			}
			
			/* The config is set by default when we create a campaign so $conf cannot be empty */
			$i = 0;
			foreach($conf as $c){
				$this->config[$i] = array("name" => $c["name"],"value" => json_decode($c["value"]));
				$i++;
			}
			return $this->config;
		}
	}
	
	
	public function get_json_campaigns(){
		try {$campaigns = SQLQuery::create()->select("SelectionCampaign")->field("id")->field("name")->order_by("name")->execute();
		} catch (Exception $e){
			return "[]";
		}
		$js = null;
		if(isset($campaigns[0]["name"])){
			$first = true;
			$js = "[";
			foreach($campaigns as $c){
				if(!$first) $js.= ", ";
				$js.= "{id:".json_encode($c["id"]).", name:".json_encode($c["name"])."}";
				$first = false;
			}
			$js.= "]";
		}
		return $js;
	}
	
	public function get_campaigns(){
		try{
			$campaigns = SQLQuery::create()->select("SelectionCampaign")->field("id")->field("name")->order_by("name")->execute();
		} catch (Exception $e){
			return array();
		}
		$camps = array();
		$i = 0;
		if(isset($campaigns[0]["name"])){
			foreach($campaigns as $c){
				$camps[$i] = array();
				$camps[$i]["id"] = $c["id"];
				$camps[$i]["name"] = $c["name"];
				$i++;
			}
		}
		return $camps;
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