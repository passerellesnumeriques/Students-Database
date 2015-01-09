<?php
class selection extends Component {
	/** @var number currently selected campaign */
	private $campaign_id = null;
	/** @var string|null if not null, the campaign is frozen */
	private $frozen_reason = null;
	
	/** @var number A calendar is linked to the selection campaign */
	private $calendar_id = null;
	
	/** @var array When set, config is an array [["name"=>"value"],[...]] */
	private $config = null;
	
	/**
	 * @method initRequest called each time the selection component executes a database query
	 * Check if a cookie selection_campaign exists; if yes, set the campaign_id attribute to its value
	 * If the campaign_id is set, the matching submodels is selected in the database
	 */
	public function initRequest() {
		Component::allowServiceWithoutLogin("selection", "travel/download_backup");
		Component::allowServiceWithoutLogin("selection", "travel/download_update");
		Component::allowServiceWithoutLogin("selection", "travel/synch_file");
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
		require_once("SelectionApplicationSection.inc");
		require_once("ApplicantImportTemplate.inc");
		require_once("ApplicantMaxAge.inc");
		require_once("SocialInvestigationProfilePage.inc");
		require_once("SocialInvestigationPicturesPlugin.inc");
		return array(
			new SelectionOrganizationPlugin(),
			new SelectionNewsPlugin(),
			new ApplicantPeopleTypePlugin(),
			new SelectionApplicationSection(),
			new ApplicantImportTemplate(),
			new ApplicantMaxAge(),
			new SocialInvestigationProfilePage(),
			new SocialInvestigationPicturesPlugin()
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
	
	/** Indicates if the campaign is frozen
	 * @return boolean true if it is frozen
	 */
	public function isFrozen() {
		return $this->frozen_reason <> null;
	}
	/** Indicates the reason for the campaign to be frozen
	 * @return string|NULL the reason, or null if it is not frozen
	 */
	public function getFrozenReason() {
		return $this->frozen_reason;
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
		/* Get the calendar id and frozen reason */
		$campaign = SQLQuery::create()->select("SelectionCampaign")->where("id",$id)->executeSingleRow();
		$this->calendar_id = $campaign["calendar"];
		if ($campaign["frozen"] == 1)
			$this->frozen_reason = $campaign["frozen_reason"];
		else
			$this->frozen_reason = null;
		// load the config
		$this->getConfig();
	}
	
	/**
	 * @method createCampaign
	 * @param string $campaign_name
	 * Create a campaign in the database, set its config to the default values, set the campaign id, set its steps
	 * Create a calendar for the selection campaign
	 * @return integer | boolean $campaign_id the id of the campaign created, false if an error occured
	 */
	public function createCampaign($campaign_name){
#SELECTION_TRAVEL
#PNApplication::error("You cannot create a new campaign because you are on a travelling version.");
#return;
#END
		$prev_id = $this->campaign_id;
		$campaign_id = null;
		$fields = array();
		$all_configs = include("config.inc");
		//check the rights
		if(!PNApplication::$instance->user_management->hasRight("manage_selection_campaign",true)){
			PNApplication::error("You are not allowed to manage the selection campaigns");
			return;
		}
		/**
		 * Cannot do a transaction because we are inserting tables (submodel) 
		 */
		try{
			require_once 'component/data_model/DataBaseLock.inc';
			$locked_by = null;
			$lock_id = DataBaseLock::lockTable("SelectionCampaign", $locked_by);
			if ($lock_id == null) {
				PNApplication::error("We cannot create a new campaign right now because $locked_by is actually locking the selection campaigns. Please try again.");
				return;
			}
			/* Create a calendar for this campaign */
			$calendar_id = PNApplication::$instance->calendar->createSharedCalendar($campaign_name,"FFA040","/static/selection/selection_16.png",array("can_access_selection_data"=>true),array("manage_information_session"=>true,"manage_exam_center"=>true,"manage_interview_center"=>true,"manage_selection_campaign"=>true,"manage_trips"=>true,"edit_social_investigation"=>true));
			
			/* if the calendar was properly created, continue */
			if($calendar_id <> null){
				$fields["name"] = $campaign_name;
				$fields["calendar"] = $calendar_id;
				$fields["frozen"] = 0;
				$campaign_id = SQLQuery::create()->insert("SelectionCampaign",$fields);
				
				/* Insert the default config in the database */
				$default_config_rows = array();
				$config = array();
				foreach($all_configs as $name => $data) {
					array_push($default_config_rows,array(
						"name" => $name,
						"value" => json_encode($data[1]),
					));
					$config[$name] = json_encode($data[1]);
				}
				SQLQuery::create()->selectSubModel("SelectionCampaign", $campaign_id)->insertMultiple("SelectionCampaignConfig",$default_config_rows);
				
				$this->setCampaignId($campaign_id);

				// save the config, to apply logic on every configuration
				$this->saveConfig($config, $lock_id);
				DataBaseLock::unlock($lock_id);
				$lock_id = null;
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
			if ($lock_id <> null)
				DataBaseLock::unlock($lock_id);
			if ($prev_id <> $this->campaign_id)
				$this->setCampaignId($prev_id);
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
#SELECTION_TRAVEL
#PNApplication::error("You cannot remove a campaign because you are on a travelling version.");
#return;
#END
		//check the rights
		if(!PNApplication::$instance->user_management->hasRight("manage_selection_campaign",true)){
			PNApplication::error("You are not allowed to manage the selection campaigns");
			return;
		}
		
		SQLQuery::startTransaction();
		$campaign = SQLQuery::create()->select("SelectionCampaign")->whereValue("SelectionCampaign", "id", $id)->executeSingleRow();
		if ($campaign == null) {
			SQLQuery::rollbackTransaction();
			PNApplication::error("Invalid selection campaign");
			return;
		}
		// remove the calendar
		PNApplication::$instance->calendar->removeSharedCalendar($campaign["calendar"]);
		// remove applicants
		$applicants_ids = SQLQuery::create()->select("Applicant")->field("people")->executeSingleField();
		set_time_limit(30+floor(count($applicants_ids)/1000));
		if (count($applicants_ids) > 0) {
			$campaigns_ids = SQLQuery::create()->select("SelectionCampaign")->whereNotValue("SelectionCampaign", "id", $id)->field("id")->executeSingleField();
			$reassign_applicants = array();
			foreach ($campaigns_ids as $cid) {
				$common_applicants_ids = SQLQuery::create()->selectSubModel("SelectionCampaign", $cid)->select("Applicant")->whereIn("Applicant","people",$applicants_ids)->field("people")->executeSingleField();
				foreach ($common_applicants_ids as $people_id) {
					$i = array_search($people_id, $applicants_ids);
					array_splice($applicants_ids, $i, 1);
					$reassign_applicants[$people_id] = $cid;
				}
			}
			PNApplication::$instance->people->removePeoplesType($applicants_ids, "applicant");
			foreach ($reassign_applicants as $id=>$cid)
				SQLQuery::create()->bypassSecurity()
					->updateByKey("smlink_Applicant_People", $id, array("sm"=>$cid));
		}
		// TODO remove organizations and their contact points...
		// TODO remove all news having the tag campaignX
		if (PNApplication::hasErrors()) {
			SQLQuery::rollbackTransaction();
			return;
		}
		SQLQuery::commitTransaction();
		/* Set the campaign id to null */
		$this->campaign_id = null;
		/* Set the calendar_id to null */
		$this->calendar_id = null;
		// remove the campaign and its sub-model
		SQLQuery::create()->removeKey("SelectionCampaign",$id);
	}
	
	/**
	 * @method renameCampaign
	 * @param integer $id
	 * @param string $name
	 * Set the name of the given campaign (id) to the given value (name) in the database
	 */
	public function renameCampaign($id, $name){
#SELECTION_TRAVEL
#PNApplication::error("You cannot rename a campaign because you are on a travelling version.");
#return;
#END
		//check the rights
		if(!PNApplication::$instance->user_management->hasRight("manage_selection_campaign",true)){
			PNApplication::error("You are not allowed to manage the selection campaigns");
			return;
		}
		SQLQuery::create()->updateByKey("SelectionCampaign",$id,array("name"=>$name));
		// update calendar name
		$calendar_id = SQLQuery::create()->select("SelectionCampaign")->whereValue("SelectionCampaign", "id", $id)->field("calendar")->executeSingleValue();
		PNApplication::$instance->calendar->renameCalendar($calendar_id, $name);
	}
	
	public function freezeCampaign($reason) {
#SELECTION_TRAVEL
#PNApplication::error("You cannot lock a campaign because you are on a travelling version.");
#return;
#END
		if ($reason == "travel") $reason = "Selection Team Travelling";
		else if ($reason == "done") $reason = "Campaign Finished";
		// lock every table in the sub-model
		$locks = array();
		require_once 'component/data_model/Model.inc';
		require_once 'component/data_model/DataBaseLock.inc';
		foreach (DataModel::get()->getSubModel("SelectionCampaign")->internalGetTables() as $table) {
			if ($table->getName() == "TravelVersion" || $table->getName() == "TravelVersionSynchStorage") continue;
			$locked_by = null;
			$id = DataBaseLock::lockTableForEver($table->getSQLNameFor($this->getCampaignId()), $reason, $locked_by);
			if ($id == null) {
				foreach ($locks as $lock) DataBaseLock::unlock($lock);
				PNApplication::error("We cannot lock the campaign because $locked_by is currently editing data.");
				return false;
			}
			array_push($locks, $id);
		}
		// freeze calendar
		PNApplication::$instance->calendar->removeSharedCalendarWritableRight($this->calendar_id, "manage_information_session", true);
		PNApplication::$instance->calendar->removeSharedCalendarWritableRight($this->calendar_id, "manage_exam_center", true);
		PNApplication::$instance->calendar->removeSharedCalendarWritableRight($this->calendar_id, "manage_interview_center", true);
		PNApplication::$instance->calendar->removeSharedCalendarWritableRight($this->calendar_id, "manage_selection_campaign", true);
		PNApplication::$instance->calendar->removeSharedCalendarWritableRight($this->calendar_id, "manage_trips", true);
		PNApplication::$instance->calendar->removeSharedCalendarWritableRight($this->calendar_id, "edit_social_investigation", true);
		// mark the campaign as frozen
		SQLQuery::create()->bypassSecurity()->updateByKey("SelectionCampaign", $this->getCampaignId(), array("frozen"=>1,"frozen_reason"=>$reason));
		$this->frozen_reason = $reason;
		return true;
	}
	
	public function unfreezeCampaign() {
#SELECTION_TRAVEL
#PNApplication::error("You cannot unlock a campaign because you are on a travelling version.");
#return;
#END
		// unlock all tables
		require_once 'component/data_model/Model.inc';
		require_once 'component/data_model/DataBaseLock.inc';
		foreach (DataModel::get()->getSubModel("SelectionCampaign")->internalGetTables() as $table)
			DataBaseLock::unlockTableForEver($table->getSQLNameFor($this->getCampaignId()), $this->frozen_reason);
		// unlock the calendar
		PNApplication::$instance->calendar->addSharedCalendarWritableRight($this->calendar_id, "manage_information_session", true);
		PNApplication::$instance->calendar->addSharedCalendarWritableRight($this->calendar_id, "manage_exam_center", true);
		PNApplication::$instance->calendar->addSharedCalendarWritableRight($this->calendar_id, "manage_interview_center", true);
		PNApplication::$instance->calendar->addSharedCalendarWritableRight($this->calendar_id, "manage_selection_campaign", true);
		PNApplication::$instance->calendar->addSharedCalendarWritableRight($this->calendar_id, "manage_trips", true);
		PNApplication::$instance->calendar->addSharedCalendarWritableRight($this->calendar_id, "edit_social_investigation", true);
		// mark the campaign as not frozen
		SQLQuery::create()->bypassSecurity()->updateByKey("SelectionCampaign", $this->getCampaignId(), array("frozen"=>0,"frozen_reason"=>null));
		// remove synch with travel version information
		$rows = SQLQuery::create()->bypassSecurity()->select("TravelVersion")->execute();
		if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("TravelVersion", $rows);
		$this->frozen_reason = null;
		return true;
	}
	
	/**
	 * @method getConfig
	 * @return array of arrays("name"=>, "value"=>)
	 * If the config attribute is set to null, will get its values in the database and set the config attribute
	 */
	public function getConfig($sub_model = null){
		if ($sub_model == null) $sub_model = $this->campaign_id;
		if ($sub_model == null || $sub_model == "@link") return array();
		if ($sub_model <> $this->campaign_id)
			return SQLQuery::create()
					->selectSubModel("SelectionCampaign", $sub_model)
					->select("SelectionCampaignConfig")
					->field("name")
					->field("value")
					->execute();
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
	public function getOneConfigAttributeValue($name,$sub_model = null){
		if ($sub_model == null && $this->campaign_id > 0) $sub_model = $this->campaign_id;
		$conf = $this->getConfig($sub_model);
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
			// apply auto generation of applicant ID
			if (isset($configs["generate_applicant_id"])) {
				$res = SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DESCRIBE `Applicant_".$this->getCampaignId()."` `applicant_id`");
				$descr = SQLQuery::getDataBaseAccessWithoutSecurity()->nextRow($res);
				if (($configs["generate_applicant_id"] == "true" && $descr["Extra"] == null) ||
					($configs["generate_applicant_id"] <> "true" && $descr["Extra"] == "auto_increment")) {
					require_once("component/data_model/Model.inc");
					$t = DataModel::get()->internalGetTable("Applicant");
					$col = $t->internalGetColumnFor("applicant_id", $this->getCampaignId());
					SQLQuery::getDataBaseAccessWithoutSecurity()->execute("ALTER TABLE `Applicant_".$this->getCampaignId()."` CHANGE `applicant_id` ".$col->getSQL(SQLQuery::getDataBaseAccessWithoutSecurity(), "Applicant_".$this->getCampaignId()));
				}
			}
			// apply maximum age
			if (isset($configs["forbid_too_old_applicants"])) {
				require_once("component/data_model/TableDefinition.inc");
				if ($configs["forbid_too_old_applicants"] == "true") {
					$min_bd = json_decode($configs["limit_date_of_birth"]);
					// exclude too old
					$q = SQLQuery::create()->bypassSecurity()->select("Applicant");
					PNApplication::$instance->people->joinPeople($q, "Applicant", "people");
					$q->where("`People`.`birth` < '".SQLQuery::escape($min_bd)."'");
					$q->whereNotValue("Applicant","automatic_exclusion_reason","Too old");
					$q->resetFields();
					$q->field("Applicant","people","applicant_id");
					$to_exclude = $q->executeSingleField();
					if (count($to_exclude) > 0)
						SQLQuery::create()->bypassSecurity()->updateByKeys("Applicant",array(array(
							$to_exclude,
							array(
								"excluded"=>1,
								"automatic_exclusion_step"=>"Application Form",
								"automatic_exclusion_reason"=>"Too old"
							)	
						)));
					// put back not too old anymore
					$q = SQLQuery::create()->bypassSecurity()->select("Applicant");
					PNApplication::$instance->people->joinPeople($q, "Applicant", "people");
					$q->where("`People`.`birth` >= '".SQLQuery::escape($min_bd)."'");
					$q->whereValue("Applicant","automatic_exclusion_reason","Too old");
					$q->resetFields();
					$q->field("Applicant","people","applicant_id");
					$to_unexclude = $q->executeSingleField();
					if (count($to_unexclude) > 0)
						SQLQuery::create()->bypassSecurity()->updateByKeys("Applicant",array(array(
							$to_unexclude,
							array(
								"excluded"=>0,
								"automatic_exclusion_step"=>null,
								"automatic_exclusion_reason"=>null
							)
						)));
				} else {
					// put back not too old anymore
					$q = SQLQuery::create()->bypassSecurity()->select("Applicant");
					$q->whereValue("Applicant","automatic_exclusion_reason","Too old");
					$q->field("Applicant","people","applicant_id");
					$to_unexclude = $q->executeSingleField();
					if (count($to_unexclude) > 0)
						SQLQuery::create()->bypassSecurity()->updateByKeys("Applicant",array(array(
							$to_unexclude,
							array(
								"excluded"=>0,
								"automatic_exclusion_step"=>null,
								"automatic_exclusion_reason"=>null
							)
						)));
				}
			}
		} catch(Exception $e) {
			$to_return = $e;
		}
		$this->config = null;
		return $to_return;
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
	
	public function getCampaignCalendar($campaign_id) {
		return SQLQuery::create()->select("SelectionCampaign")->whereValue("SelectionCampaign","id",$campaign_id)->field("calendar")->executeSingleValue();
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
		if(!PNApplication::$instance->user_management->hasRight("manage_information_session",true)){
			PNApplication::error("You are not allowed to remove any information session");
			return;
		}
		SQLQuery::create()->removeKey("InformationSession",$id);
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
	
	public function canEditExamSubjects() {
		if (!PNApplication::$instance->user_management->hasRight("manage_exam_subject")) return false;
		if ($this->hasExamResults()) return false;
		return true;
	}
	public function hasExamResults() {
		$applicant_result = SQLQuery::create()->bypassSecurity()->select("ApplicantExamSubject")->limit(0,1)->executeSingleRow();
		if ($applicant_result <> null) return true; // result already set for at least one applicant
		$applicant_result = SQLQuery::create()->bypassSecurity()->select("ApplicantExamSubjectPart")->limit(0,1)->executeSingleRow();
		if ($applicant_result <> null) return true; // result already set for at least one applicant
		return false;
	}
		
	public function canEditInterviewCriteria() {
		if (!PNApplication::$instance->user_management->hasRight("manage_interview_criteria")) return false;
		if ($this->hasInterviewResults()) return false;
		return true;
	}
	
	public function hasInterviewResults() {
		$applicant_result = SQLQuery::create()->bypassSecurity()->select("ApplicantInterviewCriterionGrade")->limit(0,1)->executeSingleRow();
		if ($applicant_result <> null) return true; // result already set for at least one applicant
		return false;
	}
	
	public function applyExamEligibilityRules() {
		// get applicants grades
		$applicants_subjects_grade = SQLQuery::create()->bypassSecurity()->select("ApplicantExamSubject")->execute();
		if (count($applicants_subjects_grade) == 0) return; // no grade !
		$applicants_extracts_grade = SQLQuery::create()->bypassSecurity()->select("ApplicantExamExtract")->execute();
		set_time_limit(300);
		$applicants = array();
		foreach ($applicants_subjects_grade as $g) {
			if (!isset($applicants[$g["applicant"]])) $applicants[$g["applicant"]] = array("subjects"=>array($g["exam_subject"]=>$g["score"]),"extracts"=>array());
			else $applicants[$g["applicant"]]["subjects"][$g["exam_subject"]] = $g["score"];
		}
		foreach ($applicants_extracts_grade as $g)
			$applicants[$g["applicant"]]["extracts"][$g["exam_extract"]] = $g["score"];
		
		// get rules
		$rules = SQLQuery::create()->bypassSecurity()->select("ExamEligibilityRule")->execute();
		$rules_topics = SQLQuery::create()->bypassSecurity()->select("ExamEligibilityRuleTopic")->execute();
		for ($i = 0; $i < count($rules); $i++) {
			$rules[$i]["topics"] = array();
			foreach ($rules_topics as $t) if ($t["rule"] == $rules[$i]["id"]) array_push($rules[$i]["topics"], $t);
		}
		$root_rule = array("id"=>null,"next_rules"=>array());
		$this->buildExamRulesTree($rules, $root_rule);
		
		// apply rules for each applicant
		$passers = array();
		$loosers = array();
		foreach ($applicants as $id=>$grades) {
			set_time_limit(30);
			$passer = $this->applyExamRulesToApplicant($root_rule, $grades);
			if ($passer) array_push($passers, $id);
			else array_push($loosers, $id);
		}
		
		// exclude loosers
		if (count($loosers) > 0) {
			$to_exclude = SQLQuery::create()->bypassSecurity()
				->select("Applicant")
				->whereNotValue("Applicant","excluded",1) // not yet excluded
				->whereIn("Applicant", "people", $loosers)
				->field("people")
				->executeSingleField();
			if (count($to_exclude) > 0)
				SQLQuery::create()->bypassSecurity()->updateAllKeys("Applicant", $to_exclude, array(
					"excluded"=>1,
					"automatic_exclusion_step"=>"Written Exam",
					"automatic_exclusion_reason"=>"Failed",
					"exam_passer"=>0,
					"interview_center"=>null,
					"interview_session"=>null
				));
		}
		// put back passers who were excluded before
		if (count($passers) > 0) {
			$to_include = SQLQuery::create()->bypassSecurity()
				->select("Applicant")
				->whereValue("Applicant", "excluded", 1)
				->whereValue("Applicant", "automatic_exclusion_step", "Written Exam")
				->whereValue("Applicant", "automatic_exclusion_reason", "Failed")
				->whereIn("Applicant", "people", $passers)
				->field("people")
				->executeSingleField();
			if (count($to_include) > 0)
				SQLQuery::create()->bypassSecurity()->updateAllKeys("Applicant", $to_include, array("excluded"=>0,"automatic_exclusion_step"=>null,"automatic_exclusion_reason"=>null,"exam_passer"=>1));
			$to_pass = array();
			foreach ($passers as $people_id)
				if (!in_array($people_id, $to_include))
					array_push($to_pass, $people_id);
			if (count($to_pass) > 0)
				SQLQuery::create()->bypassSecurity()->updateAllKeys("Applicant", $to_pass, array("exam_passer"=>1));
			// automatically assign to interview centers, if applicable
			$exam_centers = SQLQuery::create()->bypassSecurity()->select("Applicant")->whereIn("Applicant","people",$passers)->whereNotNull("Applicant","people","exam_center")->field("exam_center")->distinct()->executeSingleField();
			if (count($exam_centers) > 0) {
				$links = SQLQuery::create()->bypassSecurity()->select("InterviewCenterExamCenter")->whereIn("InterviewCenterExamCenter","exam_center",$exam_centers)->execute();
				foreach ($links as $link) {
					$to_assign = SQLQuery::create()->bypassSecurity()
						->select("Applicant")
						->whereValue("Applicant","exam_center",$link["exam_center"])
						->whereNull("Applicant","interview_center")
						->whereIn("Applicant","people",$passers)
						->field("people")
						->executeSingleField()
						;
					if (count($to_assign) > 0)
						SQLQuery::create()->bypassSecurity()->updateAllKeys("Applicant", $to_assign, array("interview_center"=>$link["interview_center"]));
				}
			}
		}
		// signal
		$this->signalExamPassersAndLoosers($passers, $loosers);
	}
	private function buildExamRulesTree(&$rules, &$rule) {
		for ($i = 0; $i < count($rules); $i++) {
			$r = $rules[$i];
			if ($r["parent"] <> $rule["id"]) continue;
			$r["next_rules"] = array();
			array_push($rule["next_rules"], $r);
			array_splice($rules, $i, 1);
			$i--;
		}
		for ($i = 0; $i < count($rule["next_rules"]); $i++)
			$this->buildExamRulesTree($rules, $rule["next_rules"][$i]);
	}
	private function applyExamRulesToApplicant($rule, $grades) {
		if (isset($rule["topics"])) {
			$total = 0;
			foreach ($rule["topics"] as $t) {
				if ($t["subject"] <> null)
					$score = @$grades["subjects"][$t["subject"]];
				else
					$score = @$grades["extracts"][$t["extract"]];
				if ($score === null) $score = 0;
				$coef = @$t["coefficient"];
				if ($coef === null) $coef = 1; else $coef = floatval($coef);
				$total += $score * $coef;
			}
			$to_reach = floatval($rule["expected"]);
			if ($total < $to_reach) return false;
		}
		// we passed the rule, we need to pass one of the next ones
		if (count($rule["next_rules"]) == 0)
			return true; // we reach the end !
		foreach ($rule["next_rules"] as $r)
			if ($this->applyExamRulesToApplicant($r, $grades))
				return true;
			return false;
	}
	
		
	/**
	 * Remove an exam subject from database and all the related data
	 * This method checks the rights before performing the remove
	 * @param integer $exam_id
	 * @return boolean true if done
	 */
	public function removeSubject($exam_id){
		//check the rights
		$can_remove = $this->canEditExamSubjects();
		if($can_remove){
			// check no eligibility rule are using it
			$rules = SQLQuery::create()->select("ExamEligibilityRuleTopic")->whereValue("ExamEligibilityRuleTopic","subject", $exam_id)->execute();
			if (count($rules) > 0) {
				PNApplication::error("You cannot remove this subject because it is used by an eligibility rule. Please remove the rule first.");
				return false;
			}
			$parts_ids = SQLQuery::create()->select("ExamSubjectPart")->whereValue("ExamSubjectPart","exam_subject",$exam_id)->field("id")->executeSingleField();
			$extracts_ids = SQLQuery::create()->select("ExamSubjectExtractParts")->whereIn("ExamSubjectExtractParts","part",$parts_ids)->field("extract")->distinct()->executeSingleField();
			if (count($extracts_ids) > 0) {
				$rules = SQLQuery::create()->select("ExamEligibilityRuleTopic")->whereValue("ExamEligibilityRuleTopic","extract", $extracts_ids)->execute();
				if (count($rules) > 0) {
					PNApplication::error("You cannot remove this subject because it is used by an eligibility rule (using an extract of it). Please remove the rule first.");
					return false;
				}
			}
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
			PNApplication::error("You cannot modify the exam subjects");
		if(PNApplication::hasErrors())
			return false;
		return true;
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
		if (!PNApplication::$instance->user_management->hasRight("manage_exam_center")) {
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
		// rooms, sessions, and events are automatically removed by the datamodel
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
			return true;
		}
	}

	public function removeInterviewCenter($id){
		//Check the right from steps and user management
		if (!PNApplication::$instance->user_management->hasRight("manage_interview_center")) {
			PNApplication::error("You are not allowed to remove this Interview Center");
			return false;
		}
		// check if applicants are assigned to sessions
		$assigned_applicant = SQLQuery::create()
			->select("InterviewSession")
			->whereValue("InterviewSession", "interview_center", $id)
			->join("InterviewSession", "Applicant", array("event"=>"interview_session"))
			->whereNotNull("Applicant", "interview_session")
			->limit(0,1)
			->executeSingleRow();
		if ($assigned_applicant <> null) {
			PNApplication::error("You cannot remove this interview center because some applicants are assigned to sessions.");
			return false;
		}
		// sessions and events are automatically removed by the datamodel
		//Perform the remove
		SQLQuery::startTransaction();
		try {
			SQLQuery::create()
			->removeKey("InterviewCenter", $id);
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(PNApplication::hasErrors()){
			SQLQuery::rollbackTransaction();
			return false;
		} else {
			SQLQuery::commitTransaction();
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
	
	public function getCampaignFromApplicant($people_id, $bypass_security = false) {
		return SQLQuery::create()->bypassSecurity($bypass_security)
#DEV
			->noWarning()
#END
			->select("smlink_Applicant_People")
			->whereValue("smlink_Applicant_People", "root", $people_id)
			->join("smlink_Applicant_People", "SelectionCampaign", array("sm"=>"id"))
			->executeSingleRow();
	}
	
	public function signalExamPassersAndLoosers($passers, $loosers) {
		// unassign loosers from interview ?
	}
	public function signalInterviewPassersAndLoosers($passers, $loosers) {
	}
	
}
?>