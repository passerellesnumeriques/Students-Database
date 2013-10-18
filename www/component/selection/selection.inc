<?php
class selection extends Component{
	
	private $campaign_id;
	
	/**
	 * When set, config is an array [["name"=>, "value"],[...]]
	 */
	private $config = null;
	
	public function init_request() {
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
				$all_configs = require_once("config.inc");
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
	
}
?>