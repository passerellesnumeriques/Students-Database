<?php
class GetSaveConfigGetCampaigns extends TestFunctionalitiesScenario {
	
	public function getName() { return "Get the config for a campaign, and all the campaigns set in the database"; }
	
	public function getCoveredFunctions() {
		return array("createCampaign","getConfig","getCampaigns");
	}
	
	public function getUsers() {
		return array(
			new TestUser("test_createCampaign_can_access", array("can_access_selection_data"=>true)),
			new TestUser("test_createCampaign_manage", array("manage_selection_campaign"=>true)),
		);
	}
	
	public function getSteps() {
		return array(
			new GetSaveConfigCampaigns_Can_Manage(),
			new GetSaveConfigCampaigns_No_Manage(),
			new SaveConfig_Can_Manage(),
			new SaveConfig_No_Manage(),
		);
	}
	
}
require_once 'component/selection/SelectionJSON.inc';
class GetSaveConfigCampaigns_Can_Manage extends TestFunctionalitiesStep{
	public function getName(){ return "Create a selection campaign, get the config and all the campaigns with a user who can manage the selections campaigns";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_manage", "");
		if($error <> null) return "Cannot login with user test_createCampaign_manage";
		try{
			$id = PNApplication::$instance->selection->createCampaign("Campaign_Create_Campaign_Can_Manage");
			$scenario_data["campaign_created_can_manage"] = $id;
		} catch (Exception $e){
			return "Cannot create campaign. Error was: ".$e->getMessage();
		}
		$config = PNApplication::$instance->selection->getConfig();
		if(PNApplication::$instance->has_errors()) return "Cannot get the config of the campaign";
		if($config == null || $config == array()) return "The config attribute was not set properly";
		
		$json_campaigns = SelectionJSON::Steps();
		if(PNApplication::$instance->has_errors()) return "Cannot get the campaigns from database";
		if($json_campaigns == "[]") return "The campaigns array was not set properly";
		
		$campaigns = PNApplication::$instance->selection->getCampaigns();
		if(PNApplication::$instance->has_errors()) return "Cannot get the campaigns from database";
		if($campaigns == array()) return "The campaigns array was not set properly";
		
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class GetSaveConfigCampaigns_No_Manage extends TestFunctionalitiesStep{
	public function getName(){ return "Create a selection campaign, get the config and all the campaigns with a user who can only access selection data, but cannot manage the selections campaigns";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_can_access", "");
		if($error <> null) return "Cannot login with user test_createCampaign_can_access";
		$id = $scenario_data["campaign_created_can_manage"];
		PNApplication::$instance->selection->setCampaignId($id);
		
		$config = PNApplication::$instance->selection->getConfig();
		if(PNApplication::has_errors()) return "Cannot get the config of the campaign";
		if($config == null || $config == array()) return "The config attribute was not set properly";
		$scenario_data["config"] = $config;
		
		$json_campaigns = $json_campaigns = SelectionJSON::Steps();
		if(PNApplication::$instance->has_errors()) return "Cannot get the campaigns from database";
		if($json_campaigns == "[]") return "The campaigns array was not set properly";
		
		$campaigns = PNApplication::$instance->selection->getCampaigns();
		if(PNApplication::$instance->has_errors()) return "Cannot get the campaigns from database";
		if($campaigns == array()) return "The campaigns array was not set properly";
		
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class SaveConfig_Can_Manage extends TestFunctionalitiesStep{
	public function getName(){return "Save a config with a user who can manage the selections campaigns";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_manage", "");
		if($error <> null) return "Cannot login with user test_createCampaign_manage";
		$id = $scenario_data["campaign_created_can_manage"];
		PNApplication::$instance->selection->setCampaignId($id);
		$fields = $scenario_data["config"];
		$final_fields = array();
		foreach($fields as $f){
			$name = null;
			$val = null;
			foreach($f as $index => $value){
				if($index == "name") $name = $value;
				if($index == "value") $val = json_encode($value);
			}
			$final_fields[$name] = $val;
		}
		
		$error = PNApplication::$instance->selection->saveConfig($final_fields);
		if($error <> null) return "Cannot save the config. Error was: ".$error->getMessage();
		
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class SaveConfig_No_Manage extends TestFunctionalitiesStep{
	public function getName(){return "Save a config with a user who cannot manage the selections campaigns, only access the selection data";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_can_access", "");
		if($error <> null) return "Cannot login with user test_createCampaign_can_access";
		$id = $scenario_data["campaign_created_can_manage"];
		$fields = $scenario_data["config"];
		PNApplication::$instance->selection->setCampaignId($id);
		$fields = $scenario_data["config"];
		$final_fields = array();
		foreach($fields as $f){
			$name = null;
			$val = null;
			foreach($f as $index => $value){
				if($index == "name") $name = $value;
				if($index == "value") $val = json_encode($value);
			}
			$final_fields[$name] = $val;
		}
		
		$error = PNApplication::$instance->selection->saveConfig($final_fields);
		if($error == null) return "Can save the config";
		
		PNApplication::$instance->user_management->logout();
		return null;
	}
}


?>