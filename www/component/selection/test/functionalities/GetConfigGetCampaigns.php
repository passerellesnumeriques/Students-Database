<?php
class GetConfigGetCampaigns extends TestFunctionalitiesScenario {
	
	public function getName() { return "Get the config for a campaign, and all the campaigns set in the database"; }
	
	public function getCoveredFunctions() {
		return array("create_campaign","get_config","get_json_campaigns","get_campaigns");
	}
	
	public function getUsers() {
		return array(
			new TestUser("test_createCampaign_can_access", array("can_access_selection_data"=>true)),
			new TestUser("test_createCampaign_manage", array("manage_selection_campaign"=>true)),
		);
	}
	
	public function getSteps() {
		return array(
			new GetConfigCampaigns_Can_Manage(),
			new GetConfigCampaigns_No_Manage(),
		);
	}
	
}

class GetConfigCampaigns_Can_Manage extends TestStep{
	public function getName(){ return "Create a selection campaign, get the config and all the campaigns with a user who can manage the selections campaigns";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_manage", "");
		if($error <> null) return "Cannot login with user test_createCampaign_manage";
		try{
			$id = PNApplication::$instance->selection->create_campaign("Campaign_Create_Campaign_Can_Manage");
			$scenario_data["campaign_created_can_manage"] = $id;
		} catch (Exception $e){
			return "Cannot create campaign. Error was: ".$e->getMessage();
		}
		$config = PNApplication::$instance->selection->get_config();
		if(PNApplication::$instance->has_errors()) return "Cannot get the config of the campaign";
		if($config == null || $config == array()) return "The config attribute was not set properly";
		
		$json_campaigns = PNApplication::$instance->selection->get_json_campaigns();
		if(PNApplication::$instance->has_errors()) return "Cannot get the campaigns from database";
		if($json_campaigns == "[]") return "The campaigns array was not set properly";
		
		$campaigns = PNApplication::$instance->selection->get_campaigns();
		if(PNApplication::$instance->has_errors()) return "Cannot get the campaigns from database";
		if($campaigns == array()) return "The campaigns array was not set properly";
		
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class GetConfigCampaigns_No_Manage extends TestStep{
	public function getName(){ return "Create a selection campaign, get the config and all the campaigns with a user who can only access selection data, but cannot manage the selections campaigns";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_can_access", "");
		if($error <> null) return "Cannot login with user test_createCampaign_can_access";
		$id = $scenario_data["campaign_created_can_manage"];
		$config = PNApplication::$instance->selection->get_config();
		if(!PNApplication::has_errors()) return "Can get the config of the campaign";
		if($config <> null) return "The config attribute is not set as null";
		
		$json_campaigns = PNApplication::$instance->selection->get_json_campaigns();
		if(PNApplication::$instance->has_errors()) return "Cannot get the campaigns from database";
		if($json_campaigns == "[]") return "The campaigns array was not set properly";
		
		$campaigns = PNApplication::$instance->selection->get_campaigns();
		if(PNApplication::$instance->has_errors()) return "Cannot get the campaigns from database";
		if($campaigns == array()) return "The campaigns array was not set properly";
		
		PNApplication::$instance->user_management->logout();
		return null;
	}
}


?>