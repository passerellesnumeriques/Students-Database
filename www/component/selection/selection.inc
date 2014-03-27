<?php
require_once 'SelectionJSON.inc';
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
	
class selection extends Component {
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
					if (SQLQuery::create()->select("SelectionCampaign")->where("id",$_COOKIE["selection_campaign"])->executeSingleRow() <> null){
						$this->setCampaignId($_COOKIE["selection_campaign"]);
					}
				} catch (Exception $e) {}
			}
		}
		if ($this->campaign_id <> null)
			SQLQuery::set_submodel("SelectionCampaign", $this->campaign_id);
	}
	
	public function getPluginImplementations() {
		require_once("SelectionOrganizationPlugin.inc");
		require_once("SelectionNewsPlugin.inc");
		require_once("SelectionApplicationPlugin.inc");
		require_once("ApplicantPeopleTypePlugin.inc");
		return array(
			new SelectionOrganizationPlugin(),
			new SelectionNewsPlugin(),
			new SelectionApplicationPlugin(),
			new ApplicantPeopleTypePlugin()
		);
	}
	
	/**
	 * @method getCampaignId
	 * @return attribute campaign_id
	 */
	public function getCampaignId(){
		return $this->campaign_id;
	}
	
	/**
	 * @method getCalendarId
	 * @return attribute calendar_id
	 */
	public function getCalendarId(){
		return $this->calendar_id;
	}
	
	/**
	 * @method setCampaignId
	 * @param $id
	 * Set the campaign_id attribute to $id, reset the config attribute, set the selection_campaign cookie,
	 * update the calendar_id attribute, and select the matching submodel
	 */
	public function setCampaignId($id){
		$this->campaign_id = $id;
		/* Reset the config */
		$this->config = null;
		setcookie("selection_campaign",$id,time()+365*24*60*60,"/dynamic/selection/");
		SQLQuery::set_submodel("SelectionCampaign", $this->campaign_id);
		/* Set the calendar id attribute */
		$this->calendar_id = SQLQuery::create()->select("SelectionCampaign")->field("calendar")->where("id",$id)->executeSingleValue();
	}
	
	/**
	 * @method createCampaign
	 * @param string $campaign_name
	 * Create a campaign in the database, set its config to the default values, set the campaign id, set its steps
	 * Create a calendar for the selection campaign
	 * @return integer | boolean $campaign_id the id of the campaign created, false if an error occured
	 */
	public function createCampaign($campaign_name){
		$campaign_id = null;
		$fields = array();
		$all_configs = include("config.inc");
		$all_steps = include("steps.inc");
		//check the rights
		if(!PNApplication::$instance->user_management->has_right("manage_selection_campaign",true)){
			PNApplication::error("You are not allowed to manage the selection campaigns");
			return;
		}
		/**
		 * Cannot do a transaction because we are inserting tables (submodel) 
		 */
		try{
			/* Create a calendar for this campaign */
			$calendar_id = PNApplication::$instance->calendar->createSharedCalendar($campaign_name,"FFA040","/static/selection/selection_16.png",array("can_access_selection_data"=>true),array("edit_information_session"=>true));
			
			/* if the calendar was properly created, continue */
			if($calendar_id <> null){
				$fields["name"] = $campaign_name;
				$fields["calendar"] = $calendar_id;
				$campaign_id = SQLQuery::create()->insert("SelectionCampaign",$fields);
				$this->setCampaignId($campaign_id);
				
				/* Insert the default config in the database */
				$default_config_rows = array();
				foreach($all_configs as $name => $data)
					array_push($default_config_rows,array(
						"name" => $name,
						"value" => json_encode($data[1]),
					));
				SQLQuery::create()->insertMultiple("SelectionCampaignConfig",$default_config_rows);
					
				/* Insert the default steps in the database */
				$rows_steps = array();
				foreach($all_steps as $name => $temp)
					array_push($rows_steps, array("name" => $name, "value" => false));
				SQLQuery::create()->insertMultiple("Steps",$rows_steps);
			}
		} catch (Exception $e) {
			PNApplication::error($e);
		}
		if(PNApplication::has_errors()){
			/**
			 * Manually RollBack
			 */
			if($calendar_id <> null)
				SQLQuery::create()->bypassSecurity()->removeKey("Calendar",$calendar_id);
			if($campaign_id <> null)
				SQLQuery::create()->bypassSecurity()->removeKey("SelectionCampaign",$campaign_id);
			return false;
		}
		return $campaign_id;
	}
	
	/**
	 * @method removeCampaign
	 * @param $id
	 * Remove the campaign matching with the given ID, and all the linked data. Set the campaign_id attribute to null
	 */
	public function removeCampaign($id){
		//check the rights
		if(!PNApplication::$instance->user_management->has_right("manage_selection_campaign",true)){
			PNApplication::error("You are not allowed to manage the selection campaigns");
			return;
		}
		/* Set the campaign id to null */
		$this->campaign_id = null;
		/* Set the calendar_id to null */
		$this->calendar_id = null;
		$campaign = SQLQuery::create()->select("SelectionCampaign")->whereValue("SelectionCampaign", "id", $id)->executeSingleRow();
		if ($campaign == null) {
			PNApplication::error("Invalid selection campaign");
			return;
		}
		SQLQuery::create()->removeKey("SelectionCampaign",$id);
		PNApplication::$instance->calendar->removeSharedCalendar($campaign["calendar"]);
		// TODO remove all news having the tag campaignX
	}
	
	/**
	 * @method renameCampaign
	 * @param integer $id
	 * @param string $name
	 * Set the name of the given campaign (id) to the given value (name) in the database
	 */
	public function renameCampaign($id, $name){
		//check the rights
		if(!PNApplication::$instance->user_management->has_right("manage_selection_campaign",true)){
			PNApplication::error("You are not allowed to manage the selection campaigns");
			return;
		}
		SQLQuery::create()->updateByKey("SelectionCampaign",$id,array("name"=>$name));
	}
	
	/**
	 * @method getConfig
	 * @return array of arrays("name"=>, "value"=>)
	 * If the config attribute is set to null, will get its values in the database and set the config attribute
	 */
	public function getConfig(){
		if($this->config <> null) {return $this->config;}
		else{
			/* Check if it is set in the database */
			try{
				$q = SQLQuery::create()
					->select("SelectionCampaignConfig")
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
	 * Get the value of the given config attribute
	 * @param string $name
	 * @return boolean
	 */
	public function getOneConfigAttributeValue($name){
		$conf = $this->getConfig();
		for($i = 0; $i < count($conf); $i++){
			if($conf[$i]["name"] == $name)
				return $conf[$i]["value"];
		}
	}
	/**
	 * @method saveConfig
	 * @param $configs an array [name => value]
	 * After updating the config into the database, the config attribute is reseted to null.
	 * This way when a component requires the config attribute, the getConfig method will be forced to get the last version in the database
	 * @return in the case of an error is launched by the database, the exception is catched and returned. Otherwise, null is returned
	 */
	public function saveConfig($configs, $db_lock){
		$to_return = null;
		try{
			foreach($configs as $name=>$value){
				SQLQuery::create()->updateByKey("SelectionCampaignConfig", $name, array("value"=>$value),$db_lock);
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
	 * @return array [["step_name"] => value,...]
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
	
	/**
	 * Update all the steps values field in the database according to the data set into the db
	 * This method must be called every time a saving / remove method is called
	 */
	public function updateAllSteps(){
		$all_steps = include("steps.inc");
		$steps = $this->getSteps();
		foreach($steps as $name => $value){
		$r = SQLQuery::create()
			->bypassSecurity() //mandatory
			->select($all_steps[$name][1])
			->count() //just check if empty or not, no need of more data
			->executeSingleValue();
			if($r <> null && $r != 0 && $r != "0"){
				if(!$steps[$name]) //must be updated
					SQLQuery::create()
						->bypassSecurity()
						->updateByKey("Steps",$name, array('value' => true));
			} else {
				if($steps[$name]) //must be updated
					SQLQuery::create()
						->bypassSecurity()
						->updateByKey("Steps",$name, array('value' => false));
			}
		}
	}
	
	/**
	 * Check the possibility to update (valid/unvalid) a step, based on the steps dependencies
	 * If the given step is already validated, meaning that the user can potentialy unvalidate it,
	 * check all the dependencies that need the current step to be valid
	 * Else, the user can potentialy validate the step, so check that it is allowed by the dependencies
	 * This method must be called by each "save" and "remove" method to valid the call, as a last check
	 * Can also be called before creating a saving page such as ExamSubject to temporarly
	 * update the user rights
	 * @param string name the step name
	 * @return array	[0]: {boolean} true if can valid this step
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
	 * Check that the user can update a data, based on the user rights and on the step values
	 * This method don't consider the case of the steps can only restrict any rights,
	 * so is having a all-or-nothing basis
	 * @param string $step_name
	 * @param string $right_name the right name that must be ok for updating the data
	 * @param string $error_message_if_no_right error message added to the errors list in case of missing right_name
	 * @return array	<ul><li>[0]: boolean <code>$can_update</code> true if steps and rights for user_management ok</li>
	 * 					<li>[1]: string <code>$error_messages</code> the list of errors to display (error from rights & errors from steps)</li></ul>
	 */
	public function canUpdateFromRightAndStep($step_name, $right_name, $error_message_if_no_right){
		$error_messages = "";
		$can_update = true;
		$first_error = true;
		// check the steps
		$validation_steps = $this->canUpdateStep($step_name);
		
		if(!$validation_steps[0]){
			$can_update = false;
			$first_error = false;
			$error_messages .= $validation_steps[2];
		} else if(!$validation_steps[1]){
			//Maybe the dependencies give no restriction. If any restriction is given, cannot update this step (all-or-nothing)
			$add = true;
			$edit = true;
			$remove = true;
			$this->updateRightsFromStepsDependenciesRestrictions($step_name,$add,$remove,$edit);
			if(!$add || !$remove || !$edit){//The rights have been restricted
				$can_update = false;
				$first_error = false;
				$error_messages .= $validation_steps[2];
			}
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
	 * Get the user rights from user_management and handle the restrictions due to the steps
	 * The user rights can only be temporarly restricted (never increased) since the first values comes from
	 * user_management
	 * @param string step_name
	 * @param string remove_right_name the right name for user_management component
	 * @param string edit_right_name the right name for user_management component
	 * @param string remove_right_name the right name for user_management component
	 * @return array 	[0]: array rights values (add =>, remove=>, edit =>)
	 *					[1]: boolean true if the rights were restricted
	 *					[2]: string restriction message
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
	 * @param boolean can_add
	 * @param boolean can_remove
	 * @param boolean can_edit
	 * This method restricts (but never increase) the given rights according to the steps dependencies restrictions
	 * The rights given as parameters shall be coming from user_management functionalities
	 * @return  array 	[0]:boolean restricted, true if the steps dependencies have restricted the rights
	 *					[1]: string restriction message
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
			//we cannot unvalid this step, and the forbidden actions on ExamSubject table are given in validation_step
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
	 * Get all the campaigns set in the database and return an array to be used by a php script
	 * @param boolean (optional) asc true if the campaigns must be ordered by an ascendant order
	 * @return array [[id=>"",name=>""],[id=>"",name=>""],...]
	 */
	public function getCampaigns($asc = false){
		try{
			$campaigns = SQLQuery::create()
				->select("SelectionCampaign")
				->field("id")
				->field("name")
				->orderBy("SelectionCampaign","id",$asc)
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
	 * Update or create an InformationSession into the database
	 * The rights must be checked before calling this method
	 * @param integer $id the id of the IS in case of an update
	 * @param array the fields_values to update/insert into the InformationSession table
	 * @return $id the id generated in case of an insert
	 */
	public function saveIS($id, $fields_values){
		try {
			if(isset($id)){
				// This is an update
				SQLQuery::create()->updateByKey("InformationSession",$id,$fields_values);
				
			} else {
				// This is an insert
				$id = SQLQuery::create()->insert("InformationSession",$fields_values);
			}
		} catch (Exception $e) {
			PNApplication::error($e);
		}
		//update the steps
		$this->updateAllSteps();
		return $id;
	}
	
	/**
	 * Save partners and contact points for a partnership datamodel defined as information sessions one:
	 * Must have a table $table_name."Partner" (InformationSessionPartner) and a table $table_name."ContactPoint" (InformationSessionContactPoint)
	 * To perform the saving, this method will remove all the rows in both tables and insert new ones
	 * @param number $id the $id value of the $field_name entity to which the partners and contact points are updated (information session ID)
	 * @param array $rows_partner array containing the rows to be saved into the $table_name."Partner" table 
	 * @param array $rows_contact_point array containing the rows to be saved into the $table_name."ContactPoint" table
	 * @param string $table_name the prefix to add to "Partner" and "ContactPoint" to get the complete table name (InformationSession)
	 * @param string $field_name the field name of the foreignkey in $table_name."Partner" and $table_name."ContactPoint" tables, representing the entity (information_session)
	 */
	public function savePartnersAndContactsPoints($id, $rows_partner, $rows_contact_point,$table_name, $field_name){	
		$transation_started_before = SQLQuery::isTransactionStarted();//This method may be called during a transaction
		if(!$transation_started_before)
			SQLQuery::startTransaction();
		try {		
			//Remove all the partners and contacts points already set
			$this->removeAllPartners($id,$table_name,$field_name);
			$this->removeAllContactPoints($id,$table_name,$field_name);
			//Insert the new ones
			if(count($rows_partner) > 0)
				$this->insertPartners($rows_partner,$table_name);
			if(count($rows_contact_point) > 0)
				$this->insertContactPoints($rows_contact_point,$table_name);
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(!$transation_started_before && PNApplication::has_errors())
			SQLQuery::rollbackTransaction();
		else if(!$transation_started_before && !PNApplication::has_errors())
			SQLQuery::commitTransaction();
		//Else the method that had started the transaction must know how to handle the errors, or commit, so nothing is done
	}
	
	/**
	 * Remove all the partners of a given entity
	 * @param number $id entity ID
	 * @param string $table_name prefix to add to "Partner" to get the right partner table name
	 * @param string $field_name the field name of the foreignkey in $table_name."Partner" table, representing the entity (information_session)
	 */
	private function removeAllPartners($id, $table_name, $field_name){
		$partners = SQLQuery::create()->select($table_name."Partner")
		->field("organization")
		->whereValue($table_name."Partner",$field_name,$id)
		->execute();
		if(isset($partners[0]["organization"])){
			foreach($partners as $p)
				SQLQuery::create()->removeKeys($table_name."Partner",array(array($field_name => $id, "organization" => $p["organization"])));
		}
	}
	
	/**
	 * Remove all the selected partners contact points of a given entity
	 * @param number $id entity ID
	 * @param string $table_name prefix to add to "ContactPoint" to get the right ContactPoint table name
	 * @param string $field_name the field name of the foreignkey in $table_name."ContactPoint" table, representing the entity (information_session)
	 */
	private function removeAllContactPoints($id, $table_name, $field_name){
		$contact_point = SQLQuery::create()->select($table_name."ContactPoint")
		->field("organization")
		->field("people")
		->whereValue($table_name."ContactPoint",$field_name,$id)
		->execute();
		if(isset($contact_point[0]["organization"])){
			foreach($contact_point as $c)
				SQLQuery::create()
				->removeKeys($table_name."ContactPoint",
						array(
								array($field_name => $id, "organization" => $c["organization"], "people" => $c["people"])
						));
		}
	}
	
	/**
	 * Insert rows into a partner table
	 * @param array $rows rows to be inserted
	 * @param string $table_name string $table_name prefix to add to "Partner" to get the right partner table name
	 */
	private function insertPartners($rows,$table_name){
		SQLQuery::create()->insertMultiple($table_name."Partner",$rows);
	}
	
	/**
	 * Insert rows into a contactpoint table
	 * @param array $rows rows to be inserted
	 * @param string $table_name string $table_name prefix to add to "ContactPoint" to get the right contactpoint table name
	 */
	private function insertContactPoints($rows,$table_name){
		SQLQuery::create()->insertMultiple($table_name."ContactPoint",$rows);
	}

	/**
	 * Remove an information session from the database
	 * @param integer id the one of the IS to remove
	 */
	public function removeIS($id){
		// check the rights
		if(!PNApplication::$instance->user_management->has_right("manage_information_session",true)){
			PNApplication::error("You are not allowed to remove any information session");
			return;
		}
		SQLQuery::create()->removeKey("InformationSession",$id);
		//update the steps
		$this->updateAllSteps();
		if(PNApplication::has_errors())
			return;
		else
			return true;
	}
	
	/**
	 * Check if a given IS has a host set
	 * This method is used by the IS status service so must use bypassSecurity
	 * @param number $IS_id
	 * @return boolean
	 */
	public function isHostSetToIS($IS_id){
		$has_host = SQLQuery::create()
				->bypassSecurity()
				->select("InformationSessionPartner")
				->field("host")
				->whereValue("InformationSessionPartner", "host", true)
				->whereValue("InformationSessionPartner", "information_session", $IS_id)
				->executeSingleValue();
		$has_host = $has_host == null ? false : $has_host;
		return $has_host;
	}
	
	/**
	 * Get all the IS with no host set
	 * This method is used by the IS status service so must use bypassSecurity
	 * @return array | null if any IS has no host, an array is returned containing arrays: array(id=>, name=>)
	 */
	public function getAllISWithNoHost(){
		$all_IS = SQLQuery::create()
			->bypassSecurity()
			->select('InformationSession')
			->field("InformationSession","id")
			->field("InformationSession","name")
			->execute();
		$IS_with_no_host = array();
		if($all_IS <> null){
			foreach ($all_IS as $is){
				if(!$this->isHostSetToIS($is["id"]))
					array_push($IS_with_no_host, $is);
			}
			$IS_with_no_host = count($IS_with_no_host) > 0 ? $IS_with_no_host : null;
			return $IS_with_no_host;
		} else 
			return null;
		
	}
	
	/**
	 * Get all the existing exam subjects from the database
	 * @return array [["id" => , "name" =>],...]
	 */
	public function getAllExamSubjects(){
		return SQLQuery::create()
				->select("ExamSubject")
				->field("ExamSubject","name","name")
				->field("ExamSubject","id","id")
				->execute();
	}
	
	/**
	 * Check if the questions of an exam subject can be updated,
	 * calling the selection#canUpdateFromRightAndStep method
	 * @return array:	[0]: boolean $can_update true if steps and rights for user_management ok
	 * 					[1]: string $error_messages the list of errors to display
	 */
	public function canManageExamSubjectQuestions(){
		return $this->canUpdateFromRightAndStep("manage_exam","manage_exam_subject","You are not allowed to manage the exam subject questions");
		}
		
	/**
	 * Remove an exam subject from database and all the related data
	 * This method checks the rights before performing the remove
	 * @param integer $exam_id
	 * @return boolean true if done
	 */
	public function removeSubject($exam_id){
		$parts = null;
		//check the rights
		$can_remove = $this->canManageExamSubjectQuestions();
		if($can_remove[0]){
			$parts = SQLQuery::create()
				->select("ExamSubjectPart")
				->field("id")
				->whereValue("ExamSubjectPart","id",$exam_id)
				->executeSingleField();
			try{
				//Before removing, get all the topics declared as full subject for this exam subject
				$topics_concerned = $this->getTopicsDeclaredFullSubjectForGivenExamSubject($exam_id);
				//Perform the remove
				SQLQuery::create()->bypassSecurity()->removeKey("ExamSubject",$exam_id);
				//In the topics concerned, remove the empty ones which are ONLY declared as full subject for this topic (for instance, case where a subject is created, and the topic full subject as well, then subject removed)
				if($topics_concerned <> null){
					foreach ($topics_concerned as $topic_id){
						if($this->isTopicEmpty($topic_id) && !$this->isTopicDeclaredFullSubjectForAnyOtherExamSubject($topic_id, $exam_id))
							//Remove it
							$this->removeTopic($topic_id);		
					}
				}
			} catch (Exception $e) {
				PNApplication::error($e);
			}
		} else
			PNApplication::error($can_remove[1]);
		if(PNApplication::has_errors())
			return false;
		else {
// 			//update the topics
// 			$this->updateExamTopicsForEligibilityRules($parts,null,$exam_id);
			//update steps
			$this->updateAllSteps();
			return true;
		}
	}
	
	/**
	 * Method called by the selection#saveSubject method
	 * After inserting the exam subject parts into the database, update the
	 * exam subject questions part_id attribute with the value got from the matching part insert
	 * @param array $parts_ids all the part ids to update
	 * @param array $questions_by_part 
	 * @param array $parts_indexes
	 * See selection#service#exam#save_subject for more details
	 * @return array the questions rows to insert into the database, using SQLQuery#insert_multiple method
	 */
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
	
	/**
	 * Save an exam subject: insert or update one in the database
	 * For more details about the parameters, see selection#service#exam#save_subject
	 * @param integer $exam_id
	 * @param array $rows_exam_table row to insert using SQLQuery::insert_multiple method
	 * @param array $rows_parts_table row to insert using SQLQuery::insert_multiple method
	 * @param array $questions_by_old_parts questions linked to parts that already existed in the database (updated)
	 * @param array $questions_by_new_part questions linked to parts that will be inserted
	 * @param array $parts_to_insert_indexes 
	 * @return boolean|string false if an error occured, else json exam subject object with the new values set into the database
	 */
	public function saveSubject($exam_id, $rows_exam_table, $rows_parts_table, $questions_by_old_parts ,$questions_by_new_part, $parts_to_insert_indexes){
		// Check the rights
		$can_update = $this->canManageExamSubjectQuestions();
		if($can_update[0]){
			SQLQuery::startTransaction();
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
					->insert("ExamSubject",$rows_exam_table);
				if($has_parts){
					// update parts exam_subject id
					foreach($rows_parts_table as &$p){
						$p["exam_subject"] = $exam_id;
						unset($p["id"]);
					}
					// save after checking that the parts to insert have no id
					$this->unsetPartsIdsIfExists($rows_parts_table);
					$new_parts = SQLQuery::create()
						->insertMultiple("ExamSubjectPart",$rows_parts_table);
					
					if($has_questions_from_new_parts){
						$rows_questions_table = $this->updatePartIdInQuestionsRows($new_parts,$questions_by_new_part,$parts_to_insert_indexes);
						// insert using bypassSecurity
						SQLQuery::create()
							->bypassSecurity()
							->insertMultiple("ExamSubjectQuestion",$rows_questions_table);
					}
				}
				// create a topic full_subject for this new subject, even if has no part yet (will be fulfilled automatically)
				$this->createTopicAndInsertFullExamSubject($exam_id);
			} else {
				// this is an update
				SQLQuery::create()
					->updateByKey("ExamSubject",$exam_id,$rows_exam_table);
				
				$had_parts = false;
				// get the old parts id and old questions id
				$old_parts = SQLQuery::create()
					->select("ExamSubjectPart")
					->field("id")
					->whereValue("ExamSubjectPart","exam_subject",$exam_id)
					->executeSingleField();
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
								->bypassSecurity()
								->select("ExamSubjectQuestion")
								->field("id")
								->whereIn("ExamSubjectQuestion","exam_subject_part",$parts_ids_to_update)
								->executeSingleField();
						if(count($questions_to_remove_from_parts_to_update) > 0)
							SQLQuery::create()
								->bypassSecurity()
								->removeKeys("ExamSubjectQuestion",$questions_to_remove_from_parts_to_update);
					}										
					//update the parts to update
					if(count($rows_parts_to_update) > 0){
						foreach($rows_parts_to_update as $row){
							SQLQuery::create()
								->updateByKey("ExamSubjectPart",$row["id"],array(
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
						//check there is no id remaining
						$this->unsetPartsIdsIfExists($rows_parts_to_insert);
						$new_parts_ids = SQLQuery::create()
							->insertMultiple("ExamSubjectPart",$rows_parts_to_insert);
					}
					
					//remove the parts to remove
					if(count($parts_to_remove) > 0)
						SQLQuery::create()
							->bypassSecurity()
							->removeKeys("ExamSubjectPart",$parts_to_remove);
				} else if($has_parts) {
					//all the parts must be inserted
					//Check no id remaining
					$this->unsetPartsIdsIfExists($rows_parts_table);
					$new_parts_ids = SQLQuery::create()
						->insertMultiple("ExamSubjectPart",$rows_parts_table);	
				}
				/** update the part id of the questions coming from new parts
				 * If !$had_parts, all the questions are in $questions_by_new_part
				 */
				if(isset($new_parts_ids)){
					$rows_questions_of_inserted_parts = $this->updatePartIdInQuestionsRows($new_parts_ids,$questions_by_new_part,$parts_to_insert_indexes);
					// insert these questions
					SQLQuery::create()
						->bypassSecurity()
						->insertMultiple("ExamSubjectQuestion",$rows_questions_of_inserted_parts);
					//Update the exams topics(insert part in full subject topics)
					$this->updateExamTopicsForEligibilityRules($new_parts_ids,$exam_id);
				}
				if(count($questions_by_old_parts) > 0){ //insert the remaining questions
					$rows_questions_from_old_parts = array();
					foreach($questions_by_old_parts as $part_questions){
						foreach($part_questions as $q)
							array_push($rows_questions_from_old_parts,$q);
					}					
					SQLQuery::create()
						->bypassSecurity()
						->insertMultiple("ExamSubjectQuestion",$rows_questions_from_old_parts);
				}
			}
			} catch (Exception $e){
				PNApplication::error($e);
			}
			if(PNApplication::has_errors())
				SQLQuery::rollbackTransaction(); //Rollback
			else
				SQLQuery::commitTransaction(); //Commit
		} else
			PNApplication::error_html($can_update[1]);
		if(PNApplication::has_errors())
			return false;
		else {
			//update steps
			$this->updateAllSteps();
			// return a new subject object
			return SelectionJSON::ExamSubjectFromID($exam_id);
		}
	}
	
	/**
	 * Unset the part id if set in the parts rows_fields_values array
	 * Method to be called before inserting any part, as a last check
	 * @param array $parts_rows_fields_values array(array(field => value, ...), array(field => value,...),...)
	 */
	private function unsetPartsIdsIfExists(&$parts_rows_fields_values){
		foreach ($parts_rows_fields_values as &$part){
			if(isset($part["id"]))
				unset($part["id"]);
		}
	}
	
	/**
	 * Get all the applicants ids already set into the database
	 * This method does not check the user rights
	 * @return array applicants ids
	 */
	private function getAllApplicantIds(){
		$ids = SQLQuery::create()
			->bypassSecurity()
			->select("Applicant")
			->field("applicant_id")
			->executeSingleField();
		return $ids;
	}
	
	/**
	 * Get all applicants ids already set into the database after
	 * checking the user rights
	 * @return array applicants ids
	 */
	public function externalGetAllApplicantsIds(){
		// check the rights
		if(PNApplication::$instance->user_management->has_right("see_applicant_info",true))
			return $this->getAllApplicantIds();
		else
			PNApplication::error("You are not allowed to access to the applicants informations");
	}
	/**
	 * Handle the consequences on the exam topics of saving an exam
	 * Perform the insertion of the parts if any topic is set as full subject for this exam
	 * @param array $parts_inserted containing the ids of the inserted parts in case the topic is a full subject one (added to the topic)
	 * @param number $exam_id the id of the exam updated
	 */	
	public function updateExamTopicsForEligibilityRules($parts_inserted,$exam_id){
// 		if($parts_removed <> null || $parts_inserted <> null){
		if($parts_inserted <> null){
			// find out the related topics for eligibility rules
// 			$topics_for_parts_removed = null;
// 			if($parts_removed <> null)
// 				$topics_for_parts_removed = $this->getTopicsConcernedByParts($parts_removed);
			$inserted = false;
// 			$topics_removed = false;
// 			$removed_because_updated_empty = true;
			// check if this subject is a "full topic" for any of the topic, and in that case insert the new parts
			$topics_to_be_inserted = $this->getTopicsDeclaredFullSubjectForGivenExamSubject($exam_id);
			if($topics_to_be_inserted <> null){
				//perform the inserts
				foreach($topics_to_be_inserted as $t){
					$rows_inserted = array();
					foreach($parts_inserted as $p)
						array_push($rows_inserted, array("exam_subject_part" => $p, "exam_topic_for_eligibility_rule" => $t));
					SQLQuery::create()
						->insertMultiple("ExamPartTopic",$rows_inserted);
				}
			}
// 			if($topics_for_parts_removed <> null){
// 				// check if any topic is now empty
// 				$topics_to_remove = array();
// 				foreach($topics_for_parts_removed as $t){
// 					if($this->isTopicEmpty($t))
// 						array_push($topics_to_remove,$t);
// 				}
// 				if(count($topics_to_remove) > 0){
// 					SQLQuery::create()
// 						->removeKeys("ExamTopicForEligibilityRule",$topics_to_remove);
// 					$topics_removed = true;
// 				}
// 			}
// 			return array($inserted,$topics_removed);
			return $inserted; 
		}
	}
	
	/**
	 * Get all the topics declared as full subject for the given exam subject
	 * @param integer $exam_id
	 * @return array | null null if no topics found, else array containing the matching topics ids
	 */
	private function getTopicsDeclaredFullSubjectForGivenExamSubject($exam_id){
		$topics = SQLQuery::create()
			->bypassSecurity()
			->select("ExamTopicFullSubject")
			->field("ExamTopicFullSubject","exam_topic_for_eligibility_rule","topic_id")
			->whereValue("ExamTopicFullSubject","exam_subject",$exam_id)
			->executeSingleField();
		return $topics;
	}
	
	/**
	 * Check if a given topic is declared as full subject for any other exam than the given one
	 * @param number $topic_id
	 * @param number $exam_id
	 * @return boolean true if declared as full subject for any other exam subject
	 */
	private function isTopicDeclaredFullSubjectForAnyOtherExamSubject($topic_id, $exam_id){
		$other_subject = SQLQuery::create()
			->bypassSecurity()
			->select("ExamTopicFullSubject")
			->field("ExamTopicFullSubject","exam_subject")
			->whereValue("ExamTopicFullSubject", "exam_topic_for_eligibility_rule", $topic_id)
			->whereNotIn("ExamTopicFullSubject", "exam_subject",array($exam_id))
			->executeSingleField();
		return $other_subject <> null;			
	}
	
	/**
	 * Check if a topic is empty
	 * @param integer $topic_id
	 * @return boolean
	 */
	private function isTopicEmpty($topic_id){
		$q = SQLQuery::create()
			->bypassSecurity()
			->select("ExamPartTopic")
			->count()
			->whereValue("ExamPartTopic","exam_topic_for_eligibility_rule",$topic_id)
			->executeSingleValue();
		if($q <> null){
			if($q == 0 || $q == "0")
				return true;
			else
				return false;
		} else
			return true;
	}
	
	/**
	 * Get all the topics that contain the given parts
	 * @param array $parts all the parts ids
	 * @return array | null array containing all the matching part ids, null if empty result
	 */
	private function getTopicsConcernedByParts($parts){
		if(isset($parts) && count($parts) > 0){
		$topics = SQLQuery::create()
			->bypassSecurity()
			->select("ExamPartTopic")
			->field("ExamPartTopic","exam_topic_for_eligibility_rule","topic")
			->whereIn("ExamPartTopic","exam_subject_part",$parts)
			->executeSingleField();
		return $topics;
		} else
			return null;
	}
	
	/**
	 * Create an exam topic for eligibility rules
	 * @param string $name
	 * @param array $parts_to_insert all the parts ids contained by the topic
	 * @param number | null (optional) $topic_max_score
	 * @param number | null (optional) $topic_number_questions
	 * @return number the id of the new topic
	 */
	public function createTopic($name, $parts_to_insert, $topic_max_score = null, $topic_number_questions = null){
		//insert the topic
		$id = SQLQuery::create()
			->insert("ExamTopicForEligibilityRule", array(
				"name" => $name,
				"max_score" => $topic_max_score,
				"number_questions" => $topic_number_questions
			));
		if($parts_to_insert <> null){
			//insert the parts
			$rows = array();
			foreach($parts_to_insert as $p)
				array_push($rows,array("exam_topic_for_eligibility_rule" => $id, "exam_subject_part" => $p));
			SQLQuery::create()
				->insertMultiple("ExamPartTopic",$rows);
		}
		//Check the values
			$this->checkTopicMaxScoreField($id);
			$this->checkTopicNumberQuestionsField($id);
		return $id;
	}
	
	/**
	 * Create a full subject topic for eligibility rules into the database
	 * for a given exam subject
	 * All the parts of this exam subject are inserted into the topic
	 * These topic and exam subject are inserted into the ExamTopicFullSubject table
	 * @param number $exam_id
	 * @return number the id of the new topic
	 */
	private function createTopicAndInsertFullExamSubject($exam_id){
		$parts_to_insert = SQLQuery::create()
			->bypassSecurity()
			->select("ExamSubjectPart")
			->field("ExamSubjectPart","id","id")
			->whereValue("ExamSubjectPart", "exam_subject", $exam_id)
			->executeSingleField();
		//Get the exam name
		$name = SQLQuery::create()
			->bypassSecurity()
			->select("ExamSubject")
			->field("name")
			->whereValue("ExamSubject","id",$exam_id)
			->executeSingleValue();
		if($name <> null){				
			$id = $this->createTopic($name, $parts_to_insert);
			//insert in the ExamTopicFullSubject table
			SQLQuery::create()->bypassSecurity()
				->insert("ExamTopicFullSubject",array("exam_subject" => $exam_id, "exam_topic_for_eligibility_rule" => $id));
			return $id;
		}
	}

	/**
	 * Save an exam topic and all the related data<br/>
	 * The rights and steps are checked before performing the query
	 * @param array $topic fields_values for ExamTopicForEligibilityRule table
	 * @param array $parts_to_add all the parts ids to add into the database
	 * @param array $parts_to_remove all the parts already set into the database that must be removed
	 * @param array $full_subjects all the full subject within the topic
	 * @param number $db_lock lock id for the table ExamTopicForEligibilityRule
	 * @return boolean|number false if an error occured, else the id of the topic saved
	 */
	public function saveTopic($topic, $parts_to_add, $parts_to_remove, $full_subjects, $db_lock){
		//Check the rights
		$rights = $this->canUpdateFromRightAndStep("define_topic_for_eligibility_rules", "manage_exam_subject", "You are not allowed to manage the exam topics for eligibility rules");
		$id = null;
		if(!$rights[0]){
			PNApplication::error_html($rights[1]);
			return false;
		} else {
			$parts_fields_values = array();
			$full_subject_field_values = array();
			
			SQLQuery::startTransaction();
			if($topic["id"] == -1 || $topic["id"] == "-1"){
				//This is an insert
				unset($topic["id"]);
				try {
					$id = SQLQuery::create()->insert("ExamTopicForEligibilityRule", $topic, $db_lock);
				} catch (Exception $e){
					PNApplication::error($e);
				}
				
			} else {
				//This is an update
				$id = $topic["id"];
				unset($topic["id"]);
				try {
					SQLQuery::create()->updateByKey("ExamTopicForEligibilityRule", $id, $topic);
					//Get and remove all the parts set into the database
					if($parts_to_remove <> null){
						foreach($parts_to_remove as $p)
							SQLQuery::create()->removeKeys("ExamPartTopic", array(array("exam_topic_for_eligibility_rule" => $id, "exam_subject_part" => $p)));
					}
					//Get and remove all the full subjects set into the database
					$full_subjects_to_remove = SQLQuery::create()
					->bypassSecurity()
					->select("ExamTopicFullSubject")
					->field("ExamTopicFullSubject","exam_subject","subject")
					->whereValue("ExamTopicFullSubject","exam_topic_for_eligibility_rule",$id)
					->executeSingleField();
					if($full_subjects_to_remove <> null){
						foreach($full_subjects_to_remove as $s)
							SQLQuery::create()->removeKeys("ExamTopicFullSubject", array(array("exam_topic_for_eligibility_rule" => $id, "exam_subject" => $s)));
					}
				} catch (Exception $e){
					PNApplication::error($e);
				}
			}
			//Insert all the new values
			foreach($parts_to_add as $p)
				array_push($parts_fields_values, array("exam_subject_part" => $p, "exam_topic_for_eligibility_rule" => $id));
			foreach ($full_subjects as $subject)
				array_push($full_subject_field_values, array("exam_subject" => $subject, "exam_topic_for_eligibility_rule" => $id));
			try {
				if(count($parts_fields_values) > 0)
					SQLQuery::create()->insertMultiple("ExamPartTopic", $parts_fields_values, $db_lock);
				if(count($full_subject_field_values) >0)
					SQLQuery::create()->insertMultiple("ExamTopicFullSubject", $full_subject_field_values, $db_lock);
				//Check the max score and questions fields
				$this->checkTopicMaxScoreField($id);
				$this->checkTopicNumberQuestionsField($id);
			} catch (Exception $e){
				PNApplication::error($e);
			}
			if(PNApplication::has_errors()){
				SQLQuery::rollbackTransaction();
				return false;
			} else {
				SQLQuery::commitTransaction();
				//update steps
				$this->updateAllSteps();
				return $id;
			}
		}
	}
	
	/**
	 * Remove an exam topic and all the related data<br/>
	 * The rights and steps are checked before performing the query</br>
	 * If removing this topic creates any empty eligibility rules, these rules are removed
	 * @param number $id
	 * @return boolean true if performed
	 */
	public function removeTopic($id){
		//Check the rights
		$rights = $this->canUpdateFromRightAndStep("define_topic_for_eligibility_rules", "manage_exam_subject", "You are not allowed to manage the exam topics for eligibility rules");
		if(!$rights[0]){
			PNApplication::error_html($rights[1]);
			return false;
		} else {
			try {
				SQLQuery::startTransaction();
				//Check if the topics is set in any rule
				$rules_ids_to_check = SQLQuery::create()
					->bypassSecurity()
					->select("EligibilityRuleExamTopic")
					->field("EligibilityRuleExamTopic","eligibility_rule")
					->whereValue("EligibilityRuleExamTopic","exam_topic_for_eligibility_rule",$id)
					->executeSingleField();
				//Remove the topic
				SQLQuery::create()
					->removeKey("ExamTopicForEligibilityRule",$id);
				//Check there is no empty eligibility rule
				if($rules_ids_to_check <> null){
					$rules_to_remove = array();
					foreach ($rules_ids_to_check as $rule_id){
						$other_topics_linked_to_this_rule = SQLQuery::create()
							->bypassSecurity()
							->select("EligibilityRuleExamTopic")
							->field("EligibilityRuleExamTopic","exam_topic_for_eligibility_rule")
							->whereValue("EligibilityRuleExamTopic","eligibility_rule",$rule_id)
							->executeSingleField();
						if($other_topics_linked_to_this_rule == null){ //There is no other topic linked to this rule, so we remove it
							SQLQuery::create()
								->bypassSecurity()
								->removeKey("EligibilityRule",$rule_id);
						}
					}
				}
			} catch (Exception $e){
				PNApplication:error($e);
			}
			if(PNApplication::has_errors()){
				SQLQuery::rollbackTransaction();
				return false;
			}
			SQLQuery::commitTransaction();
			//update steps
			$this->updateAllSteps();
			return true;
		}
	}
	
	/**
	 * Method to call before applying the eligibility rules<br>
	 * Check that all the exam subject parts appear one time and only one
	 * in the exam topics. By this check we are sure that <br/><ul>
	 * <li>No part has an implicite coefficient (case appears in several topics)</li>
	 * <li>No part is useless (case never appear in any topic)</li>
	 * <li>All the parts set in the topic are still in the ExamSubjectPart table. If no, the removePartsUnknownFromTopics method is called</li>
	 * </ul>
	 * @return null|string null if no error detected, else the error message to display
	 */
	public function validateAllTopicsForEligibilityRules(){
		$res = true;
		//get all the parts ids from ExamSubjectPart table
		$parts_from_exam_subject_parts = SQLQuery::create()
			->bypassSecurity()
			->select("ExamSubjectPart")
			->field("ExamSubjectPart","id","id")
			->executeSingleField();
		
		//get all the parts ids from ExamPartTopic table
		$parts_from_exam_part_topic = SQLQuery::create()
			->bypassSecurity()
			->select("ExamPartTopic")
			->field("ExamPartTopic","exam_subject_part","part")
			->executeSingleField();
		
		$parts_in_no_topic = array();
		$parts_unknown = array();
		$empty_topics = array();
		
		//check that all the parts set in the topic are still in the ExamSubjectPart table
		foreach ($parts_from_exam_part_topic as $p){
			if(!in_array($p, $parts_from_exam_subject_parts)){
				array_push($parts_unknown, $p);
			}
		}
		if($parts_unknown <> null){
			$parts_unknown_removed = $this->removePartsUnknownFromTopics($parts_unknown);
			if(!$parts_unknown_removed)
				$res = false;		
		}
		
		//check that each part is at least in one topic
		foreach ($parts_from_exam_subject_parts as $p){
			if(in_array($p, $parts_from_exam_part_topic)){
				$key = array_search($p, $parts_from_exam_part_topic);
				array_splice($parts_from_exam_part_topic, $key, 1);
			} else {
				array_push($parts_in_no_topic, $p);
				$res = false;
			}
		}
		
		//check that no part appears more than one time in the topics
		if(count($parts_from_exam_part_topic) > 0){
			//all the remaining parts appear more than one time in the topics
			$res = false;	
		}
		
		//Check that the topics are not empty
		$all_topics = SQLQuery::create()
			->bypassSecurity()
			->select("ExamTopicForEligibilityRule")
			->field("ExamTopicForEligibilityRule","id")
			->field("ExamTopicForEligibilityRule","name")
			->execute();
		if(count($all_topics) > 0){
			foreach ($all_topics as $topic){
				$parts_for_this_topic = SQLQuery::create()
					->bypassSecurity()
					->select("ExamPartTopic")
					->field("ExamPartTopic","exam_subject_part")
					->whereValue("ExamPartTopic","exam_topic_for_eligibility_rule",$topic["id"])
					->executeSingleField();
				if($parts_for_this_topic == null){
					array_push($empty_topics,$topic["name"]);
					$res = false;
				}
			}
		}
		
		if($res)
			return;
		else {
			$error_message = "<ul>";
			$error_multiple_entries = null;
			$error_missing = null;
			$error_unknown = null;
			$error_empty_topic = null;
			if(count($parts_in_no_topic) > 0){
				$error_missing = "The following parts don't appear in any exam topic:<br/><ul>";
				$missing_parts_data = SQLQuery::create()
					->bypassSecurity()
					->select("ExamSubjectPart")
					->field("ExamSubjectPart","index","part_index")
					->field("ExamSubjectPart","name","part_name")
					->join("ExamSubjectPart", "ExamSubject", array("exam_subject" => "id"))
					->field("ExamSubject","name","subject_name")
					->whereIn("ExamSubjectPart", "id", $parts_in_no_topic)
					->orderBy("ExamSubject","id")
					->orderBy("ExamSubjectPart","index")
					->execute();
				
				foreach ($missing_parts_data as $p){
					$error_missing .= "<li>Part ".$p["part_index"]." - ".$p["part_name"]." - ".$p["subject_name"]." subject</li>";
				}
				$error_missing .= "</ul>";
			}
			
			if(count($parts_from_exam_part_topic) > 0){
				$error_multiple_entries = "The following parts appear in several topics: <br/><ul>";
				$parts_multiple_data = SQLQuery::create()
					->bypassSecurity()
					->select("ExamSubjectPart")
					->field("ExamSubjectPart","index","part_index")
					->field("ExamSubjectPart","name","part_name")
					->join("ExamSubjectPart", "ExamSubject", array("exam_subject" => "id"))
					->field("ExamSubject","name","subject_name")
					->whereIn("ExamSubjectPart", "id", $parts_in_no_topic)
					->orderBy("ExamSubject","id")
					->orderBy("ExamSubjectPart","index")
					->join("ExamSubjectPart", "ExamPartTopic", array("id" => "exam_subject_part"))
					->join("ExamPartTopic", "ExamTopicForEligibilityRule", array("exam_topic_for_eligibility_rule" =>"id"))
					->field("ExamTopicForEligibilityRule","name","topic_name")
					->execute();
				foreach ($parts_multiple_data as $p){
					$error_multiple_entries .= "<li>Part ".$p["part_index"]." - ".$p["part_name"]." - ".$p["subject_name"]." subject, in topic ".$p["topic_name"]."</li>";
				}
				$error_multiple_entries .= "</ul>";
			}
			if(count($empty_topics) > 0){
				$error_empty_topic = "The following topics are empty (no part assigned): <br/><ul>";
				foreach ($empty_topics as $topic_name){
					$error_empty_topic .= "<li>".$topic_name."</li>";
				}
				$error_empty_topic .= "</ul>";
			}
			if($parts_unknown <> null && !$parts_unknown_removed)
				$error_message .= "<li>Some parts are set in the topics but don't exist anymore into the database</li>";
			if($error_missing <> null)
				$error_message .= "<li>".$error_missing."</li>";
			if($error_multiple_entries <> null)
				$error_message .= "<li>".$error_multiple_entries."</li>";
			if($error_empty_topic <> null)
				$error_message .= "<li>".$error_empty_topic."</li>";
			$error_message .= "</ul>";
			return $error_message;
		}
	}
	
	/**
	 * Remove all the parts set in some topics but don't exist in the ExamSubjectPart (removed whereas table ExamTopicForEligibilityRule was locked for instance)
	 * @param array $parts containing the part ids
	 * @param unknown $parts
	 * @return boolean false if any error occured
	 */
	private function removePartsUnknownFromTopics($parts){
		try {
			foreach ($parts as $p){
			//Get the topic ids in which the part appears
				$topics = SQLQuery::create()
					->bypassSecurity()
					->select("ExamTopicForEligibilityRule")
					->field("ExamTopicForEligibilityRule","id")
					->where("ExamTopicForEligibilityRule","exam_subject_part",$p)
					->executeSingleField();
				if($topics <> null){
					foreach ($topics as $t){
						SQLQuery::create()
							->bypassSecurity()
							->removeKeys("ExamTopicForEligibilityRule", array(array("exam_subject_part" => $p, "exam_topic_for_eligibility_rule" => $t)));
					}
				}
			}
		} catch (Exception $e){
			PNApplication::error($e);
			return false;
		}
		return true;
	} 
	
	/**
	 * Check that the max_score set in ExamTopicForEligibilityRule table complies with the reality
	 * If no, the value is updated
	 * @param number $id
	 */
	public function checkTopicMaxScoreField($id){
		$data = SQLQuery::create()
			->bypassSecurity()
			->select("ExamTopicForEligibilityRule")
			->join("ExamTopicForEligibilityRule","ExamPartTopic",array("id" => "exam_topic_for_eligibility_rule"))
			->join("ExamPartTopic","ExamSubjectPart",array("exam_subject_part" => "id"))
			->whereValue("ExamTopicForEligibilityRule","id",$id)
			->field("ExamTopicForEligibilityRule","max_score","topic_max_score")
			->field("ExamSubjectPart","max_score","part_max_score")
			->execute();
		$score_from_topic = floatval($data[0]["topic_max_score"]);
		$score_from_parts = 0;
		foreach($data as $d)
			$score_from_parts += floatval($d["part_max_score"]);
		if($score_from_parts <> $score_from_topic)//update the value
			$q = SQLQuery::create()
				->bypassSecurity()
				->updateByKey("ExamTopicForEligibilityRule",$id,array('max_score' => $score_from_parts));
	}
	
	/**
	 * Check that the number_questions set in ExamTopicForEligibilityRule table complies with the reality
	 * If no, the value is updated
	 * @param number $id
	 */
	public function checkTopicNumberQuestionsField($id){
		$data = SQLQuery::create()
			->bypassSecurity()
			->select("ExamTopicForEligibilityRule")
			->join("ExamTopicForEligibilityRule","ExamPartTopic",array("id" => "exam_topic_for_eligibility_rule"))
			->join("ExamPartTopic","ExamSubjectQuestion",array("exam_subject_part" => "exam_subject_part"))
			->whereValue("ExamTopicForEligibilityRule","id",$id)
			->field("ExamTopicForEligibilityRule","number_questions","topic_number_questions")
			->count("nb_from_questions")
			->execute();
		$nb_from_topic = floatval($data[0]["topic_number_questions"]);
		$nb_from_questions = floatval($data[0]["nb_from_questions"]);
		if($nb_from_questions <> $nb_from_topic)//update the value
			SQLQuery::create()
				->bypassSecurity()
				->updateByKey("ExamTopicForEligibilityRule",$id,array("number_questions" => $nb_from_questions));
	}
	
	/**
	 * Save the eligibility rules<br/>
	 * All the query are performed into a SQL transaction<br/>
	 * Check the rights from user_management and steps, and save all the rules data
	 * The elegibility rules can only be saved all together, not in an individual way, so this method will also performed the removed required
	 * @param array $rules_to_update array(rule1, rule2,...) Each rule array contains:<ul><li><code>rule_table</code> array field_values for EligibilityRule table</li><li><code>rule_topic_table_insert</code> array containing all the fields_values arrays to insert into the EligibilityRuleExamTopic table</li><li><code>rule_topic_table_update</code> array containing all the fields_values arrays to update into the EligibilityRuleExamTopic table</li><li><code>rule_topic_table_remove</code> array containing all the topics ids to remove from the EligibilityRuleExamTopic table</li></ul>
	 * @param array $rules_to_insert array(rule1, rule2,...) Each rule array contains:<ul><li><code>rule_table</code> array field_values for EligibilityRule table</li><li><code>rule_topic_table_insert</code> array containing all the fields_values arrays to insert into the EligibilityRuleExamTopic table</li></ul>
	 * @param number $db_lock the database lock for the EligibilityRule table
	 * @return boolean true if no error occured, else false
	 */
	public function saveRules ($rules_to_update, $rules_to_insert, $db_lock){
		//Check the rights
		$rights = $this->canUpdateFromRightAndStep("define_eligibility_rules", "manage_exam_subject", "You are not allowed to manage the eligibility rules");
		if(!$rights[0]){
			PNApplication::error_html($rights[1]);
			return false;
		} else {
			//Start the transaction
			SQLQuery::startTransaction();
			try {
				$root_rule_id = null;
				$rules_not_to_remove = array();
				//handle the rules to insert
				if(count($rules_to_insert) > 0){					
					foreach($rules_to_insert as $rule){
						$catch_root_id = false;
						if($rule["rule_table"]["parent"] == null)
							$catch_root_id = true;
						//Insert in EligibilityRule table
						$id = SQLQuery::create()->insert("EligibilityRule", $rule['rule_table'], $db_lock);
						if($catch_root_id)
							$root_rule_id = $id;
						//Update the rule id in the fields_values for EligibilityRuleExamTopic
						foreach ($rule["rule_topic_table_insert"] as &$r){
							$r["eligibility_rule"] = $id;
						}
						SQLQuery::create()->insertMultiple("EligibilityRuleExamTopic", $rule["rule_topic_table_insert"]);
						array_push($rules_not_to_remove,$id);
					}
				}
				//handle the rules to update
				if(count($rules_to_update) > 0){
					foreach($rules_to_update as $rule){
						if($rule["rule_table"]["parent"] == null)
							$root_rule_id = $rule["rule_table"]["id"];
						//Update the EligibilityRule table fields
						SQLQuery::create()->updateByKey("EligibilityRule", $rule["rule_table"]["id"], $rule["rule_table"], $db_lock);
						//Handle the data for EligibilityRuleExamTopic table
						if(count($rule['rule_topic_table_insert']) > 0){
							//perform the insert
							SQLQuery::create()->insertMultiple("EligibilityRuleExamTopic", $rule["rule_topic_table_insert"]);
						}
						if(count($rule["rule_topic_table_update"]) > 0){
							//Perform the updates
							foreach($rule["rule_topic_table_update"] as $topic){
								$topic_id = $topic["exam_topic_for_eligibility_rule"];
								unset($topic["exam_topic_for_eligibility_rule"]); //We will only update the coefficients and expected fields
								SQLQuery::create()->updateByKeys("EligibilityRuleExamTopic", array(
									array(
										array(
											"eligibility_rule" => $rule["rule_table"]["id"],
											"exam_topic_for_eligibility_rule"  => $topic_id
										),
										$topic
									), 
								));
							}
						}
						if(count($rule["rule_topic_table_remove"]) > 0){
							//Perform the removes
							foreach ($rule["rule_topic_table_remove"] as $topic_id){
								SQLQuery::create()->removeKeys("EligibilityRuleExamTopic", array(
									array(
										"eligibility_rule" => $rule["rule_table"]["id"],
										"exam_topic_for_eligibility_rule"  => $topic_id
									)));
							}
						}
						array_push($rules_not_to_remove, $rule["rule_table"]["id"]);
					}
				}
				if($root_rule_id <> null){
					//Once the inserts and the updates are done, update the parent fields
					$not_root_rules_to_update = SQLQuery::create()
						->select("EligibilityRule")
						->field("EligibilityRule","id")
						->whereNotIn("EligibilityRule", "id", array($root_rule_id))
						->executeSingleField();
					foreach ($not_root_rules_to_update as $rule_id){
						SQLQuery::create()->updateByKey("EligibilityRule", $rule_id, array("parent" => $root_rule_id), $db_lock);
					}
				}
				//handle the rules to remove
				$rules_to_remove = array();
				$rules_in_db = SQLQuery::create()
					->bypassSecurity()
					->select("EligibilityRule")
					->field("EligibilityRule","id")
					->executeSingleField();
				if($rules_in_db <> null){
					foreach ($rules_in_db as $rule){
						if(!in_array($rule, $rules_not_to_remove)){
							array_push($rules_to_remove, $rule);
						}
					}
				}
				if(count($rules_to_remove) > 0){
					//perform the remove
					foreach ($rules_to_remove as $rule){
						SQLQuery::create()
							->removeKey("EligibilityRule", $rule);
					}
				}
			} catch (Exception $e){
				PNApplication::error($e);
			}
			if(PNApplication::has_errors()){
				SQLQuery::rollbackTransaction();
				return false;
			} else {
				SQLQuery::commitTransaction();
				//update steps
				$this->updateAllSteps();
				return true;
			}
		}
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
	
	/**
	 * Get all the information sessions not linked to any exam center, and having a host partner set
	 * @return array|null array containing the data retrieved from the database (ISData of the information sessions not linked), else null if no free IS
	 */
	public function getAllISWithHostAssignedNotLinkedToEC(){
		$linked_IS = SQLQuery::create()
			->select("ExamCenterInformationSession")
			->field("ExamCenterInformationSession","information_session")
			->distinct()
			->executeSingleField();
		
		$q = SQLQuery::create()
			->select("InformationSession")
			->join("InformationSession", "InformationSessionPartner", array("id" => "information_session"))
			->whereValue("InformationSessionPartner", "host", true);
		
		SelectionJSON::InformationSessionSQL($q);
		if($linked_IS <> null){
			$q
				->join("InformationSession","ExamCenterInformationSession", array("id" => "information_session"))
				->whereNotIn("InformationSession","id",$linked_IS);
		}
		return $q->execute();
	}
	
	/**
	 * Unlink an information session from an exam center
	 * @param number $EC_id exam center id
	 * @param number $IS_id information session id
	 * @return array <ul><li>true if well performed, else false</li><li>NULL | rows of the applicants that cannot be removed because assigned to exam session</li><li>NULL | rows of the applicants that cannot be removed because assigned to exam center room</li></ul>
	 */
	public function unlinkISfromEC($EC_id, $IS_id){
		SQLQuery::startTransaction();
		try {
			SQLQuery::create()
				->removeKeys("ExamCenterInformationSession", array(array("information_session" => $IS_id, "exam_center" => $EC_id)));
			//Unassign the applicants
			$applicants_to_unassign = SQLQuery::create()
				->select("Applicant")
				->field("Applicant","people")
				->whereValue("Applicant","information_session",$IS_id)
				->whereNull("Applicant","exam_session")
				->whereNull("Applicant","exam_center_room")
				->executeSingleField();
			$cannot_be_removed_session = SQLQuery::create()
				->select("Applicant")
				->whereValue("Applicant", "information_session", $IS_id)
				->whereNotNull("Applicant", "exam_session");
			SelectionJSON::ApplicantSQL($cannot_be_removed_session);
			$cannot_be_removed_session = $cannot_be_removed_session ->execute();
			$cannot_be_removed_room = SQLQuery::create()
				->select("Applicant")
				->whereValue("Applicant", "information_session", $IS_id)
				->whereNotNull("Applicant", "exam_session");
			SelectionJSON::ApplicantSQL($cannot_be_removed_room);
			$cannot_be_removed_room = $cannot_be_removed_room ->execute();
			if($applicants_to_unassign <> NULL){
				SQLQuery::create()
					->bypassSecurity()
					->updateAllKeys("Applicant", $applicants_to_unassign, array("exam_center" => NULL));
			}
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(PNApplication::has_errors()){
			SQLQuery::rollbackTransaction();
			return array(false,null,null);
		} else {
			SQLQuery::commitTransaction();
			return array(true,$cannot_be_removed_session,$cannot_be_removed_room);
		}
	}
	
	/**
	 * Link one or several information sessions to any exam center
	 * @param number $EC_id exam center id
	 * @param number | array $IS_ids information session id, or array of ids
	 * @param number $lock_ECIS lock of the table ExamCenterInformationSession
	 * @return boolean true if well performed, else false
	 */
	public function linkIStoEC($EC_id, $IS_ids,$lock_ECIS){
		SQLQuery::startTransaction();
		try{
			if(is_array($IS_ids)){
				$rows = array();
				foreach ($IS_ids as $id)
					array_push($rows, array("exam_center" => $EC_id, "information_session" => $id));
				SQLQuery::create()->insertMultiple("ExamCenterInformationSession", $rows,$lock_ECIS);
			} else {
				SQLQuery::create()->insert("ExamCenterInformationSession", array("exam_center" => $EC_id, "information_session" =>$IS_ids),$lock_ECIS);
			}
			//Assign the students to this center
			if(is_array($IS_ids)){
				foreach ($IS_ids as $id)
					$this->internalAssignApplicantsFromISToEC($id, $EC_id);
			} else {
				$this->internalAssignApplicantsFromISToEC($IS_ids, $EC_id);
			}
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(PNApplication::has_errors()){
			SQLQuery::rollbackTransaction();
			return false;
		} else {
			SQLQuery::commitTransaction();
			return true;
		}
	}
	
	/**
	 * Set an organization as host for an exam center, from an information session host partner
	 * The host is added to the table InformationSessionPartner as host with the same host_address, and all the contact points linked to this partner are also copied
	 * @param number $EC_id exam center id
	 * @param number $IS_host information session id
	 * @param boolean $update_name true if the name of the exam center must be updated with the new geographic area text (the one from host address)
	 * @param number | null $lock_exam_center lock id for the ExamCenter table. Only used in the case of update_name == true
	 * @return boolean true if well performed, else false
	 */
	public function setECHostFromIS($EC_id,$IS_host,$update_name = false, $lock_exam_center = null){
		SQLQuery::startTransaction();
		try {
			//Select all the data related to the host into the database
			$q_partner_data = SQLQuery::create()
				->select("InformationSessionPartner")
				->join("InformationSessionPartner","InformationSessionContactPoint",array("information_session"=>"information_session","organization"=>"organization"))
				->whereValue("InformationSessionPartner","information_session",$IS_host)
				->whereValue("InformationSessionPartner", "host", true);
			SelectionJSON::ISPartnersSQL($q_partner_data);
			$partner_data = $q_partner_data->execute();
			if($partner_data <> null){
				//Prepare the insert into the DB
				$area_id = PNApplication::$instance->contact->getAddressGeographicAreaAttribute($partner_data[0]["host_address"]);
				SQLQuery::create()->updateByKey('ExamCenter', $EC_id, array("geographic_area" => $area_id));
				$row_partner_table = array(
						"organization" => $partner_data[0]["organization"],
						"host" => true,
						"host_address" => $partner_data[0]["host_address"],
						"exam_center" => $EC_id
				);
				SQLQuery::create()->insert("ExamCenterPartner", $row_partner_table);
				if($partner_data[0]["contact_point"] <> null){
					//rows contacts points
					$rows_cp = array();
					foreach ($partner_data as $p){
						array_push($rows_cp, array(
							"exam_center" => $EC_id,
							"organization" => $p["organization"],
							"people" => $p["contact_point"]
						));
					}
					SQLQuery::create()->insertMultiple("ExamCenterContactPoint", $rows_cp);
				}
				
				if($partner_data[0]["host_address"] <> null && $update_name){
					//set the center name with the host address data
					$name = PNApplication::$instance->geography->getGeographicAreaText($area_id);
					if($name <> null)
						SQLQuery::create()
						->updateByKey("ExamCenter",$EC_id,array("name" => $name),$lock_exam_center);
				}				
			}
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(PNApplication::has_errors()){
			SQLQuery::rollbackTransaction();
			return false;
		} else {
			SQLQuery::commitTransaction();
			return true;
		}
	}
	
	/**
	 * Create an exam center from an information session with a host set, and set this host has the exam center host also
	 * @param number $IS_id
	 * @param string | null $name if the name shall be customized
	 * @param string | null $lock_id the lock id of the ExamCenter table 
	 * @return boolean|number false if not well performed, else the new exam center id
	 */
	public function createExamCenterFromISWithHost($IS_id, $name = null, $lock_id = null){
		try {
			$continue = true;
			//The name will be updated by setECHostFromIS method
			$host_address = SQLQuery::create()
				->bypassSecurity()
				->select("InformationSessionPartner")
				->field('InformationSessionPartner',"host_address")
				->whereValue("InformationSessionPartner", 'host', true)
				->whereValue("InformationSessionPartner", "information_session", $IS_id)
				->executeSingleValue();
			$area_id = PNApplication::$instance->contact->getAddressGeographicAreaAttribute($host_address);
			$name = PNApplication::$instance->geography->getGeographicAreaText($area_id);
			//Start the transaction
			SQLQuery::startTransaction();
			$id = SQLQuery::create()
				->insert("ExamCenter", array(
					"name" => $name,
					"geographic_area" => $area_id
				),$lock_id);
			
			
		} catch (Exception $e){
			PNApplication::error($e);
			$continue = false;
		}
		if($continue){
			//Add the host
			$this->setECHostFromIS($id, $IS_id, false, $lock_id);
		}
		if(PNApplication::has_errors()){
			SQLQuery::rollbackTransaction();
			$this->updateAllSteps();
			return false;
		} else {
			SQLQuery::commitTransaction();
			$this->updateAllSteps();
			return $id;
		}
	}
	
	/**
	 * Get all the EC with no host set
	 * This method is used by EC status service so must use bypassSecurity
	 * @return array | null if any EC has no host, an array is returned containing arrays: array(id=>, name=>)
	 */
	public function getAllECWithNoHost(){
		$all_EC = SQLQuery::create()
		->bypassSecurity()
		->select('ExamCenter')
		->field("ExamCenter","id")
		->field("ExamCenter","name")
		->execute();
		$EC_with_no_host = array();
		if($all_EC <> null){
			foreach ($all_EC as $ec){
				if(!$this->isHostSetToEC($ec["id"]))
					array_push($EC_with_no_host, $ec);
			}
			$EC_with_no_host = count($EC_with_no_host) > 0 ? $EC_with_no_host : null;
			return $EC_with_no_host;
		} else
			return null;
	
	}
	
	
	/**
	 * Check if a given EC has a host set
	 * This method is used by EC status service so must use bypassSecurity
	 * @param number $EC_id
	 * @return boolean
	 */
	public function isHostSetToEC($EC_id){
		$has_host = SQLQuery::create()
		->bypassSecurity()
		->select("ExamCenterPartner")
		->field("host")
		->whereValue("ExamCenterPartner", "host", true)
		->whereValue("ExamCenterPartner", "exam_center", $EC_id)
		->executeSingleValue();
		$has_host = $has_host == null ? false : $has_host;
		return $has_host;
	}
	
	/**
	 * Check if any applicant is assigned to the given exam center room
	 * @param number $id the exam center room ID
	 * @return boolean
	 */
	public function isAnyApplicantAssignedToRoom($id){
		$assigned = SQLQuery::create()
			->bypassSecurity()
			->select("Applicant")
			->field("Applicant","applicant_id")
			->whereValue("Applicant", "exam_room", $id)
			->executeSingleField();
		$assigned = $assigned == null ? false : true;
		return $assigned;
	}
	
	/**
	 * Get the applicants assigned to the given exam center rooms
	 * @param array $ids all the campaign ids
	 * @return array associative array(room_id => array of Applicants data if any, else NULL)
	 */
	public function getApplicantsAssignedToRooms($ids){
		$applicants = array();
		foreach($ids as $id){
			$applicants[$id] = $this->getApplicantsAssignedToCenterEntity(null,null,$id);
		}
		return $applicants;
	}
	
	/**
	 * Check that a room can be removed without any consequence on the applicants assignment
	 * First check that no applicant is assigned to this room in any exam session
	 * Then check that without this room, the center capacity still complies with the number of applicants assigned to the exam sessions in this center
	 * @param number $room_id exam center room ID
	 * @return array with three attributes:<ul><li>[0]: boolean, true if the room can be removed</li><li>[1]: NULL | array with the rows of the applicants assigned to the room</li><li>[2]: NULL | associative array: sessions IDs are the keys (and set only when removing the room would create a problem on the center capacity), the value is the number of applicants assigned to this session</ul></li></ul>
	 */
	public function canRoomBeRemovedWithoutConsequencesOnApplicantsAssignment($room_id){
		$can = true;
		$error_applicants = null;
		$error_capacity = null;
		//Check that there is no applicant assigned
		$assigned_to_room = SQLQuery::create()
			->bypassSecurity()
			->select("Applicant")
			->whereValue("Applicant","exam_center_room",$room_id);
		SelectionJSON::ApplicantSQL($assigned_to_room);
		$assigned_to_room = $assigned_to_room->execute();
		if($assigned_to_room <> null)
			$can = false;
		$error_applicants = $assigned_to_room;
		
		//Check that the room capacity wont affect the applicants assigned per session
		$room_data = SQLQuery::create()->bypassSecurity()->select("ExamCenterRoom")->whereValue("ExamCenterRoom", "id", $room_id)->executeSingleRow();
		$EC_id = $room_data["exam_center"];
		$room_capacity = $room_data["capacity"];
		$center_capacity = $this->getExamCenterCapacity($EC_id);
		$sessions_in_center = SQLQuery::create()->select("ExamSession")
			->whereValue("ExamSession", "exam_center", $EC_id);
		SelectionJSON::ExamSessionSQL($sessions_in_center);
		$sessions_in_center = $sessions_in_center ->execute();
		if($sessions_in_center <> null){
			$number_applicants_assigned_per_session = array();
			foreach ($sessions_in_center as $session){
				$count = SQLQuery::create()
					->bypassSecurity()
					->select("Applicant")
					->count()
					->whereValue("Applicant", "exam_session", $session["event"])
					->executeSingleValue();
				$count = $count == null ? 0 : $count;
				$number_applicants_assigned_per_session[$session["event"]] = $count;
			}
			foreach ($number_applicants_assigned_per_session as $session_id => $count_assigned){
				if($center_capacity - $room_capacity < $count_assigned){
					$can = false;
					if($error_capacity == null) $error_capacity = array();
					$error_capacity[$session_id] = $count_assigned;
				}
			}
		}
		return array($can, $error_applicants, $error_capacity);
	}
	public function saveExamCenter($id, $row_center, $rows_partners, $rows_contacts_points, $rooms_ids_to_remove, $rooms_to_update, $rooms_to_insert){
		//Check the rights
		$rights = $this->canUpdateFromRightAndStep("exam_center", "manage_exam_center", "You are not allowed to manage the exam centers");
		if(!$rights[0]){
			PNApplication::error_html($rights[1]);
			return false;
		} else {
			//Start the transaction
			SQLQuery::startTransaction();
			$warning_not_all_rooms_removed = false;
			try{
				if($id == null){
					//This is an insert
					$id = SQLQuery::create()
						->insert("ExamCenter", $row_center);
					//Update all the linked data
					foreach ($rows_partners as &$row){
						$row["exam_center"] = $id;
					}
					foreach($rows_contacts_points as &$row){
						$row["exam_center"] = $id;
					}
					foreach ($rooms_to_insert as &$room){
						$room["exam_center"] = $id;
					}
					foreach ($rooms_to_update as &$room){
						$room["exam_center"] = $id;
					}
				} else {
					//This is an update
					SQLQuery::create()
						->updateByKey("ExamCenter", $id, $row_center);
					//Update the rooms to update
					if(count($rooms_to_update) > 0){
						foreach($rooms_to_update as $room){
							$room_id = $room["id"];
							unset($room["id"]);
							SQLQuery::create()
								->updateByKey("ExamCenterRoom",$room_id, $room);
						}
					}
					//Remove the rooms to remove, after checking that no applicant is assigned
					if(count($rooms_ids_to_remove) > 0){
						$can_really_be_removed = array();
						foreach ($rooms_ids_to_remove as $room_id){
							if(!$this->isAnyApplicantAssignedToRoom($room_id))
								array_push($can_really_be_removed, $room_id);
							else
								$warning_not_all_rooms_removed = true;
						}
						if(count($can_really_be_removed) > 0){
							SQLQuery::create()
								->removeKeys("ExamCenterRoom", $can_really_be_removed);
						}
					}
				}
				//Still have to insert the rooms to insert
				if(count($rooms_to_insert) > 0){
					SQLQuery::create()->insertMultiple("ExamCenterRoom", $rooms_to_insert);
				}
				//Save the partnership data
				$this->savePartnersAndContactsPoints($id, $rows_partners, $rows_contacts_points, "ExamCenter", "exam_center");
			}  catch (Exception $e){
				PNApplication::error($e);
			}
			if(PNApplication::has_errors()){
				SQLQuery::rollbackTransaction();
				return false;
			} else {
				SQLQuery::commitTransaction();
				if($warning_not_all_rooms_removed)
					PNApplication::warning("Some rooms have not been removed because they have applicants assigned");
				$this->updateAllSteps();
				return $id;
			}
		}
	}
	
	/**
	 * Remove an exam center from its ID
	 * First the user rights are checked, according to user_management and steps restrictions
	 * Then this method checks that no room of this center has any applicant assigned: in that case nothing is done
	 * The remove query is performed within a transaction
	 * @param number $id Exam Center ID
	 * @return boolean true if well performed, else false
	 */
	public function removeExamCenter($id){
		//Check the right from steps and user management
		$rights_and_steps = $this->getRestrictedRightsFromStepsAndUserManagement("exam_center", "manage_exam_center", "manage_exam_center", "manage_exam_center");
		if(!$rights_and_steps[0]["remove"]){
			if($rights_and_steps[1])
				PNApplication::warning($rights_and_steps[2]);
			PNApplication::error("You are not allowed to remove this Exam Center");
			return false;
		}
		//Check that no room has any applicant assigned
		$rooms = SQLQuery::create()
			->bypassSecurity()
			->select("ExamCenterRoom")
			->field("ExamCenterRoom","id")
			->whereValue("ExamCenterRoom", "exam_center", $id)
			->executeSingleField();
		if($rooms <> null){
			$may_be_assigned = $this->getApplicantsAssignedToRooms($rooms);
			$assigned = false;
			foreach ($may_be_assigned as $applicant_assigned){
				if($applicant_assigned <> null){
					$assigned = true;
					break;
				}
			}
			if($assigned){
				PNApplication::error("Exam Center cannot be removed because some applicants are assigned to some of its rooms");
				return false;
			}
		}
		//Perform the remove
		SQLQuery::startTransaction();
		try {
			SQLQuery::create()
				->removeKey("ExamCenter", $id);
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(PNApplication::has_errors()){
			SQLQuery::rollbackTransaction();
			return false;
		} else {
			SQLQuery::commitTransaction();
			$this->updateAllSteps();
			return true;
		}
	}
	
/**
 * Methods about applicants
 */
	
	public function canAssignApplicantToCenter(){
		//Check the right
		return PNApplication::$instance->user_management->has_right("manage_applicant");
	}
	
	public function canAssignApplicantToSession(){
		//Check the right
		return PNApplication::$instance->user_management->has_right("manage_applicant");
	}
	
	public function canAssignApplicantToRoom(){
		//Check the right
		return PNApplication::$instance->user_management->has_right("manage_applicant");
	}
	
	/**
	 * Check if an applicant can be unassigned from an exam center
	 * @param number $EC_id exam center ID
	 * @param number $people_id people ID (applicant one)
	 * @return array <ul><li>0: boolean, true if the applicant can be unassigned</li><li>1: string error message if the applicant is assigned to a session</li><li>2: string error message if the applicant is assigned to a room</li></ul>
	 */
	public function canUnassignApplicantFromCenter($EC_id, $people_id){
		//Check the right
		$right = PNApplication::$instance->user_management->has_right("manage_applicant");
		if(!$right){
			PNApplication::error("You are not allowed to manage the applicants");
			return array(false,null,null);
		}
		$can = true;
		$session_error = null;
		$room_error = null;
		//Check that the applicant is not assigned to a session / room already
		$applicant = SQLQuery::create()
			->bypassSecurity()			
			->whereValue('Applicant', "people", $people_id);
		SelectionJSON::ApplicantSQL($applicant);
			$applicant = $applicant->executeSingleRow();
		if($applicant["exam_session"] <> null){
			$can = false;
			//Get the event name
			$ev_name = PNApplication::$instance->calendar->getEventName($this->getCalendarId(), $applicant["exam_session"]);
			$session_error = "Applicant ".$applicant["applicant_id"].", ".$applicant["last_name"].", ".$applicant["first_name"]." is already assigned to a session in this center (".$ev_name.")";			
		}
		if($applicant["exam_center_room"] <> null){
			$can = false;
			//Get the room name
			$room_name = SQLQuery::create()
				->bypassSecurity()
				->select("ExamCenterRoom")
				->field("ExamCenterRoom","name")
				->whereValue("ExamCenterRoom", "name", $applicant["exam_center_room"])
				->executeSingleValue();
			$room_error = "Applicant ".$applicant["applicant_id"].", ".$applicant["last_name"].", ".$applicant["first_name"]." is already assigned to a room in this center (".$room_name.")";
		}
		return array($can,$session_error,$room_error);
	}
	
	/**
	 * Check that an applicant can be unassigned by the user from an exam session or an exam center room
	 * @param number $people_id of the applicant
	 * @param number | NULL $session_id exam session ID if unassign from session
	 * @param number | NULL $room_id exam center room ID if unassign from room
	 * @return array <ul><li>0: boolean can be unassigned or not</li><li>1: NULL | string error message from grade</li></ul>
	 */
	public function canUnassignApplicantFromSessionOrRoom($people_id, $session_id, $room_id){
		$can = true;
		//Check the right
		$right = PNApplication::$instance->user_management->has_right("manage_applicant");
		if(!$right){
			PNApplication::error("You are not allowed to manage the applicants");
			return array(false,null);
		}
		//Check that the applicant has no grade yet
		//TODO
		return array(true,null);
	}
	
	/**
	 * Remove an applicant from an exam center entity, after checking that its allowed
	 * If the request is to remove an applicant from an exam session, the applicant would also be removed from the exam center room where he was assigned (if any)
	 * @param number $people_id people ID (applicant one)
	 * @param NULL | number $EC_id exam center ID if the applicant shall be removed from this center
	 * @param NULL | number $session_id exam session ID if the applicant shall be removed from this session
	 * @param NULL | number $room_id exam center room ID if the applicant shall be removed from this exam center room
	 * @return false if an error occured, else associative array: <ul><li><code>done</code> boolean true if the applicant was well unassigned</li><li><code>error_assigned_to_session</code> NULL | string error message because the applicant was assigned to a session</li><li><code>error_assigned_to_room</code> NULL | string error message because the applicant was assigned to a room</li><li><code>error_has_grade</code> NULL | string error message because the applicant already has grade (exam result)</li></ul>
	 */
	public function unassignApplicantFromCenterEntity($people_id, $EC_id, $session_id = null, $room_id = null){
		$mode = null;
		$can = null;
		$res = array(
			"done"=>null,
			"error_assigned_to_session" => null,
			"error_assigned_to_room" => null,
			"error_has_grade" => null
		);
		//Check if can be removed and set the mode
		if($EC_id <> null){
			$mode = "exam_center";
			$can_remove_from_center = $this->canUnassignApplicantFromCenter($EC_id,$people_id);
			$can = $can_remove_from_center[0];
			$res["error_assigned_to_session"] = $can_remove_from_center[1];
			$res["error_assigned_to_room"] = $can_remove_from_center[2];
		} else {
			if ($session_id <> null && $room_id == null) {
				$mode = "exam_session";
			}else if ($session_id <> null && $room_id <> null){
				$mode = "exam_center_room";
			}
			$can_remove_from_session_or_room = $this->canUnassignApplicantFromSessionOrRoom($people_id, $session_id, $room_id);
			$can = $can_remove_from_session_or_room[0];
			$res["error_has_grade"] = $can_remove_from_session_or_room[1];
		}
		if(!$can){
			$res["done"] = false;
			return $res;
		}
		SQLQuery::startTransaction();
		try{
			SQLQuery::create()
				->bypassSecurity()
				->updateByKey("Applicant", $people_id, array($mode => null));
			if($mode == "exam_session"){
				//Remove the student from the room, if any
				$room = SQLQuery::create()
					->bypassSecurity()
					->select("Applicant")
					->field("Applicant","exam_center_room")
					->whereValue("Applicant", "people", $people_id)
					->executeSingleValue();
				if($room <> null){
					SQLQuery::create()
						->bypassSecurity()
						->updateByKey("Applicant", $people_id, array("exam_center_room" => null));
				}
			}
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(PNApplication::has_errors()){
			SQLQuery::rollbackTransaction();
			return false;
		}
		SQLQuery::commitTransaction();
		$res["done"] = true;
		return $res;
	}
	
	/**
	 * Get the applicants assigned to a given information session but not assigned to any exam center
	 * @param number $IS_id information session ID
	 * @return NULL | array <ul><li>NULL if no applicant found</li><li>array containing the applicants rows for the applicants assigned</li></ul>
	 */
	public function getApplicantsAssignedToISNotAssignedToAnyEC($IS_id){
		$q = SQLQuery::create()
			->select("Applicant")
			->whereNull("Applicant", "exam_center")			
			->whereValue("Applicant", "information_session", $IS_id);
		SelectionJSON::ApplicantSQL($q);
		return $q->execute();
	}
	
	/**
	 * Get the applicants assigned to a center entity (exam center | exam session | exam center room)
	 * If no parameter given, get all the applicants
	 * @param number | NULL $EC_id exam center ID to get applicants assigned to this exam center, else NULL
	 * @param number | NULL $session_id exam session event ID to get applicants assigned to this exam session, or assigned to a room for a session, else NULL
	 * @param number | NULL $room_id exam center room session ID to get applicants assigned to this room, else NULL
	 * @param string $order_by  (optional) can be "name" or "applicant_id" the order by condition to set
	 * @param string | NULL $field_null (optional) name of a field that shall be NULL
	 * @return NULL | array NULL if no applicant found, else the applicants rows
	 */
	public function getApplicantsAssignedToCenterEntity($EC_id, $session_id = null, $room_id = null, $order_by = "applicant_id", $field_null = null){
		$q = SQLQuery::create()
			->select("Applicant");
		SelectionJSON::ApplicantSQL($q);
		if(isset($EC_id))
			$q->whereValue("Applicant", "exam_center",$EC_id);
		if(isset($session_id))
			$q->whereValue("Applicant", "exam_session",$session_id);
		if(isset($room_id))
			$q->whereValue("Applicant", "exam_center_room",$room_id);
		if($order_by == "name")
			$q->orderBy("People","last_name");
		else if($order_by == "applicant_id")
			$q->orderBy("Applicant","applicant_id");
		if($field_null <> null)
			$q->whereNull("Applicant", $field_null);
		return $q->execute();
	}
	
	/**
	 * Get all the applicants not assigned to any exam center
	 * @return NULL | array <ul><li>NULL if no applicant found</li><li>array containing the applicants rows for the applicants not assigned to any center</li></ul>
	 */
	public function getApplicantsNotAssignedToAnyEC(){
		$q = SQLQuery::create()
			->select("Applicant")
			->whereNull("Applicant", "exam_center");
		SelectionJSON::ApplicantSQL($q);
		return $q->execute();
	}
	
	/**
	 * Get the applicants assigned by room for a given exam session
	 * @param number $session_id
	 * @return NULL|array NULL if no room in this exam center, else associative array (rooms ids are the keys) with for each room:<ul><li><code>name</code> room name</li><li><code>capacity</code> room capacity</li><li><code>applicants</code> array |NULL NULL if no applicant assigned, else rows for applicants assigned</li></ul>
	 */
	public function getApplicantsAssignedByRoomForSession($session_id){
		$EC_id = SQLQuery::create()->select("ExamSession")->field("ExamSession","exam_center")->whereValue('ExamSession', "event", $session_id)->executeSingleValue();
		$rooms = SQLQuery::create()
			->select("ExamCenterRoom")
			->whereValue("ExamCenterRoom", "exam_center", $EC_id);
		SelectionJSON::ExamCenterRoomSQL($rooms);
		$rooms = $rooms->execute();
		if($rooms == null) return null;
		$res = array();
		foreach ($rooms as $room){
			$res[$room["id"]] = array(
					"name" => $room["name"],
					"capacity" => $room["capacity"],
					"applicants" => $this->getApplicantsAssignedToCenterEntity(null,$session_id,$room["id"])
			);
		}
		return $res;
	}

	/**
	 * Assign applicants to an exam center from the information sessions linked to this exam center
	 * Only the free applicants are assigned
	 * @param number $EC_id the exam center ID
	 * @return NULL|array <ul><li>NULL if the exam center has no IS linked, or if no applicant to assign</li><li>applicants rows, the rows retrieved from DB of the applicants assigned</li></ul>
	 */
	public function assignApplicantsFromISToEC($EC_id){
		//Check the right
		$can = $this->canAssignApplicantToCenter();
		if(!$can){
			PNApplication::error('You are not allowed to assign any applicant to an exam center');
			return false;
		}
		//Get the IS linked
		$IS = SQLQuery::create()
			->select("ExamCenterInformationSession")
			->field("ExamCenterInformationSession","information_session")
			->whereValue('ExamCenterInformationSession', "exam_center", $EC_id)
			->executeSingleField();
		if($IS == null)
			return null;
		$applicants_to_assign = array();
		$applicants_to_assign_ids = array();
		foreach ($IS as $id){
			array_push($applicants_to_assign, $this->getApplicantsAssignedToISNotAssignedToAnyEC($id));			
		}
		foreach ($applicants_to_assign as $applicant)
			array_push($applicants_to_assign_ids, $applicant["people_id"]);
		if(count($applicants_to_assign_ids) == 0)
			return null;
		//Perform the assignment
		SQLQuery::create()
			->bypassSecurity()
			->updateAllKeys("Applicant", $applicants_to_assign_ids, array("exam_center" => $EC_id));
		return $applicants_to_assign;
	}
	
	/**
	 * Assign any applicants to a given exam center, after checking the user is allowed
	 * @param number | array $applicants applicants people ID
	 * @param number $EC_id exam center ID
	 * @return boolean true if well assigned, else false
	 */
	public function assignApplicantsToEC($applicants,$EC_id){
		if(!is_array($applicants))
			$applicants = array($applicants);
		//Check the right
		$can = $this->canAssignApplicantToCenter();
		if(!$can){
			PNApplication::error('You are not allowed to assign any applicant to an exam center');
			return false;
		}
		SQLQuery::startTransaction();
		try {
			SQLQuery::create()
				->bypassSecurity()
				->updateAllKeys("Applicant", $applicants, array("exam_center" => $EC_id));
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(PNApplication::has_errors()){
			SQLQuery::rollbackTransaction();
			return false;
		} else {
			SQLQuery::commitTransaction();
			return true;
		}
	}
	
	/**
	 * Assign the applicants from information sessions to a given exam center
	 * @param number | array $IS_ids IDs of the informations sessions 
	 * @param number $EC_id exam center ID
	 */
	private function internalAssignApplicantsFromISToEC($IS_ids,$EC_id){
		$applicants_to_assign = array();
		$applicants_to_assign_ids = array();
		if(!is_array($IS_ids))
			$IS_ids = array($IS_ids);
		foreach ($IS_ids as $id){
			$r = $this->getApplicantsAssignedToISNotAssignedToAnyEC($id);
			if($r <> null)
				array_push($applicants_to_assign, $r);
		}
		foreach ($applicants_to_assign as $applicant_per_IS){
			foreach ($applicant_per_IS as $applicant){
				array_push($applicants_to_assign_ids, $applicant["people_id"]);
			}
		}
			
		if(count($applicants_to_assign_ids) == 0)
			return null;
		//Perform the assignment
		SQLQuery::create()
		->bypassSecurity()
		->updateAllKeys("Applicant", $applicants_to_assign_ids, array("exam_center" => $EC_id));
	}
	
	/**
	 * Get the applicants assigned per exam session for a given exam center
	 * @param number $EC_id exam center ID
	 * @param boolean $count if true, only get the number of applicants assigned per exam session, else get the applicants rows per session
	 * @return NULL|array NULL if no session for this exam center, else associative array with session event id as key (so session key), and each session key contains<ul><li>Number of applicants assigned if count = true</li><li>Else applicants assigned rows</li></ul>
	 */
	public function getApplicantsAssignedToSessionsInEC($EC_id, $count = false){
		$sessions = SQLQuery::create()
			->select("ExamSession")
			->field("ExamSession","event")
			->whereValue("ExamSession", "exam_center", $EC_id)
			->executeSingleField();
		if($sessions == null){
			return null;
		}
		$applicants = array();
		foreach ($sessions as $session){
			$r = SQLQuery::create()
			->select("Applicant")			
			->whereValue("Applicant", "exam_session", $session);
			if($count){
				$r = $r
						->count()
						->executeSingleValue();
				$applicants[$session]= $r == null ? 0 : $r;
			} else {
				SelectionJSON::ApplicantSQL($r);
				$r->field("Applicant","people");
				$rows =  $r->execute();
				$applicants[$session] = $rows;
			}
		}
		return $applicants;		
	}
	
	/**
	 * Get an exam center capacity, based on its rooms capacity
	 * @param number $EC_id exam center ID
	 * @return number the EC capacity
	 */
	public function getExamCenterCapacity($EC_id){
		$rooms = SQLQuery::create()
			->select("ExamCenterRoom")
			->field("ExamCenterRoom","capacity")
			->whereValue("ExamCenterRoom", "exam_center", $EC_id)
			->executeSingleField();
		$capacity = 0;
		if($rooms == null)
			return $capacity;
		foreach ($rooms as $cap){
			$capacity += $cap;
		}
		return $capacity;
	}
	
	/**
	 * Automatically assign applicants to exam sessions for a given center
	 * The assignment depends on the remaining slots and on the number of applicants to assign
	 * @param number $EC_id Exam center ID
	 * @return boolean|string|array <ul><li>false if any error occured (not allowed, not well performed)</li><li>string if nothing could be done explaining why (no slot remaining, no session, no applicant to assign)</li><li>else asssociative array (session_id => array(applicants_ids)) of applicants assigned</li></ul>
	 */
	public function assignApplicantsToSessionsAutomatically($EC_id){
		//Check the rights
		if(!$this->canAssignApplicantToSession()){
			PNApplication::error("You are not allowed to assign any applicant to an exam session");
			return false;
		}
		//Get all the sessions in this center
		$sessions = SQLQuery::create()
			->select("ExamSession")
			->field("ExamSession","event")
			->whereValue("ExamSession", "exam_center", $EC_id)
			->execute();
		$center_capacity = $this->getExamCenterCapacity($EC_id);
		if($center_capacity == 0)
			return "The capacity of this exam center is 0 (no room yet)";
		if($sessions <> NULL){
			//Get the remaining places
			$remaining_per_session = array();
			$free_slot_remaining = 0;
			foreach ($sessions as $session){
				$assigned = SQLQuery::create()
					->select('Applicant')
					->count()
					->whereValue("Applicant", "exam_session", $session["event"])
					->executeSingleValue();
				$assigned = $assigned == null ? 0 : $assigned;
				$remaining_per_session[$session["event"]] = $center_capacity - $assigned;
				$free_slot_remaining += $remaining_per_session[$session["event"]];
			}
			if($free_slot_remaining == 0){
				//No applicant can be assigned
				return "There is no remaining slot to assign any applicant";
			} else {
				//Get the applicants to assign
				$applicants_to_assign = SQLQuery::create()
					->select("Applicant")
					->field("Applicant","people")
					->whereValue("Applicant", "exam_center", $EC_id)
					->whereNull("Applicant", "exam_session")
					->executeSingleField();
				if($applicants_to_assign <> null){
					$to_perform = array();//Contains all the applicants to assign by session
					foreach ($remaining_per_session as $session => $remaining_slots){
						if($remaining_slots > 0){
							//Can assign applicants							
							//Calculate the number of applicants that can be assigned to this session
							if(count($applicants_to_assign) >= $remaining_slots){
								$to_assign = array();
								//Fill up the session
								for($i = 0; $i < $remaining_slots; $i++)
									array_push($to_assign, $applicants_to_assign[$i]);
								$applicants_to_assign = array_slice($applicants_to_assign,$remaining_slots);
								$to_perform[$session] = $to_assign;
							} else {
								//More remaining slots than applicants to assign
								$to_perform[$session] = $applicants_to_assign;
								$applicants_to_assign = array();
							}
							if(count($applicants_to_assign) == 0)
								break;
						}
					}
					//Perform
					SQLQuery::startTransaction();
					try {
						foreach ($to_perform as $session_id => $applicants_ids){
							SQLQuery::create()
								->bypassSecurity()
								->updateAllKeys("Applicant", $applicants_ids, array("exam_session" => $session_id));
						}
					} catch (Exception $e){
						PNApplication::error($e);						
					}
					if(PNApplication::has_errors()){
						SQLQuery::rollbackTransaction();
						return false;
					} else {
						SQLQuery::commitTransaction();
						return $to_perform;
					}
				} else {
					//No applicant to assign
					return "There is no applicant to assign to the sessions";
				}
			}
		} else {
			return "There is no session planned in this exam center";
		}
	}
	
	/**
	 * Get the number of remaining slots for a given exam session
	 * @param number $session_id
	 * @return number
	 */
	public function getRemainingSlotsForExamSession($session_id){
		$EC_id = SQLQuery::create()
			->bypassSecurity()
			->select("ExamSession")
			->field("ExamSession","exam_center")
			->whereValue("ExamSession", "event", $session_id)
			->executeSingleValue();
		$capacity = $this->getExamCenterCapacity($EC_id);
		$already_assigned = SQLQuery::create()
			->bypassSecurity()
			->select("Applicant")
			->count()
			->whereValue("Applicant", "exam_session", $session_id)
			->executeSingleValue();
		$already_assigned = $already_assigned == null ? 0 : $already_assigned;
		$remaining = $capacity - $already_assigned;
		return $remaining;
	}
	
	/**
	 * Get the number of remaining slots in an exam center room for a given exam session
	 * @param number $room_id exam center room ID
	 * @param number $session_id exam session ID
	 * @param NULL | number $room_capacity (optional) number the exam center room capacity
	 * @return number
	 */
	public function getRemainingSlotsForExamRoomDuringSession($room_id, $session_id, $room_capacity = null){
		if($room_capacity == null){
			$room_capacity = SQLQuery::create()
				->select("ExamCenterRoom")
				->field("ExamCenterRoom","capacity")
				->whereValue("ExamCenterRoom", "id", $room_id)
				->executeSingleValue();
		}
		$assigned = SQLQuery::create()
			->select("Applicant")
			->count()
			->whereValue("Applicant", "exam_session", $session_id)
			->whereValue("Applicant", "exam_center_room", $room_id)
			->executeSingleValue();
		$assigned = $assigned == null ? 0 : $assigned;
		return ($room_capacity - $assigned);
	}
	
	/**
	 * Assign any applicants to a given exam session, after checking the user is allowed, and that there are remaining slots
	 * @param number | array $applicants applicants people ID
	 * @param number $session_id exam session event ID
	 * @return boolean true if well assigned, else false
	 */
	public function assignApplicantsToSession($applicants,$session_id){
		if(!is_array($applicants))
			$applicants = array($applicants);
		//Check the right
		$can = $this->canAssignApplicantToSession();
		if(!$can){
			PNApplication::error('You are not allowed to assign any applicant to an exam session');
			return false;
		}
		//Check that there are remaining slots
		$remaining = $this->getRemainingSlotsForExamSession($session_id);
		if($remaining == 0){
			PNApplication::error("There is no remaining slot in this session");
			return;
		}
		if($remaining < count($applicants)){
			PNApplication::error("You are trying to assign ".count($applicants)." applicants to this exam session whereas there are only ".$remaining." remaining slots");
			return;
		}
		SQLQuery::startTransaction();
		try {
			SQLQuery::create()
			->bypassSecurity()
			->updateAllKeys("Applicant", $applicants, array("exam_session" => $session_id));
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(PNApplication::has_errors()){
			SQLQuery::rollbackTransaction();
			return false;
		} else {
			SQLQuery::commitTransaction();
			return true;
		}
	}
	
	/**
	 * Assign applicants to exam center rooms for a given exam session automatically
	 * @param number $session_id
	 * @param NULL | number | array $priority_applicants (optional) applicants ids to be assigned with priority: the method will start with assigning these applicants to the rooms (if they are really supposed to be assigned)
	 * @return boolean|string|array  <ul><li>false if any error occured (not allowed, not well performed)</li><li>string if nothing could be done explaining why (no slot remaining, no room, no applicant to assign)</li><li>else asssociative array (room_id => array(applicants_ids)) of applicants assigned</li></ul>
	 */
	public function assignApplicantsToRoomsForASessionAutomatically($session_id, $priority_applicants = null){
		//Check the rights
		if(!$this->canAssignApplicantToRoom()){
			PNApplication::error("You are not allowed to assign any applicant to an exam room");
			return false;
		}
		//Get the EC_id
		$EC_id = SQLQuery::create()->select("ExamSession")->field("ExamSession","exam_center")->whereValue('ExamSession', "event", $session_id)->executeSingleValue();
		//Get all the rooms in this center
		$rooms = SQLQuery::create()
			->select("ExamCenterRoom")
			->whereValue("ExamCenterRoom", "exam_center", $EC_id);
		SelectionJSON::ExamCenterRoomSQL($rooms);
		$rooms = $rooms->execute();
		if($rooms == NULL)
			return "The capacity of this exam center is 0 (no room yet)";
		//Get the remaining places
		$remaining_per_room = array();
		$free_slot_remaining = 0;
		foreach ($rooms as $room){
			$assigned = SQLQuery::create()
				->select('Applicant')
				->count()
				->whereValue("Applicant", "exam_center_room", $room["id"])
				->whereValue("Applicant", "exam_session", $session_id)
				->executeSingleValue();
			$assigned = $assigned == null ? 0 : $assigned;
			$remaining_per_room[$room["id"]] = $room["capacity"] - $assigned;
			$free_slot_remaining += $remaining_per_room[$room["id"]];
		}
		if($free_slot_remaining == 0){
			//No applicant can be assigned
			return "There is no remaining slot to assign any applicant";
		} else {
			//Get the applicants to assign
			$applicants_to_assign = SQLQuery::create()
				->select("Applicant")
				->field("Applicant","people")
				->whereValue("Applicant", "exam_center", $EC_id)
				->whereValue("Applicant", "exam_session", $session_id)//Only get the applicants assigned to the rooms for this session
				->whereNull("Applicant", "exam_center_room")
				->executeSingleField();
			if($applicants_to_assign == null)
				return "There is no applicant to assign to the rooms";
			$ordered_applicants_to_assign = array();
			if($priority_applicants <> null){
				//Put the priority applicants at the beginning of the $ordered_applicants_to_assign, after checking that they really have to be assigned
				if(!is_array($priority_applicants))
					$priority_applicants = array($priority_applicants);				
				foreach ($priority_applicants as $applicant_id){
					$index = array_search($applicant_id, $applicants_to_assign);
					if($index != false){//else the applicant is not supposed to be assigned
						//Put in the ordered array
						array_push($ordered_applicants_to_assign,$applicant_id);
						//Remove from the applicants_to_assign array
						array_splice($applicants_to_assign,$index,1);
					}
				}				
			}
			//add the remaining applicants to assign, if any
			if(count($applicants_to_assign) > 0){
				foreach ($applicants_to_assign as $applicant_id)
					array_push($ordered_applicants_to_assign,$applicant_id);
			}			
			$to_perform = array();//Contains all the applicants to assign by room
			foreach ($remaining_per_room as $room_id => $remaining_slots){
				if($remaining_slots > 0){
					//Can assign applicants
					//Calculate the number of applicants that can be assigned to this room
					if(count($ordered_applicants_to_assign) >= $remaining_slots){
						$to_assign = array();
						//Fill up the room
						for($i = 0; $i < $remaining_slots; $i++)
							array_push($to_assign, $ordered_applicants_to_assign[$i]);
						$ordered_applicants_to_assign = array_slice($ordered_applicants_to_assign,$remaining_slots);
						$to_perform[$room_id] = $to_assign;
					} else {
					//More remaining slots than applicants to assign
					$to_perform[$room_id] = $ordered_applicants_to_assign;
					$ordered_applicants_to_assign = array();
					}
					if(count($ordered_applicants_to_assign) == 0)
						break;
				}
			}
			//Perform
			SQLQuery::startTransaction();
			try {
				foreach ($to_perform as $room_id => $applicants_ids){
				SQLQuery::create()
					->bypassSecurity()
					->updateAllKeys("Applicant", $applicants_ids, array("exam_center_room" => $room_id));
				}
			} catch (Exception $e){
				PNApplication::error($e);
			}
			if(PNApplication::has_errors()){
				SQLQuery::rollbackTransaction();
				return false;
			} else {
				SQLQuery::commitTransaction();
				return $to_perform;
			}
		}
	}
	
	/**
	 * Assign an applicant to an exam center room, for a given exam session
	 * Before performing the asssignment, few checks are done: user rights, remaining slots in the room for the session,applicants assigned to the session
	 * @param number | array $applicants applicants IDs
	 * @param number $session_id exam session ID
	 * @param number $room_id exam center room ID
	 * @return void|boolean
	 */
	public function assignApplicantsToRoomForSession($applicants,$session_id, $room_id){
		if(!is_array($applicants))
			$applicants = array($applicants);
		//Check the right
		$can = $this->canAssignApplicantToRoom();
		if(!$can){
			PNApplication::error("You are not allowed to assign any applicant to exam center rooms");
			return false;
		}
		//Check that there are remaining slots
		$remaining = $this->getRemainingSlotsForExamRoomDuringSession($room_id, $session_id);
		if($remaining == 0){
			PNApplication::error("The room to which you are trying to assign applicants is full for this session");
			return false;
		}
		if(count($applicants) > $remaining){
			PNApplication::error("You are trying to assign ".count($applicants)." applicants to this room but there are only ".$remaining." slots remaining for this exam session");
			return false;
		}
		//Check that the applicants are assigned to the session
		$assigned_to_this_session = SQLQuery::create()
			->bypassSecurity()
			->select("Applicant")
			->count()
			->whereValue("Applicant", "exam_session", $session_id)
			->whereIn("Applicant", "people",$applicants)
			->executeSingleValue();
		if($assigned_to_this_session == null)
			$assigned_to_this_session = 0;
		if($assigned_to_this_session != count($applicants)){
			PNApplication::error("Some applicants are not assigned to this exam session");
			return false;
		}
		//Can perform the assignment
		SQLQuery::startTransaction();
		try {
			SQLQuery::create()
				->bypassSecurity()
				->updateAllKeys("Applicant", $applicants, array("exam_center_room" => $room_id));
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(PNApplication::has_errors()){
			SQLQuery::rollbackTransaction();
			return false;
		}
		SQLQuery::commitTransaction();
		return true;
	}
	
	/**
	 * Create an exam session
	 * @param array $event CalendarEvent
	 * @param number $EC_id exam center ID
	 * @return boolean|string false if any error occured, else the event ID of the event created
	 */
	public function createExamSession($event, $EC_id){
		//Check the right
		$can = PNApplication::$instance->user_management->has_right("manage_exam_center");
		if(!$can){
			PNApplication::error("You are not allowed to create an exam session");
			return false;
		}
		//Update the event title
		$center_name = SQLQuery::create()->select("ExamCenter")->field('ExamCenter',"name")->whereValue("ExamCenter", "id", $EC_id)->executeSingleValue();
		$event["title"] = "Exam session, ".$center_name;
		//Update the calendar_id
		$event["calendar_id"] = $this->getCalendarId();
		SQLQuery::startTransaction();
		$continue = PNApplication::$instance->calendar->saveEvent($event);
		if(!$continue){
			SQLQuery::rollbackTransaction();
			return false;
		}
		//Update the app link attributes
		$event["app_link"] = "/dynamic/selection/page/exam/session_profile?id=".$event["id"];
		$event["app_link_name"] = "This event is an Exam Session: click to see it";
		$continue = PNApplication::$instance->calendar->saveEvent($event);
		if(!$continue){
			SQLQuery::rollbackTransaction();
			return false;
		}
		//Insert in ExamSession table
		try {
			SQLQuery::create()
				->insert("ExamSession", array("event" => $event["id"], "exam_center" => $EC_id));
		} catch (Exception $e){
			PNApplication::error($e);
			SQLQuery::rollbackTransaction();
			return false;
		}
		SQLQuery::commitTransaction();
		return $event["id"];
	}
}
?>