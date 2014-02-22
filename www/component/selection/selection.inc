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
		return array(
			new SelectionOrganizationPlugin(),
			new SelectionNewsPlugin(),
			new SelectionApplicationPlugin()
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
		SQLQuery::create()->removeKey("SelectionCampaign",$id);
		
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
	 * @return the config attribute
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
	public function saveConfig($configs){
		$to_return = null;
		try{
			foreach($configs as $name=>$value){
				SQLQuery::create()->updateByKey("SelectionCampaignConfig", $name, array("value"=>$value));
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
	 * @return array	[0]: boolean $can_update true if steps and rights for user_management ok
	 * 					[1]: string $error_messages the list of errors to display (error from rights & errors from steps)
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
	 * @return array 	[0]: array rights values
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
			if(isset($id) && $id <> null){
				// This is an update
				SQLQuery::create()->updateByKey("InformationSession",$id,$fields_values);
				//update the steps
				$this->updateAllSteps();
				return;
			} else {
				// This is an insert
				$id = SQLQuery::create()->insert("InformationSession",$fields_values);
				//update the steps
				$this->updateAllSteps();
				return $id;
			}
		} catch (Exception $e) {
			PNApplication::error($e);
		}
	}
	
	/**
	 * Remove all the partners of the given information session
	 * The rights must be checked before calling this method
	 * @param integer IS_id the IS id from which the partners must be removed
	 */
	public function removeAllISPartners($IS_id){
		$partners = SQLQuery::create()->select("InformationSessionPartner")
				->field("organization")
				->whereValue("InformationSessionPartner","information_session",$IS_id)
				->execute();
		if(isset($partners[0]["organization"])){
			foreach($partners as $p)
				SQLQuery::create()->removeKeys("InformationSessionPartner",array(array("information_session" => $IS_id, "organization" => $p["organization"])));
		}
	}	
	
	/**
	 * Remove all the contact points of the given information session
	 * The rights must be checked before calling this method
	 * @param integer IS_id
	 */
	public function removeAllISContactPoints($IS_id){
		$contact_point = SQLQuery::create()->select("InformationSessionContactPoint")
				->field("organization")
				->field("people")
				->whereValue("InformationSessionContactPoint","information_session",$IS_id)
				->execute();
		if(isset($contact_point[0]["organization"])){
			foreach($contact_point as $c)
				SQLQuery::create()
				->removeKeys("InformationSessionContactPoint",
				array(
					array("information_session" => $IS_id, "organization" => $c["organization"], "people" => $c["people"])
				));
		}
	}
	
	/**
	 * Insert the partners of an information session into the database
	 * The rights must be checked before calling this method
	 * @param array rows to insert in InformationSession_partner table
	 */
	public function insertISPartners($rows){
		SQLQuery::create()->insertMultiple("InformationSessionPartner",$rows);
	}
	
	/**
	 * Insert the contact points of an information session into the database
	 * The rights must be checked before calling this method
	 * @param array rows to insert in InformationSessionContactPoint table
	 */
	public function insertISContactPoints($rows){
		SQLQuery::create()->insertMultiple("InformationSessionContactPoint",$rows);
	}
	/**
	 * Remove an information session from the database
	 * @param integer id the one of the IS to remove
	 * @param integer fake_organization the id of the fake organization linked to this IS
	 */
	public function removeIS($id, $fake_organization){
		// check the rights
		if(!PNApplication::$instance->user_management->has_right("manage_information_session",true)){
			PNApplication::error("You are not allowed to remove any information session");
			return;
		}
		//update the steps
		$this->updateAllSteps();
		$this->removeFakeOrganization($id, $fake_organization);
		SQLQuery::create()->removeKey("InformationSession",$id);
		if(PNApplication::has_errors())
			return;
		else
			return true;
	}
	
	/**
	 * Remove the fake organization linked to an information session
	 * after checking it is really the one linked to the given IS
	 * The rights must be checked before calling this method
	 * @param integer IS_id
	 * @param integer fake_organization id
	 */
	private function removeFakeOrganization($IS_id, $fake_organization){
		//check the fake_organization matches with the one set in the IS
		$db_fake = SQLQuery::create()->select("InformationSession")
			->field("fake_organization")
			->whereValue("InformationSession","id",$IS_id)
			->executeSingleValue();
		if(isset($db_fake)){
			if($db_fake == $fake_organization)
				SQLQuery::create()->bypassSecurity()->removeKey("Organization",$fake_organization);
			else
				PNApplication::error("The given fake_organization(".$fake_organization.") does not match with the one set into the database(".$db_fake.")");
		} else
			PNApplication::error("This fake_organization does not exist into the database");
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
	public function removeExam($exam_id){
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
				SQLQuery::create()->bypassSecurity()->removeKey("ExamSubject",$exam_id);
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
	
	/**
	 * Method called by the selection#saveExam method
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
	public function saveExam($exam_id, $rows_exam_table, $rows_parts_table, $questions_by_old_parts ,$questions_by_new_part, $parts_to_insert_indexes){
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
					// save
					$new_parts = SQLQuery::create()
						->insertMultiple("ExamSubjectPart",$rows_parts_table);
					
					if($has_questions_from_new_parts){
						$rows_questions_table = $this->updatePartIdInQuestionsRows($new_parts,$questions_by_new_part,$parts_to_insert_indexes);
						// insert using bypassSecurity
						SQLQuery::create()
							->bypassSecurity()
							->insertMultiple("ExamSubjectQuestion",$rows_questions_table);
					}
						// create a topic full_subject for this new subject
						$this->createTopicAndInsertFullExamSubject($exam_id);
				}
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
						$new_parts_ids = SQLQuery::create()
							->insertMultiple("ExamSubjectPart",$rows_parts_to_insert);
					}
					/** The exam topics for eligibility rules must only be updated in the case of the subject
					 * already had parts (that could have been selected into any exam topic)
					 * This method must be called before removing the parts, to remove the topic if empty
					 */
						$this->updateExamTopicsForEligibilityRules($parts_to_remove,$new_parts_ids,$exam_id);
					
					//remove the parts to remove
					if(count($parts_to_remove) > 0)
						SQLQuery::create()
							->bypassSecurity()
							->removeKeys("ExamSubjectPart",$parts_to_remove);
				} else if($has_parts) {
					//all the parts must be inserted
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
	 * In case a topic gets empty after removing parts, it is automatically deleted
	 * @param array parts_removed containing the ids of the removed parts
	 * @param array parts_inserted containing the ids of the inserted parts,
	 * used to generate a message + in case the topic is a full subject one (added to the topic)
	 */	
	public function updateExamTopicsForEligibilityRules($parts_removed,$parts_inserted,$exam_id){
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
						$topics_to_be_inserted = $this->getTopicsFullSubjectForGivenExamSubject($exam_id);
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
							->removeKeys("ExamTopicForEligibilityRule",$topics_to_remove);
						$topics_removed = true;
					}
				}
				return array($inserted,$topics_removed);
		} else
			return;
	}
	
	/**
	 * Get all the topics declared as full subject for the given exam subject
	 * @param integer $exam_id
	 * @return array containing the matching topics ids
	 */
	private function getTopicsFullSubjectForGivenExamSubject($exam_id){
		$topics = SQLQuery::create()
			->select("ExamTopicFullExam")
			->field("ExamTopicFullExam","exam_topic_for_eligibility_rule","topic_id")
			->whereValue("ExamTopicFullExam","exam_subject",$exam_id)
			->executeSingleField();
		return $topics;
	}
	
	/**
	 * Check if a topic is empty
	 * @param integer $topic_id
	 * @return boolean
	 */
	private function isTopicEmpty($topic_id){
		$q = SQLQuery::create()
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
	 * Check if a part is empty
	 * @param integer $part_id
	 * @return boolean
	 */
	private function isPartEmpty($part_id){
		$q = SQLQuery::create()
			->select("ExamSubjectQuestion")
			->count()
			->whereValue("ExamSubjectQuestion","exam_subject_part",$part_id)
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
		if(isset($parts[0])){
		$topics = SQLQuery::create()
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
		if($parts_to_insert <> null){
			//insert the topic
			$id = SQLQuery::create()
				->insert("ExamTopicForEligibilityRule", array(
					"name" => $name,
					"max_score" => $topic_max_score,
					"number_questions" => $topic_number_questions
				));
			//insert the parts
			$rows = array();
			foreach($parts_to_insert as $p)
				array_push($rows,array("exam_topic_for_eligibility_rule" => $id, "exam_subject_part" => $p));
			SQLQuery::create()
				->insertMultiple("ExamPartTopic",$rows);
			//Check the values
				$this->checkTopicMaxScoreField($id);
				$this->checkTopicNumberQuestionsField($id);
			return $id;
		}
	}
	
	/**
	 * Create a full subject topic for eligibility rules into the database
	 * for a given exam subject
	 * All the parts of this exam subject are inserted into the topic
	 * These topic and exam subject are inserted into the ExamTopicFullExam table 
	 * @param unknown $exam_id
	 * @return number the id of the new topic
	 */
	private function createTopicAndInsertFullExamSubject($exam_id){
		$parts_to_insert = SQLQuery::create()
			->bypassSecurity()
			->select("ExamSubjectPart")
			->field("ExamSubjectPart","id","id")
			->whereValue("ExamSubjectPart", "exam_subject", $exam_id)
			->executeSingleField();
		if($parts_to_insert <> null){
			//Get the exam name
			$name = SQLQuery::create()
				->select("ExamSubject")
				->field("name")
				->whereValue("ExamSubject","id",$exam_id)
				->executeSingleValue();
			if($name <> null){				
				$id = $this->createTopic($name, $parts_to_insert);
				//insert in the ExamTopicFullExam table
				SQLQuery::create()
					->insert("ExamTopicFullExam",array("exam_subject" => $exam_id, "exam_topic_for_eligibility_rule" => $id));
				return $id;
			}
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
					->select("ExamTopicFullExam")
					->field("ExamTopicFullExam","exam_subject","subject")
					->whereValue("ExamTopicFullExam","exam_topic_for_eligibility_rule",$id)
					->executeSingleField();
					if($full_subjects_to_remove <> null){
						foreach($full_subjects_to_remove as $s)
							SQLQuery::create()->removeKeys("ExamTopicFullExam", array(array("exam_topic_for_eligibility_rule" => $id, "exam_subject" => $s)));
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
					SQLQuery::create()->insertMultiple("ExamTopicFullExam", $full_subject_field_values, $db_lock);
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
	 * The rights and steps are checked before performing the query
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
				SQLQuery::create()
					->removeKey("ExamTopicForEligibilityRule",$id);
			} catch (Exception $e){
				PNApplication:error($e);
				return false;
			}
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
		
		if($res)
			return;
		else {
			$error_message = "<ul>";
			$error_multiple_entries = null;
			$error_missing = null;
			$error_unknown = null;
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
			if($parts_unknown <> null && !$parts_unknown_removed)
				$error_message .= "<li>Some parts are set in the topics but don't exist anymore into the database</li>";
			if($error_missing <> null)
				$error_message .= "<li>".$error_missing."</li>";
			if($error_multiple_entries <> null)
				$error_message .= "<li>".$error_multiple_entries."</li>";
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
					$catch_root_id = false;
					foreach($rules_to_insert as $rule){
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
	
}
?>