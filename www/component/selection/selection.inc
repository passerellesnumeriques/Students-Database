<?php
require_once("component/contact/OrganizationPlugin.inc");
$all_steps = include("steps.inc");

	/**
	 * Return an array("add"=>value,"remove"=>value,"update"=>value)
	 * where the value is set according to the most restrictive one of the parameters
	 */
	function getRestrictedRights($restricted_adds,$restricted_removes,$restricted_updates){
		$rights = array();
		if(count($restricted_adds) > 0){
			$rights["add"] = true;
			$rights["remove"] = true;
			$rights["update"] = true;
			foreach($restricted_adds as $r){
				if(!$r){
					$rights["add"] = false;
					break;
				}
			}
			foreach($restricted_removes as $r){
				if(!$r){
					$rights["remove"] = false;
					break;
				}
			}
			foreach($restricted_updates as $r){
				if(!$r){
					$rights["update"] = false;
					break;
				}
			}
		}
		return $rights;
	}
	
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
		$this->calendar_id = SQLQuery::create()->select("SelectionCampaign")->field("calendar")->where("id",$id)->execute_single_value();
	}
	
	/**
	 * @method create_campaign
	 * @param {string} $name
	 * Create a campaign in the database, set its config to the default values, set the campaign id, set its steps
	 * Create a calendar for the selection campaign
	 * @return $campaign_id the id of the campaign created
	 */
	public function create_campaign($name){
		$campaign_id = null;
		$fields = array();
		$all_configs = include("config.inc");
		$all_steps = include("steps.inc");
		//check the rights
		if(!PNApplication::$instance->user_management->has_right("manage_selection_campaign",true)){
			PNApplication::error("You are not allowed to manage the selection campaigns");
			return;
		}
		
		SQLQuery::start_transaction();
		try{
		/* Create a calendar for this campaign */
		$calendar_id = PNApplication::$instance->calendar->createSharedCalendar($name,array("can_access_selection_data"=>true),array("edit_information_session"=>true));
		
		/* if the calendar was properly created, continue */
		if($calendar_id <> null){
			$fields["name"] = $name;
			$fields["calendar"] = $calendar_id;
			$campaign_id = SQLQuery::create()->insert("SelectionCampaign",$fields);
			$this->set_campaign_id($campaign_id);
			
			/* Insert the default config in the database */
			$default_config_rows = array();
			foreach($all_configs as $name => $data)
				array_push($default_config_rows,array(
					"name" => $name,
					"value" => json_encode($data[1]),
				));
			SQLQuery::create()->insert_multiple("Selection_campaign_config",$default_config_rows);
				
				/* Insert the default steps in the database */
				$rows_steps = array();
				foreach($all_steps as $name => $temp)
					array_push($rows_steps, array("name" => $name, "value" => false));
				SQLQuery::create()->insert_multiple("Steps",$rows_steps);
		}
		} catch (Exception $e) {
			PNApplication::error($e);
		}
		if(PNApplication::has_errors())
			SQLQuery::cancel_transaction(); //Rollback
		else {
			SQLQuery::end_transaction(); //Commit
		}
		return $campaign_id;
	}
	
	/**
	 * @method remove_campaign
	 * @param $id
	 * Remove the campaign matching with the given ID, and all the linked data. Set the campaign_id attribute to null
	 */
	public function remove_campaign($id){
		//check the rights
		if(!PNApplication::$instance->user_management->has_right("manage_selection_campaign",true)){
			PNApplication::error("You are not allowed to manage the selection campaigns");
			return;
		}
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
		//check the rights
		if(!PNApplication::$instance->user_management->has_right("manage_selection_campaign",true)){
			PNApplication::error("You are not allowed to manage the selection campaigns");
			return;
		}
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
	
	public function getOneConfigAttributeValue($name){
		$conf = $this->get_config();
		for($i = 0; $i < count($conf); $i++){
			if($conf[$i]["name"] == $name)
				return $conf[$i]["value"];
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
	 * The steps are always checked into the database, to be sure
	 * that each user is having the same steps values
	 * @return {array} [["step_name"] => value,...]
	 */
	public function getSteps(){
			$all_steps = include("steps.inc");
		$steps = array();
		$db_steps = SQLQuery::create()
			->select("Steps")
			->field("Steps","name","name")
			->field("Steps","value","value")
			->execute();
		foreach($all_steps as $name => $temp){
			foreach($db_steps as $db){
				if($db["name"] == $name){
					if($db["value"] == "0" || $db["value"] == false)
						$steps[$name] = false;
					else
						$steps[$name] = true;
			}
		}
	}
		return $steps;
	}
	
	public function getJsonSteps(){
		$all_steps = include("steps.inc");
		$steps = $this->getSteps();
		$json = "[";
		$first = true;
		foreach($steps as $name => $value){
			if(!$first)
				$json.= ", ";
			$first = false;
			$json.= "{name:".json_encode($name).",value:".json_encode($value);
			$json.= ",text:".json_encode($all_steps[$name][0])."}";
		}
		$json .= "]";
		return $json;
	}
	
	/**
	 * This method must be called every time a saving / remove method is called
	 */
	public function updateAllSteps(){
		$all_steps = include("steps.inc");
		$steps = $this->getSteps();
		foreach($steps as $name => $value){
		$r = SQLQuery::create()
			->bypass_security() //mandatory
				->select($all_steps[$name][1])
			->count() //just check if empty or not, no need of more data
			->execute_single_value();
			if($r <> null && $r != 0 && $r != "0"){
				if(!$steps[$name]) //must be updated
					SQLQuery::create()
						->bypass_security()
						->update_by_key("Steps",$name, array('value' => true));
			} else {
				if($steps[$name]) //must be updated
					SQLQuery::create()
						->bypass_security()
						->update_by_key("Steps",$name, array('value' => false));
	}
	}
	}
	
	/** This method must be called by each "save" and "remove" method to valid the call, as a last check
		 * Can also be called before creating a saving page such as exam_subject to temporarly
		 * update the user rights
	 * @param {sting} name the step name
	 * @return {array} [0]: {boolean} true if can valid this step
	 * 					[1]: {boolean} true if can unvalid this step
	 * 					[2]: {string} error message to display in case cannot be updated
	 * 					[3]: {array | null} restricted rights if [1] == false
	 *								["add"]: {boolean} true if can add
	 *								["remove"]: {boolean} true if can remove
	 *								["update"]: {boolean} true if can update
		 */
	public function canUpdateStep($name){
		$all_steps = include("steps.inc");
		$can_valid = true;
		$can_unvalid = true;
		$steps = $this->getSteps();
		$errors_message = "";
		$first_error = true;
		$restricted_rights = array();
		if($steps[$name]){ 
			/** Check in the dependencies of all the already validated steps
			 * and give the restricted rights
			 */
			$to_check = array();
			$restricted_adds = array();
			$restricted_removes = array();
			$restricted_updates = array();
			foreach($steps as $step_name => $value){
				if($value)
					array_push($to_check,$step_name);
			}
			foreach($to_check as $step_name){
				foreach($all_steps[$step_name][2] as $d){
					if(isset($d[0]) && $d[0] == $name){
						if($d[1]){ //means this dependency needs the current step to be validated
							$can_unvalid = false;
							if($first_error){
								$errors_message .= "<center>Your rights have been temporarly updated<br/>because the following steps are already validated:<br/></center>";
								$first_error = false;
							}
							$errors_message.= "<i>".$all_steps[$step_name][0]."</i><br/>";
							if(isset($d[3]["add"]))
								array_push($restricted_adds, $d[3]["add"]);
							if(isset($d[3]["remove"]))
								array_push($restricted_removes, $d[3]["remove"]);
							if(isset($d[3]["update"]))
								array_push($restricted_updates, $d[3]["update"]);
						}
					}
				}
			}
			$restricted_rights = getRestrictedRights($restricted_adds,$restricted_removes,$restricted_updates);
			return array($can_valid,$can_unvalid,$errors_message,$restricted_rights);
		} else {
			/** Check the dependencies that allow this validation
			 */
			$step_data = $all_steps[$name];
			foreach($step_data[2] as $d){
				if(isset($d[0]) && $steps[$d[0]] != $d[1]){
					$can_valid = false;
					if(!$first_error)
						$errors_message.="<br/>";
					$first_error = false;
					$errors_message.= $d[2];
				}
			}
			return array($can_valid,$can_unvalid,$errors_message,null);
		}
	}
	
	/**
	 * This method don't consider the case of the steps can only restrict any rights,
	 * so is having a all-or-nothing basis
	 */
	public function canUpdateFromRightAndStep($step_name, $right_name, $error_message_if_no_right){
		$error_messages = "";
		$can_update = true;
		$first_error = false;
		// check the steps
		$validation_steps = $this->canUpdateStep($step_name);
		if(!$validation_steps[0] || !$validation_steps[1]){
			$can_update = false;
			$first_error = false;
			$error_messages .= $validation_steps[2];
		}
		// check the right
		if(!PNApplication::$instance->user_management->has_right($right_name,true)){
			if(!$first_error)
				$error_messages .= "<br/>";
			$can_update = false;
			$first_error = false;
			$error_messages .= $error_message_if_no_right;
		}
		return array($can_update, $error_messages);
	}
	
	/**
	 * @param {string} step_name
	 * @param {string} remove_right_name
	 * @param {string} edit_right_name
	 * @param {string} remove_right_name
	 * This method gets the user rights from user_management and handle the restrictions due to the steps
	 * @return {array} [0]: {array} rights values
	 *					[1]: {boolean} true if the rights were restricted
	 *					[2]: {string} restriction message
	 */
	public function getRestrictedRightsFromStepsAndUserManagement($step_name, $add_right_name, $remove_right_name, $edit_right_name){
		//First get the rights
		$can_add = PNApplication::$instance->user_management->has_right($add_right_name,true);
		$can_remove = PNApplication::$instance->user_management->has_right($add_right_name,true);
		$can_edit = PNApplication::$instance->user_management->has_right($add_right_name,true);
		//then check the steps
		$restrictions = array(false,null);
		if($can_add || $can_remove || $can_edit){ //if no right, nothing to do
			$restrictions = $this->updateRightsFromStepsDependenciesRestrictions($step_name, $can_add, $can_remove, $can_edit);
		}
		return array(array("add" => $can_add, "remove" => $can_remove, "edit" => $can_edit),$restrictions[0], $restrictions[1]);
	}
	
	/**
	 * @param {boolean} can_add
	 * @param {boolean} can_remove
	 * @param {boolean} can_edit
	 * This method restricts (but never increase) the given rights according to the steps dependencies restrictions
	 * The rights given as parameters shall be coming from user_management functionalities
	 * @return  {array} [0]:{boolean} restricted, true if the steps dependencies have restricted the rights
	 *					[1]: {string} restriction message
	 */
	public function updateRightsFromStepsDependenciesRestrictions($step_name,&$can_add, &$can_remove, &$can_edit){
		$validation_step = $this->canUpdateStep($step_name);
		$restricted = false;
		if(!$validation_step[0]){
			//we cannot valid this step, so cannot do anything
			$can_edit = false;
			$can_remove = false;
			$can_add = false;
			$restricted = true;
		} else if(!$validation_step[1]){
			//we cannot unvalid this step, and the forbidden actions on exam_subject table are given in validation_step
			/* The rights can only be restricted, not increased */
			if(!$validation_step[3]["add"]){
				if($can_add != false)
					$restricted = true;
				$can_add = false;
			}
			if(!$validation_step[3]["remove"]){
				if($can_remove != false)
					$restricted = true;
				$can_remove = false;
			}
			if(!$validation_step[3]["update"]){
				if($can_remove != false)
					$restricted = true;
				$can_edit = false;
			}
		}
		return array($restricted,$validation_step[2]);
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
	 * @param {boolean} (optional) asc true if the campaigns must be ordered by an ascendant order
	 * Get all the campaigns set in the database and return an array to be used by a php script
	 * @return {array} [[id=>"",name=>""],[id=>"",name=>""],...]
	 */
	public function get_campaigns($asc = false){
		try{
			$campaigns = SQLQuery::create()
				->select("SelectionCampaign")
				->field("id")
				->field("name")
				->order_by("SelectionCampaign","id",$asc)
				->execute();
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
	 * @method saveIS
	 * Update or create an information_session into the database
	 * The rights must be checked before calling this method
	 * @param {integer} $id the id of the IS in case of an update
	 * @param []{string} the fields to update/insert
	 * @return $id the id generated in case of an insert
	 */
	public function saveIS($id, $fields_values){
		try {
		if(isset($id) && $id <> null){
			// This is an update
			SQLQuery::create()->update_by_key("Information_session",$id,$fields_values);
			return;
		} else {
			// This is an insert
			$id = SQLQuery::create()->insert("Information_session",$fields_values);
			return $id;
		}
			//update the steps
			$this->updateAllSteps();
		} catch (Exception $e) {
			PNApplication::error($e);
	}
	}
	
	/**
	 * @param {integer} IS_id the IS id from which the partners must be removed
	 * Remove all the partners of the given information session
	 * The rights must be checked before calling this method
	 */
	public function removeAllISPartners($IS_id){
		$partners = SQLQuery::create()->select("Information_session_partner")
				->field("organization")
				->where_value("Information_session_partner","information_session",$IS_id)
				->execute();
		if(isset($partners[0]["organization"])){
			foreach($partners as $p)
				SQLQuery::create()->remove_keys("Information_session_partner",array(array("information_session" => $IS_id, "organization" => $p["organization"])));
		}
	}	
	
	/**
	 * @param {integer} IS_id
	 * Remove all the contact points of the given information session
	 * The rights must be checked before calling this method
	 */
	public function removeAllISContactPoints($IS_id){
		$contact_point = SQLQuery::create()->select("Information_session_contact_point")
				->field("organization")
				->field("people")
				->where_value("Information_session_contact_point","information_session",$IS_id)
				->execute();
		if(isset($contact_point[0]["organization"])){
			foreach($contact_point as $c)
				SQLQuery::create()
				->remove_keys("Information_session_contact_point",
				array(
					array("information_session" => $IS_id, "organization" => $c["organization"], "people" => $c["people"])
				));
		}
	}
	
	/**
	 * @param {array} rows to insert in information_session_partner table
	 * The rights must be checked before calling this method
	 */
	public function insertISPartners($rows){
		SQLQuery::create()->insert_multiple("Information_session_partner",$rows);
	}
	
	/**
	 * @param {array} rows to insert in Information_session_contact_point table
	 * The rights must be checked before calling this method
	 */
	public function insertISContactPoints($rows){
		SQLQuery::create()->insert_multiple("Information_session_contact_point",$rows);
	}
	/**
	 * @param {integer} id the one of the IS to remove
	 * @param {integer} fake_organization the id of the fake organization linked to this IS
	 */
	public function removeIS($id, $fake_organization){
		// check the rights
		if(!PNApplication::$instance->user_management()->has_right("",true)){
			PNApplication::error("You are not allowed to remove any information session");
			return;
		}
		//update the steps
		$this->updateAllSteps();
		$this->removeFakeOrganization($id, $fake_organization);
		SQLQuery::create()->remove_key("Information_session",$id);
		if(PNApplication::has_errors())
			return;
		else
			return true;
	}
	
	/**
	 * @param {integer} IS_id
	 * @param {integer} fake_organization id
	 * Remove the fake organization linked to an information session
	 * after checking it is really the one linked to the given IS
	 * The rights must be checked before calling this method
	 */
	private function removeFakeOrganization($IS_id, $fake_organization){
		//check the fake_organization matches with the one set in the IS
		$db_fake = SQLQuery::create()->select("Information_session")
			->field("fake_organization")
			->where_value("Information_session","id",$IS_id)
			->execute_single_value();
		if(isset($db_fake)){
			if($db_fake == $fake_organization)
				SQLQuery::create()->bypass_security()->remove_key("Organization",$fake_organization);
			else
				PNApplication::error("The given fake_organization(".$fake_organization.") does not match with the one set into the database(".$db_fake.")");
		} else
			PNApplication::error("This fake_organization does not exist into the database");
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
		$data.= "{id:-1, address:null, fake_organization:null, date:null, number_boys_expected:null, number_boys_real:null, number_girls_expected:null, number_girls_real:null, name:null, partners:[]}";
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
	
	public function get_json_exam_subject_data($exam_id){
		$data = SQLQuery::create()
			->select("Exam_subject")
			->field("Exam_subject","name","subject_name")
			->field("Exam_subject","max_score","subject_max_score")
			->join("Exam_subject","Exam_subject_part",array("id" => "exam_subject"))
			->field("Exam_subject_part","id","part_id")
			->field("Exam_subject_part","index","part_index")
			->field("Exam_subject_part","max_score","part_max_score")
			->field("Exam_subject_part","name","part_name")
			->join("Exam_subject_part","Exam_subject_question",array("id" => "exam_subject_part"))
			->field("Exam_subject_question","id","question_id")
			->field("Exam_subject_question","index","question_index")
			->field("Exam_subject_question","max_score","question_max_score")
			->field("Exam_subject_question","correct_answer","question_correct_answer")
			->field("Exam_subject_question","choices","question_choices")
			->where_value("Exam_subject","id",$exam_id)
			->order_by("Exam_subject_part","id")
			->execute();
			
		$json = "";
		if(isset($data[0]["subject_name"])){
			$json.= "{id:".json_encode($exam_id);
			$json.= ", name:".json_encode($data[0]["subject_name"]);
			$json.= ", max_score:".json_encode($data[0]["subject_max_score"]);
			$json.= ", parts:";
			$no_part = true;
			foreach($data as $d){
				if(isset($d["part_id"])){
					$no_part = false;
					break;
				}
			}
			if($no_part){
				$json.= "[]";
			} else {
				$json.= "[";
				$no_question = true;
				foreach($data as $d){
					if(isset($d["question_id"])){
						$no_question = false;
						break;
					}
				}
				if($no_question){
					$first = true;
					foreach($data as $d){
						if(!$first)
							$json.= ", ";
						$first = false;
						$json.= "{id:".json_encode($d["part_id"]);
						$json.= ", index:".json_encode($d["part_index"]);
						$json.= ", name:".json_encode($d["part_name"]);
						$json.= ", max_score:".json_encode($d["part_max_score"]);
						$json.= ", questions:[]";
						$json.= "}";
					}
				} else {
					$current_part = $data[0]["part_id"];
					$no_question_before = false;
					$first_part = true;
					foreach ($data as $d){
						if($d["part_id"] != $current_part){
							if($no_question_before){
								$json.= "]}, ";
								$no_question_before = false;
							} else
								$json.= "}]}, ";
							$json.= "{id:".json_encode($d["part_id"]);
							$json.= ", index:".json_encode($d["part_index"]);
							$json.= ", name:".json_encode($d["part_name"]);
							$json.= ", max_score:".json_encode($d["part_max_score"]);
							if(!isset($d["question_id"])){
								$json.= ", questions:[";
								$no_question_before = true;
							} else {
								$json.= ", questions:[{";
								$json.= "id:".json_encode($d["question_id"]);
								$json.= ", index:".json_encode($d["question_index"]);
								$json.= ", max_score:".json_encode($d["question_max_score"]);
								$json.= ", correct_answer:".json_encode($d["question_correct_answer"]);
								$json.= ", choices:".json_encode($d["question_choices"]);
							}
						} else {
							if(!$first_part){
								$json.= "}, {";
								$json.= "id:".json_encode($d["question_id"]);
								$json.= ", index:".json_encode($d["question_index"]);
								$json.= ", max_score:".json_encode($d["question_max_score"]);
								$json.= ", correct_answer:".json_encode($d["question_correct_answer"]);
								$json.= ", choices:".json_encode($d["question_choices"]);
							} else {
								$json.= "{";
								$json.= "id:".json_encode($d["part_id"]);
								$json.= ", index:".json_encode($d["part_index"]);
								$json.= ", name:".json_encode($d["part_name"]);
								$json.= ", max_score:".json_encode($d["part_max_score"]);
								$json.= ", questions:[";
								if(!isset($d["question_id"]))
									$no_question_before = true;
								else{
									$json.= "{id:".json_encode($d["question_id"]);
									$json.= ", index:".json_encode($d["question_index"]);
									$json.= ", max_score:".json_encode($d["question_max_score"]);
									$json.= ", correct_answer:".json_encode($d["question_correct_answer"]);
									$json.= ", choices:".json_encode($d["question_choices"]);
								}
							}
							$first_part = false;
						}
						$current_part = $d["part_id"];
					}
					if(!$no_question_before)
						$json.= "}]}";
					else
						$json.= "]}";
				}
				$json.= "]";
			}
			$json.= "}";
		} else 
			$json = "{}";
		return $json;
	}
	
	public function getAllExamSubjectNames(){
		$names = SQLQuery::create()
				->select("Exam_subject")
				->field("name")
				->execute_single_field();
		return $names;
	}
	
	public function getAllExamSubjects(){
		return SQLQuery::create()
				->select("Exam_subject")
				->field("Exam_subject","name","name")
				->field("Exam_subject","id","id")
				->execute();
	}
	
	public function canManageExamSubjectQuestions(){
		return $this->canUpdateFromRightAndStep("manage_exam","manage_exam_subject","You are not allowed to manage the exam subject questions");
		}
		
	public function removeExam($exam_id){
		$parts = null;
		//check the rights
		$can_remove = $this->canManageExamSubjectQuestions();
		if($can_remove[0]){
			$parts = SQLQuery::create()
				->select("Exam_subject_part")
				->field("id")
				->where_value("Exam_subject_part","id",$exam_id)
				->execute_single_field();
			try{
				SQLQuery::create()->bypass_security()->remove_key("Exam_subject",$exam_id);
			} catch (Exception $e) {
				PNApplication::error($e);
			}
		} else
			PNApplication::error($can_remove[1]);
		if(PNApplication::has_errors())
			return false;
		else {
			//update the topics
			$this->updateExamTopicsForEligibilityRules($parts,null,$exam_id);
			//update steps
			$this->updateAllSteps();
			return true;
		}
	}
	
	private function updatePartIdInQuestionsRows($parts_ids,$questions_by_part,$parts_indexes){
		$rows_questions_table = array();
		for($i = 0; $i < count($parts_indexes); $i++){
			foreach($questions_by_part[$parts_indexes[$i]] as $q){
				$q["exam_subject_part"] = $parts_ids[$i];
				array_push($rows_questions_table,$q);
			}
		}
		return $rows_questions_table;
	}
	
	public function saveExam($exam_id, $rows_exam_table, $rows_parts_table, $questions_by_old_parts ,$questions_by_new_part, $parts_to_insert_indexes){
		// Check the rights
		$can_update = $this->canManageExamSubjectQuestions();
		if($can_update[0]){
			SQLQuery::start_transaction();
			try {
			$has_questions_from_old_parts = false;
			$has_questions_from_new_parts = false;
			$has_parts = false;
			if(count($rows_parts_table) > 0)
				$has_parts = true;
			if($has_parts){
				foreach($questions_by_old_parts as $q){
					if(count($q) > 0){
						$has_questions_from_old_parts = true;
						break;
					}
				}
				foreach($questions_by_new_part as $q){
					if(count($q) > 0){
						$has_questions_from_new_parts = true;
						break;
					}
				}
			}
				
			if($exam_id == -1 || $exam_id == "-1"){
				// this an insert
				$exam_id = SQLQuery::create()
					->insert("Exam_subject",$rows_exam_table);
				if($has_parts){
					// update parts exam_subject id
					foreach($rows_parts_table as &$p){
						$p["exam_subject"] = $exam_id;
						unset($p["id"]);
					}
					// save
					$new_parts = SQLQuery::create()
						->insert_multiple("Exam_subject_part",$rows_parts_table);
					
					if($has_questions_from_new_parts){
						$rows_questions_table = $this->updatePartIdInQuestionsRows($new_parts,$questions_by_new_part,$parts_to_insert_indexes);
						// insert using bypass_security
						SQLQuery::create()
							->bypass_security()
							->insert_multiple("Exam_subject_question",$rows_questions_table);
					}
						// create a topic full_subject
						$this->createTopicAndInsertFullExamSubject($exam_id,$new_parts);
				}
			} else {
				// this is an update
				SQLQuery::create()
					->update_by_key("Exam_subject",$exam_id,$rows_exam_table);
				
				$had_parts = false;
				// get the old parts id and old questions id
				$old_parts = SQLQuery::create()
					->select("Exam_subject_part")
					->field("id")
					->where_value("Exam_subject_part","exam_subject",$exam_id)
					->execute_single_field();
				if(count($old_parts) > 0)
					$had_parts = true;
				$new_parts_ids = null;
				if($had_parts){
					//Because of the topics for eligibility rules, we must update the parts (instead of remove / insert)
					$rows_parts_to_update = array();
					$rows_parts_to_insert = array();
					$parts_to_remove = array();
					$parts_ids_to_update = array();
					$parts_updated = array();
					foreach($rows_parts_table as $p){
						if($p["id"] == -1 || $p["id"] == "-1")
							array_push($rows_parts_to_insert,array(
								"exam_subject" => $exam_id,
								"index" => $p["index"],
								"max_score" => $p["max_score"],
								"name" => $p["name"]
							));
						else {
							//this is an update
							array_push($rows_parts_to_update,$p);
							array_push($parts_ids_to_update,$p["id"]);
						}
					}
					//get the parts to remove
					foreach($old_parts as $old_part){
						if(!in_array($old_part,$parts_ids_to_update))
							array_push($parts_to_remove,$old_part);
					}
					//remove the questions of the updated parts (removed parts will remove all the other questions)
					if(count($parts_ids_to_update) > 0){
						$questions_to_remove_from_parts_to_update = SQLQuery::create()
								->bypass_security()
								->select("Exam_subject_question")
								->field("id")
								->where_in("Exam_subject_question","exam_subject_part",$parts_ids_to_update)
								->execute_single_field();
						if(count($questions_to_remove_from_parts_to_update) > 0)
							SQLQuery::create()
								->bypass_security()
								->remove_keys("Exam_subject_question",$questions_to_remove_from_parts_to_update);
					}										
					//update the parts to update
					if(count($rows_parts_to_update) > 0){
						foreach($rows_parts_to_update as $row){
							SQLQuery::create()
								->update_by_key("Exam_subject_part",$row["id"],array(
									"exam_subject" => $exam_id,
									"index" => $row["index"],
									"max_score" => $row["max_score"],
									"name" => $row["name"]
								));
							array_push($parts_updated,$row["id"]);
						}
					}
					//insert the parts to insert
					if(count($rows_parts_to_insert) > 0){
						$new_parts_ids = SQLQuery::create()
							->insert_multiple("Exam_subject_part",$rows_parts_to_insert);
					}
					/** The exam topics for eligibility rules must only be updated in the case of the subject
					 * already had parts (that could have been selected into any exam topic)
					 * This method must be called before removing the parts, to remove the topic if empty
					 */
						$this->updateExamTopicsForEligibilityRules($parts_to_remove,$new_parts_ids,$exam_id);
					
					//remove the parts to remove
					if(count($parts_to_remove) > 0)
						SQLQuery::create()
							->bypass_security()
							->remove_keys("Exam_subject_part",$parts_to_remove);
				} else if($has_parts) {
					//all the parts must be inserted
					$new_parts_ids = SQLQuery::create()
						->insert_multiple("Exam_subject_part",$rows_parts_table);	
				}
				/** update the part id of the questions coming from new parts
				 * If !$had_parts, all the questions are in $questions_by_new_part
				 */
				if(isset($new_parts_ids)){
					$rows_questions_of_inserted_parts = $this->updatePartIdInQuestionsRows($new_parts_ids,$questions_by_new_part,$parts_to_insert_indexes);
					// insert these questions
					SQLQuery::create()
						->bypass_security()
						->insert_multiple("Exam_subject_question",$rows_questions_of_inserted_parts);
				}
				if(count($questions_by_old_parts) > 0){ //insert the remaining questions
					$rows_questions_from_old_parts = array();
					foreach($questions_by_old_parts as $part_questions){
						foreach($part_questions as $q)
							array_push($rows_questions_from_old_parts,$q);
					}					
					SQLQuery::create()
						->bypass_security()
						->insert_multiple("Exam_subject_question",$rows_questions_from_old_parts);
				}
			}
			} catch (Exception $e){
				PNApplication::error($e);
			}
			if(PNApplication::has_errors())
				SQLQuery::cancel_transaction(); //Rollback
			else
				SQLQuery::end_transaction(); //Commit
		} else
			PNApplication::error($can_update[1]);
		if(PNApplication::has_errors())
			return false;
		else {
			//update steps
			$this->updateAllSteps();
			// return a new subject object
			return $this->get_json_exam_subject_data($exam_id);
			//TODO return information message from topic
		}
	}
	
	private function getAllApplicantIds(){
		$ids = SQLQuery::create()
			->bypass_security()
			->select("Applicant")
			->field("applicant_id")
			->execute_single_field();
		return $ids;
	}
	
	public function externalGetAllApplicantsIds(){
		// check the rights
		if(PNApplication::$instance->user_management->has_right("see_applicant_info",true))
			return $this->getAllApplicantIds();
		else
			PNApplication::error("You are not allowed to access to the applicants informations");
	}
	/** @method selection#updateExamTopicsForEligibilityRules
	 * Handle the consequences on the exam topics of saving an exam
	 * In case a topic gets empty after removing parts, it is automatically deleted
	 * @param {array} parts_removed containing the ids of the removed parts
	 * @param {array} parts_inserted containing the ids of the inserted parts,
	 * used to generate a message + in case the topic is a full subject one (added to the topic)
	 */	
	public function updateExamTopicsForEligibilityRules($parts_removed,$parts_inserted,$exam_id){
	
	//TODO finish(message)
	
		if($parts_removed <> null || $parts_inserted <> null){
			// find out the related topics for eligibility rules
			$topics_for_parts_removed = null;
			if($parts_removed <> null)
				$topics_for_parts_removed = $this->getTopicsConcernedByParts($parts_removed);
				$inserted = false;
				$topics_removed = false;
				$removed_because_updated_empty = true;
				if($parts_inserted <> null){
						// check if this subject is a "full topic" for any of the topic, and in that case insert the new parts
						$topics_to_be_inserted = $this->getTopicsFullExamForGivenExam($exam_id);
						if($topics_to_be_inserted <> null){
							//perform the inserts
							foreach($topics_to_be_inserted as $t){
								$rows_inserted = array();
								foreach($parts_inserted as $p)
									array_push($rows_inserted, array("exam_subject_part" => $p, "exam_topic_for_eligibility_rule" => $t));
								SQLQuery::create()
									->insert_multiple("Exam_part_topic",$rows_inserted);
							}
						}
				}
				if($topics_for_parts_removed <> null){
					// check if any topic is now empty
					$topics_to_remove = array();
					foreach($topics_for_parts_removed as $t){
						if($this->isTopicEmpty($t))
							array_push($topics_to_remove,$t);
					}
					if(count($topics_to_remove) > 0){
						SQLQuery::create()
							->remove_keys("Exam_topic_for_eligibility_rule",$topics_to_remove);
						$topics_removed = true;
					}
				}
				return array($inserted,$topics_removed);
		} else
			return;
	}
	
	private function getTopicsFullExamForGivenExam($exam_id){
		$topics = SQLQuery::create()
			->select("Exam_topic_full_exam")
			->field("Exam_topic_full_exam","exam_topic_for_eligibility_rule","topic_id")
			->where_value("Exam_topic_full_exam","exam_subject",$exam_id)
			->execute_single_field();
		return $topics;
	}
	
	private function isTopicEmpty($topic_id){
		$q = SQLQuery::create()
			->select("Exam_part_topic")
			->count()
			->where_value("Exam_part_topic","exam_topic_for_eligibility_rule",$topic_id)
			->execute_single_value();
		if($q <> null){
			if($q == 0 || $q == "0")
				return true;
			else
				return false;
		} else
			return true;
	}
	
	private function isPartEmpty($part_id){
		$q = SQLQuery::create()
			->select("Exam_subject_question")
			->count()
			->where_value("Exam_subject_question","exam_subject_part",$part_id)
			->execute_single_value();
		if($q <> null){
			if($q == 0 || $q == "0")
				return true;
			else
				return false;
		} else
			return true;
	}
	
	private function getTopicsConcernedByParts($parts){
		if(isset($parts[0])){
		$topics = SQLQuery::create()
			->select("Exam_part_topic")
			->field("Exam_part_topic","exam_topic_for_eligibility_rule","topic")
			->where_in("Exam_part_topic","exam_subject_part",$parts)
			->execute_single_field();
		return $topics;
		} else
			return null;
	}
	
	public function createTopic($name, $parts_to_insert, $topic_max_score = null, $topic_number_questions = null){
		if($parts_to_insert <> null){
			//insert the topic
			$id = SQLQuery::create()
				->insert("Exam_topic_for_eligibility_rule", array(
					"name" => $name,
					"max_score" => $topic_max_score,
					"number_questions" => $topic_number_questions
				));
			//insert the parts
			$rows = array();
			foreach($parts_to_insert as $p)
				array_push($rows,array("exam_topic_for_eligibility_rule" => $id, "exam_subject_part" => $p));
			SQLQuery::create()
				->insert_multiple("Exam_part_topic",$rows);
			return $id;
		}
	}
	
	private function createTopicAndInsertFullExamSubject($exam_id, $parts_to_insert){
		if($parts_to_insert <> null){
			//Get the exam name
			$name = SQLQuery::create()
				->select("Exam_subject")
				->field("name")
				->where_value("Exam_subject","id",$exam_id)
				->execute_single_value();
			if($name <> null){
				$id = $this->createTopic($name, $parts_to_insert);
				//insert in the Exam_topic_full_exam table
				SQLQuery::create()
					->insert("Exam_topic_full_exam",array("exam_subject" => $exam_id, "exam_topic_for_eligibility_rule" => $id));
				return $id;
			}
		}
	}
	// private function getPartByTopicConcerned($part_ids,&$topics){
		// foreach($part_ids as $p){
			// $topic = SQLQuery::create()
				// ->bypass_security()
				// ->select("Exam_part_topic")
				// ->distinct()
				// ->join("Exam_part_topic","Exam_topic_for_eligibility_rule",array("exam_topic_for_eligibility_rule" => "id"))
				// ->field("Exam_topic_for_eligibility_rule","id","id")
				// ->field("Exam_topic_for_eligibility_rule","name","name")
				// ->where_value("Exam_part_topic","exam_subject_part",$p)
				// ->execute();
			// if($topic <> null){
				// if(!isset($topics[$topic["id"]]))
					// $topics[$topic["id"]] = array(
						// "name" => $topic["name"],
						// "parts" => array()
					// );
				// array_push($topics[$topic["id"]]["parts"],$p);
			// }
		// }
	// }

	public function checkAllPartsAppearOneTimeInATopicForEligibilityRules(){
		//TODO
		// check that each exam subject part appears at least one time in a topic for eligibility rules
		// otherwize means that this part is useless!!!
	}

	/** @method#selection#getExamTopicForEligibilityRulesData
	 * @param {integer} topic_id the id of the topic
	 * @return {array} [id => ""
	 * 					name => ""
	 * 					subjects => [
	 *							id => [
	 *								name => ""
	 *								max_score => ""
	 *								{boolean} full_subject =>
	 * 								parts => array(
	 *									id =>[
	 *										name => ""
	 *										max_score => ""
	 *										index => ""
	 *									],...)
	 * 							],...]
	 *					]
	 * subjects & parts arrays use the subject id (respectively part id) as key
	 */
	public function getExamTopicForEligibilityRules($topic_id){
		$data = SQLQuery::create()
			->select("Exam_topic_for_eligibility_rule")
			->field("Exam_topic_for_eligibility_rule","name","name")
			->field("Exam_topic_for_eligibility_rule","max_score","max_score")
			->field("Exam_topic_for_eligibility_rule","number_questions","number_questions")
			->join("Exam_topic_for_eligibility_rule","Exam_part_topic",array("id" => "exam_topic_for_eligibility_rule"))
			->join("Exam_part_topic","Exam_subject_part",array("exam_subject_part" => "id"))
			->join("Exam_subject_part","Exam_subject",array("exam_subject" => "id"))
			->field("Exam_subject_part","id","part_id")
			->field("Exam_subject_part","exam_subject","exam_id")
			->field("Exam_subject_part","max_score","part_max_score")
			->field("Exam_subject_part","index","part_index")
			->field("Exam_subject_part","name","part_name")
			->field("Exam_subject","name","exam_name")
			->field("Exam_subject","max_score","exam_max_score")
			->where_value("Exam_topic_for_eligibility_rule","id",$topic_id)
			->order_by("Exam_subject","id")
			->execute();
		$full_subjects = SQLQuery::create()
			->select("Exam_topic_full_exam")
			->field("exam_subject")
			->where_value("Exam_topic_full_exam","exam_topic_for_eligibility_rule",$topic_id)
			->execute_single_field();
		if(isset($data[0]["name"])){
			$topic = array();
			$topic["id"] = $topic_id;
			$topic["name"] = $data[0]["name"];
			$topic["max_score"] = $data[0]["max_score"];
			$topic["number_questions"] = $data[0]["number_questions"];
			$topic["subjects"] = array();
			foreach($data as $d){
				if(isset($topic["subjects"][$d["exam_id"]])){
					$topic["subjects"][$d["exam_id"]]["parts"][$d["part_id"]] = array(
						"name" => $d["part_name"],
						"max_score" => $d["part_max_score"],
						"index" => $d["part_index"]
					);
				} else {
					//create the subject array
					$topic["subjects"][$d["exam_id"]] = array(
						"name" => $d["exam_name"],
						"max_score" => $d["exam_max_score"],
						"parts" => array()
					);
					if(in_array($d["exam_id"],$full_subjects))
						$topic["subjects"][$d["exam_id"]]["full_subject"] = true;
					else
						$topic["subjects"][$d["exam_id"]]["full_subject"] = false;
					//create the part
					$topic["subjects"][$d["exam_id"]]["parts"][$d["part_id"]] = array(
						"name" => $d["part_name"],
						"max_score" => $d["part_max_score"],
						"index" => $d["part_index"]
					);					
				}
				//a topic part cannot be empty because removed by #selection#updateExamTopicsForEligibilityRules
			}
			return $topic;
		} else
			return;
	}
	
	public function getJsonExamTopicForEligibilityRules($id){
		$topic = $this->getExamTopicForEligibilityRules($id);
		$json = "";
		if(!isset($topic["id"]))
			$json = "{}";
		else {
			$json .= "{id:".json_encode($id).", name:".json_encode($topic["name"]);
			$json .= ", max_score:".json_encode($topic["max_score"]).", number_questions:".json_encode($topic["number_questions"]).", subjects:";
			if(count($topic["subjects"] > 0)){
				$json .= "[";
				$first_subject = true;
				foreach($topic["subjects"] as $subject_id => $s){
					if(!$first_subject)
						$json .= ", ";
					$first_subject = false;
					$json .= "{id: ".json_encode($subject_id).", name:".json_encode($s["name"]).", max_score:".json_encode($s["max_score"]);
					$json .= ", full_subject:".json_encode($s["full_subject"]);
					$json .= ", parts:[";
					if(count($s["parts"]) > 0){
						$first_part = true;
						foreach($s["parts"] as $part_id => $p){
							if(!$first_part)
								$json .= ", ";
							$first_part = false;
							$json .= "{id:".json_encode($part_id).", name:".json_encode($p["name"]);
							$json .= ", max_score:".json_encode($p["max_score"]).", index:".json_encode($p["index"])."}";
						}
					}
					$json .= "]}";
				}
				$json .= "]";
			} else
				$json .= "[]";
			$json .= "}";
		}
		return $json;
	}
	
	public function getAllJsonTopics($excluded_id = null){
		$all_ids = SQLQuery::create()
			->select("Exam_topic_for_eligibility_rule")
			->field("id")
			->execute_single_field();
		$all_ids_after_exclusion = array();
		if(isset($excluded_id)){
			foreach($all_ids as $id){
				if($id != $excluded_id)
					array_push($all_ids_after_exclusion,$id);
			}
		} else
			$all_ids_after_exclusion = $all_ids;
		$json = "[";
		$first = true;
		foreach($all_ids_after_exclusion as $id){
			if(!$first)
				$json .= ", ";
			$first = false;
			$json .= $this->getJsonExamTopicForEligibilityRules($id);
		}
		$json .= "]";
		return $json;
	}
	
	/**
	 * @param {array} ids
	 * @param {boolean} get_names. If true, in case of an unvalid applicant ID
	 * will get the last name of the applicant who has the same ID
	 */
	// public function validateNewApplicantIds($ids,$get_names,$all_ids = null;){
		// $all_valids = true;
		// if(!isset($all_ids))
			// $all_ids = $this->getAllApplicantIds();
		// $wrong_ids = array();
		// foreach($ids as $id){
			// if(in_array($id,$all_ids)){
				// $all_valids = false;
				// $error = array();
				// $error[0] = $id;
				// if($get_names){
					// $name = $this->getNameFromApplicantId($id);
					// array_push($error,$name);
				// }
				// array_push($wrong_ids,$error);
			// }
		// }
		// return array($all_valids,$wrong_ids);
	// }
	
	private function getNameFromApplicantId($applicant_id){
		return SQLQuery::create()
		->bypass_security()
		->select("Applicant")
		->field("last_name")
		->where_value("Applicant","applicant_id",$applicant_id)
		->execute_single_value();
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