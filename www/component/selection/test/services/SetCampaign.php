<?php
class SetCampaign extends TestServicesScenario{
	public function getName(){return "Select a campaign";}
	
	public function getUsers() {
		return array(
			new TestUser("test_SetCampaign_can_access", array("can_access_selection_data"=>true)),
			new TestUser("test_SetCampaign_manage", array("manage_selection_campaign"=>true)),
		);
	}
	
	public function getSteps(){
		return array(
			new SetCampaign_Test_Select_No_Manage(),
			new SetCampaign_Test_Select_Can_Manage(),
		);
	}
	
	protected function init_database(&$scenario_data){
		// Create one selection campaign into the database
		$key = SQLQuery::create()->bypass_security()->insert("SelectionCampaign",array("name"=>"Test_SetCampaign"));
		$scenario_data["id"] = $key;
		if(!$key <> null) return "The campaign was not inserted properly into the database";
	}
}

class SetCampaign_Test_Select_No_Manage extends TestServicesStep{
	public function getName(){return "Select a campaign with a user who can only access the selection data";}
	
	public function initializationStep(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_SetCampaign_can_access","");
		if($error <> null) return "Cannot login with test_SetCampaign_can_access user: ".$error;
		//return var_dump($scenario_data["id"]);
		return null;
	}
	
	public function getServiceName(){return "setCampaignId";}
	
	public function getServiceInput(&$scenario_data){return "{campaign_id:'".$scenario_data["id"]."'}";}
	
	public function getJavascriptToCheckServiceOutput($scenario_data){
		return "if(errors) return 'Error: last error was: '+errors[errors.length -1]; if(!result) return 'Cannot set the id'; return null;";
	}
	
	public function finalizationStep(&$scenario_data){
		$attribute_id = PNApplication::$instance->selection->getCampaignId();
		if($attribute_id != $scenario_data["id"]) return "The campaign id attribute was not set properly: attribute value is ".$attribute_id." and the id created is ".$scenario_data["id"].".";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class SetCampaign_Test_Select_Can_Manage extends TestServicesStep{
	public function getName(){return "Select a campaign with a user who can manage selection campaign";}
	
	public function initializationStep(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_SetCampaign_manage","");
		if($error <> null) return "Cannot login with test_SetCampaign_manage user: ".$error;
		return null;
	}
	
	public function getServiceName(){return "setCampaignId";}
	
	public function getServiceInput(&$scenario_data){return "{campaign_id:'".$scenario_data["id"]."'}";}
	
	public function getJavascriptToCheckServiceOutput($scenario_data){
		return "if(errors) return 'Error: last error was: '+errors[errors.length -1]; if(!result) return 'Cannot set the id'; return null;";
	}
	
	public function finalizationStep(&$scenario_data){
		$attribute_id = PNApplication::$instance->selection->getCampaignId();
		if($attribute_id != $scenario_data["id"]) return "The campaign id attribute was not set properly: attribute value is ".$attribute_id." and the id created is ".$scenario_data["id"].".";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
?>