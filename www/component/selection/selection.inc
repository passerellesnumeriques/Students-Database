<?php
require_once("component/contact/OrganizationPlugin.inc");
class selection extends Component implements OrganizationPlugin {
	
	private $campaign_id = null;
	
	/* A calendar is linked to the selection campaign */
	private $calendar_id = null;
	
	/**
	 * When set, config is an array [["name"=>"value"],[...]]
	 */
	private $config = null;
	
	/**
	 * @method init_request called each time the selection component executes a database query
	 * Check if a cookie selection_campaign exists; if yes, set the campaign_id attribute to its value
	 * If the campaign_id is set, the matching submodels is selected in the database
	 */
	public function init_request() {
		if ($this->campaign_id == null) {
			if (isset($_COOKIE["selection_campaign"])) {
				try {
					if (SQLQuery::create()->select("SelectionCampaign")->where("id",$_COOKIE["selection_campaign"])->execute_single_row() <> null){
						$this->set_campaign_id($_COOKIE["selection_campaign"]);
					}
				} catch (Exception $e) {}
			}
		}
		if ($this->campaign_id <> null)
			SQLQuery::set_submodel("SelectionCampaign", $this->campaign_id);
	}
	
	/**
	 * @method get_campaign_id
	 * @return attribute campaign_id
	 */
	public function get_campaign_id(){
		return $this->campaign_id;
	}
	
	/**
	 * @method get_calendar_id
	 * @return attribute calendar_id
	 */
	public function get_calendar_id(){
		return $this->calendar_id;
	}
	
	/**
	 * @method set_campaign_id
	 * @param $id
	 * Set the campaign_id attribute to $id, reset the config attribute, set the selection_campaign cookie,
	 * update the calendar_id attribute, and select the matching submodel
	 */
	public function set_campaign_id($id){
		$this->campaign_id = $id;
		/* Reset the config */
		$this->config = null;
		setcookie("selection_campaign",$id,time()+365*24*60*60,"/dynamic/selection/");
		SQLQuery::set_submodel("SelectionCampaign", $this->campaign_id);
		/* Set the calendar id attribute */
		$calendar_id = SQLQuery::create()->select("SelectionCampaign")->field("calendar")->where("id",$id)->execute_single_value();
		$this->calendar_id = $calendar_id;
	}
	
	/**
	 * @method create_campaign
	 * @param {string} $name
	 * Create a campaign in the database, set its config to the default values, set the campaign id
	 * Create a calendar for the selection campaign
	 * @return $campaign_id the id of the campaign created
	 */
	public function create_campaign($name){
		$campaign_id = null;
		$fields = array();
		$all_configs = include("config.inc");
		
		/* Create a calendar for this campaign */
		$calendar_id = PNApplication::$instance->calendar->create_shared_calendar($name,array("can_access_selection_data"=>true),array("edit_information_session"=>true));
		
		/* if the calendar was properly created, continue */
		if($calendar_id <> null){
			$fields["name"] = $name;
			$fields["calendar"] = $calendar_id;
			$campaign_id = SQLQuery::create()->insert("SelectionCampaign",$fields);
			$this->set_campaign_id($campaign_id);
			
			/* Insert the default config in the database */
			foreach($all_configs as $all_config){
				SQLQuery::create()->insert("Selection_campaign_config",array("name"=>$all_config[0],"value"=>json_encode($all_config[2])));
			}
			return $campaign_id;
		}
	}
	
	/**
	 * @method remove_campaign
	 * @param $id
	 * Remove the campaign matching with the given ID, and all the linked data. Set the campaign_id attribute to null
	 */
	public function remove_campaign($id){
		/* Set the campaign id to null */
		$this->campaign_id = null;
		/* Set the calendar_id to null */
		$this->calendar_id = null;
		SQLQuery::create()->remove_key("SelectionCampaign",$id);
		
	}
	
	/**
	 * @method rename_campaign
	 * @param $id
	 * @param $name
	 * Set the name of the given campaign (id) to the given value (name) in the database
	 */
	public function rename_campaign($id, $name){
		SQLQuery::create()->update_by_key("SelectionCampaign",$id,array("name"=>$name));
	}
	
	/**
	 * @method get_config
	 * @return the config attribute
	 * If the config attribute is set to null, will get its values in the database and set the config attribute
	 */
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
	
	/**
	 * @method save_config
	 * @param $config an array [name => value]
	 * After updating the config into the database, the config attribute is reseted to null.
	 * This way when a component requires the config attribute, the get_config method will be forced to get the last version in the database
	 * @return in the case of an error is launched by the database, the exception is catched and returned. Otherwise, null is returned
	 */
	public function save_config($configs){
		$to_return = null;
		try{
			foreach($configs as $name=>$value){
				SQLQuery::create()->update_by_key("Selection_campaign_config", $name, array("value"=>$value));
			}
			
		} catch(Exception $e) {
			$to_return = $e;
		}
		$this->config = null;
		return $to_return;
	}
	
