<?php 
class CreateSelectRenameRemoveCampaign extends TestFunctionalitiesScenario {
	
	public function getName() { return "Create Selection Campaign and select it"; }
	
	public function getCoveredFunctions() {
		return array("createCampaign","setCampaignId","getCampaignId","removeCampaign","renameCampaign");
	}
	
	public function getUsers() {
		return array(
			new TestUser("test_createCampaign_can_access", array("can_access_selection_data"=>true)),
			new TestUser("test_createCampaign_manage", array("manage_selection_campaign"=>true)),
		);
	}
	
	public function getSteps() {
		return array(
			new Campaign_Create_Campaign_No_Manage(),
			new Campaign_Create_Campaign_Can_Manage(),
			new Campaign_Select_Campaign_No_Manage (),
			new Campaign_Select_Campaign_Can_Manage (),
			new Calendar_Get_Calendar_No_manage(),
			new Calendar_Get_Calendar_Can_manage(),
			new Campaign_Rename_Remove_No_Manage (),
			new Campaign_Rename_Remove_Can_Manage (),
		);
	}
	
}

class Campaign_Create_Campaign_No_Manage extends TestFunctionalitiesStep{
	public function getName(){ return "Create a selection campaign with a user who can only access selection data, but cannot manage the selections campaigns";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test", "test_createCampaign_can_access", "");
		if($error <> null) return "Cannot login with user test_createCampaign_can_access";
		try{
			PNApplication::$instance->selection->createCampaign("createCampaign_Create_Campaign_No_Manage");
			if(!PNApplication::has_errors())return "Can create a campaign";
			else PNApplication::clear_errors();
		} catch (Exception $e){}
		if(SQLQuery::create()->bypass_security()->select("SelectionCampaign")->field("name")->where("name","createCampaign_Create_Campaign_No_Manage")->execute_single_value() <> null) return "The campaign was created in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Campaign_Create_Campaign_Can_Manage extends TestFunctionalitiesStep{
	public function getName(){return "Create a selection campaign with a user who can manage the selections campaigns"; }
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_manage", "");
		if($error <> null) return "Cannot login with user test_createCampaign_manage";
		try{
			$id = PNApplication::$instance->selection->createCampaign("Campaign_Create_Campaign_Can_Manage");
			$scenario_data["campaign_created_can_manage"] = $id;
		} catch (Exception $e){
			return "Cannot create campaign. Error was: ".$e->getMessage();
		}
		if(SQLQuery::create()->bypass_security()->select("SelectionCampaign")->field("name")->where("id",$id)->execute_single_value() != "Campaign_Create_Campaign_Can_Manage") return "The campaign was not created in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Campaign_Select_Campaign_No_Manage extends TestFunctionalitiesStep{
	public function getName(){return "Select a selection campaign with a user who can only access selection data, but cannot manage selection campaign";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_can_access", "");
		if($error <> null) return "Cannot login with user test_createCampaign_can_access";
		PNApplication::$instance->selection->setCampaignId($scenario_data["campaign_created_can_manage"]);
		$current_id = PNApplication::$instance->selection->getCampaignId();
		if($current_id != $scenario_data["campaign_created_can_manage"]) return "Cannot select a campaign";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Campaign_Select_Campaign_Can_Manage extends TestFunctionalitiesStep{
	public function getName(){return "Select a selection campaign with a user who can manage the selections campaigns";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_manage", "");
		if($error <> null) return "Cannot login with user test_createCampaign_manage";
		PNApplication::$instance->selection->setCampaignId($scenario_data["campaign_created_can_manage"]);
		$current_id = PNApplication::$instance->selection->getCampaignId();
		if($current_id != $scenario_data["campaign_created_can_manage"]) return "Cannot select a campaign";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Calendar_Get_Calendar_No_manage extends TestFunctionalitiesStep{
	public function getName(){return "Get the calendar id linked to the campaign with a user who can only access selection data, but cannot manage selection campaign";}
	public function run(&$scenario_data){
		$calendar_id = "notYet";
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_can_access", "");
		if($error <> null) return "Cannot login with user test_createCampaign_can_access";
		PNApplication::$instance->selection->setCampaignId($scenario_data["campaign_created_can_manage"]);
		$calendar_id = PNApplication::$instance->selection->getCalendarId();
		if($calendar_id == "notYet") return "Nothing was returned by the getCalendarId method";
		if($calendar_id == null) return "The calendar_id attribute was not set when the campaign was created. Its current value is ".$calendar_id;
		if($calendar_id != SQLQuery::create()->bypass_security()->select("SelectionCampaign")->field("calendar")->where("id",$scenario_data["campaign_created_can_manage"])->execute_single_value()) return "The calendar_id attribute set does not match with the one in the database";
		$scenario_data["calendar_id"] = $calendar_id;
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Calendar_Get_Calendar_Can_manage extends TestFunctionalitiesStep{
	public function getName(){return "Get the calendar id linked to the campaign with a user who can manage the selections campaigns";}
	public function run(&$scenario_data){
		$calendar_id = "notYet";
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_manage", "");
		if($error <> null) return "Cannot login with user test_createCampaign_manage";
		PNApplication::$instance->selection->setCampaignId($scenario_data["campaign_created_can_manage"]);
		$calendar_id = PNApplication::$instance->selection->getCalendarId();
		if($calendar_id == "notYet") return "Nothing was returned by the getCalendarId method";
		if($calendar_id == null) return "The calendar_id attribute was not set when the campaign was created. Its current value is ".$calendar_id;
		if($calendar_id != SQLQuery::create()->bypass_security()->select("SelectionCampaign")->field("calendar")->where("id",$scenario_data["campaign_created_can_manage"])->execute_single_value()) return "The calendar_id attribute set does not match with the one in the database";
		$scenario_data["calendar_id"] = $calendar_id;
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Campaign_Rename_Remove_No_Manage extends TestFunctionalitiesStep{
	public function getName(){return "Rename and remove a selection campaign with a user who can only access selection data, but cannot manage selection campaign";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_can_access", "");
		if($error <> null) return "Cannot login with user test_createCampaign_can_access";
		try{
			PNApplication::$instance->selection->renameCampaign($scenario_data["campaign_created_can_manage"],"campaign_created_can_manage_2");
			return "Can rename a campaign";
		} catch(Exception $e){}
		if(SQLQuery::create()->bypass_security()->select("SelectionCampaign")->field("name")->where("id",$scenario_data["campaign_created_can_manage"])->execute_single_value() == "campaign_created_can_manage_2") return "The name was set in the database";
		try{
			PNApplication::$instance->selection->removeCampaign($scenario_data["campaign_created_can_manage"]);
			return "Can remove a campaign";
		} catch(Exception $e){}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Campaign_Rename_Remove_Can_Manage extends TestFunctionalitiesStep{
	public function getName(){return "Rename and remove a selection campaign with a user who can manage the selections campaigns";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_manage", "");
		if($error <> null) return "Cannot login with user test_createCampaign_manage";
		try{
			PNApplication::$instance->selection->renameCampaign($scenario_data["campaign_created_can_manage"],"campaign_created_can_manage_2");
		} catch(Exception $e){
			return "Cannot rename a campaign. Error was: ".$e->getMessage();
		}
		if(SQLQuery::create()->bypass_security()->select("SelectionCampaign")->field("name")->where("id",$scenario_data["campaign_created_can_manage"])->execute_single_value() != "campaign_created_can_manage_2") return "The name was not set in the database";
		try{
			PNApplication::$instance->selection->removeCampaign($scenario_data["campaign_created_can_manage"]);
		} catch(Exception $e){
			return "Cannot remove a campaign. Error was: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

?>