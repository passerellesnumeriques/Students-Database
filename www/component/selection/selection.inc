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
	 * @method initRequest called each time the selection component executes a database query
	 * Check if a cookie selection_campaign exists; if yes, set the campaign_id attribute to its value
	 * If the campaign_id is set, the matching submodels is selected in the database
	 */
	public function initRequest() {
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
			SQLQuery::setSubModel("SelectionCampaign", $this->campaign_id);
	}
	
	public function getPluginImplementations() {
		require_once("SelectionOrganizationPlugin.inc");
		require_once("SelectionNewsPlugin.inc");
		require_once("ApplicantPeopleTypePlugin.inc");
		return array(
			new SelectionOrganizationPlugin(),
			new SelectionNewsPlugin(),
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
		if ($id <> null && $id <= 0) $id = null;
		$this->campaign_id = $id;
		/* Reset the config */
		$this->config = null;
		setcookie("selection_campaign",$id == null ? "-1" : $id,time()+365*24*60*60,"/dynamic/selection/");
		SQLQuery::setSubModel("SelectionCampaign", $this->campaign_id);
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
		if(PNApplication::hasErrors()){
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
		/*
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
		*/
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
		if(!$transation_started_before && PNApplication::hasErrors())
			SQLQuery::rollbackTransaction();
		else if(!$transation_started_before && !PNApplication::hasErrors())
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
		if(PNApplication::hasErrors())
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
			try{
				//Perform the remove
				SQLQuery::create()->bypassSecurity()->removeKey("ExamSubject",$exam_id);
				// check there is no empty exam extract (if we removed all its parts)
				$empty_extracts = SQLQuery::create()
					->select("ExamSubjectExtract")
					->join("ExamSubjectExtract", "ExamSubjectExtractParts", array("id"=>"extract"))
					->groupBy("ExamSubjectExtract","id")
					->whereNull("ExamSubjectExtractParts", "part") // no more part attached
					->field("ExamSubjectExtract", "id")
					->executeSingleField();
				if (count($empty_extracts) > 0)
					SQLQuery::create()->removeKeys("ExamSubjectExtract", $empty_extracts);
			} catch (Exception $e) {
				PNApplication::error($e);
			}
		} else
			PNApplication::error($can_remove[1]);
		if(PNApplication::hasErrors())
			return false;
		else {
// 			//update the topics
// 			$this->updateExamTopicsForEligibilityRules($parts,null,$exam_id);
			//update steps
			//$this->updateAllSteps();
			return true;
		}
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
		if(PNApplication::hasErrors()){
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

}
?>