	/**
	 * @method get_json_campaigns
	 * Get all the campaigns set in the database and return a json_encoded string to be used by a javascript
	 * @return {string} [{id:'',name:''},{id:'',name:''}...]
	 */
	public function get_json_campaigns(){
		$js = "[]";
		try {$campaigns = SQLQuery::create()->select("SelectionCampaign")->field("id")->field("name")->order_by("name")->execute();
		} catch (Exception $e){
			PNApplication::error($e);
		}
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
	
	/**
	 * @method get_campaigns
	 * Get all the campaigns set in the database and return an array to be used by a php script
	 * @return {array} [[id=>"",name=>""],[id=>"",name=>""],...]
	 */
	public function get_campaigns(){
		try{
			$campaigns = SQLQuery::create()->select("SelectionCampaign")->field("id")->field("name")->order_by("name")->execute();
		} catch (Exception $e){
			PNApplication::error($e);
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
	
	/**
	 * @method save_IS
	 * Update or create an information_session into the database
	 * @param {integer} $id the id of the IS in case of an update
	 * @param []{string} the fields to update/insert
	 * @return $id the id generated in case of an insert
	 */
	public function save_IS($id, $fields_values){
		if(isset($id) && $id <> null){
			// This is an update
			SQLQuery::create()->update_by_key("Information_session",$id,$fields_values);
			return;
		} else {
			// This is an insert
			$id = SQLQuery::create()->insert("Information_session",$fields_values);
			return $id;
		}
	}
	
	/**
	 * @method get_json_IS_data
	 * @param $id the id of the IS
	 * @return string {id:,address:{address_object},date:,number_boys_expected:,number_girls_expected:,number_boys_real:,number_girls_real:,name:,partners:
	 * 					[{organization;,host:,host_address:,contact_points_selected:[...]},{organization...}]}
	 * if $id == -1: the object returned is {id:-1, address:null, date:null, number_expected:null, number_real:null, name:null, partners:[]}
	 */
	public function get_json_IS_data($id, $return_partners_array = false){
		$data = "";
		$partners_array = array();
		if($id == -1 || $id == "-1"){
		//This is a creation
		$data.= "{id:-1, address:null, date:null, number_expected:null, number_real:null, name:null, partners:[]}";
		} else {
			$IS = SQLQuery::create()
					->select("Information_session")
					->field("date")
					->field("postal_address")
					->field("fake_organization")
					->field("number_boys_expected")
					->field("number_girls_expected")
					->field("number_boys_real")
					->field("number_girls_real")
					->field("name")
					->where("id",$id)
					->execute_single_row();
			$partners = SQLQuery::create()
					->field("Information_session_partner","organization","organization")
					->select("Information_session_partner")
					->field("Information_session_partner","host","host")
					->field("host_address")
					->join("Information_session_partner","Information_session_contact_point",array("information_session"=>"information_session","organization"=>"organization"))
					->field("Information_session_contact_point","people","contact_point")
					->where_value("Information_session_partner","information_session",$id)
					->order_by("Information_session_partner","organization")
					->execute();
			if(isset($IS) && $IS <> null){
				$data.= "{id:'".$id."', ";
				// if($IS["postal_address"] <> null){
					// $data.= PNApplication::$instance->contact->get_json_address_good_format_for_address_text($IS["postal_address"]);
				// } else $data.= "address:null";
				$data.= "address:".json_encode($IS["postal_address"]);
				$data.= ", fake_organization:".json_encode($IS["fake_organization"]);
				$data.= ", date:".json_encode($IS["date"]);
				$data.= ", number_boys_expected:".json_encode($IS["number_boys_expected"]);
				$data.= ", number_girls_expected:".json_encode($IS["number_girls_expected"]);
				$data.= ", number_boys_real:".json_encode($IS["number_boys_real"]);
				$data.= ", number_girls_real:".json_encode($IS["number_girls_real"]);
				$data.= ", name:".json_encode($IS["name"]);
				$data.= ", partners:";
				if(isset($partners[0]["organization"]) && $partners[0]["organization"] <> null){
					$first = true;
					$current_partner = null;
					$data.= "[";
					foreach($partners as $p){
						if($p["organization"] != $current_partner){
							$name = SQLQuery::create()->select("Organization")->field("name")->where_value("Organization","id",$p["organization"])->execute_single_value();
							if(!$first){
								$data.= "]}";
								$data.= ", ";
							}
							$first = false;
							$data.= "{";
							array_push($partners_array,$p["organization"]);
							$data.= "organization:".json_encode($p["organization"]).", ";
							$data.= "organization_name:".json_encode($name).", ";
							$data.= "host:".json_encode($p["host"]).", ";
							$data.= "host_address:".json_encode($p["host_address"]).", ";
							$data.= "contact_points_selected:";
							if(isset($p["contact_point"]) && $p["contact_point"] <> null){
								$data.= "[";
								$data.= json_encode($p["contact_point"]);
							} else $data.= "[";
							$current_partner = $p["organization"];
						} else {
							$data.= ", ";
							$data.= json_encode($p["contact_point"]);
						}
					}
					$data.= "]}";
					$data.= "]";
				} else $data.= "[]";
				
				$data.= "}";
			} else $data.="{}";
		}
		if(!$return_partners_array) return $data;
		else return array("data" => $data, "partners" => $partners_array);
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