<?php 
class CreateSelectRenameRemoveCampaign extends TestFunctionalitiesScenario {
	
	public function getName() { return "Create Selection Campaign and select it"; }
	
	public function getCoveredFunctions() {
		return array("create_campaign","set_campaign_id","get_campaign_id","remove_campaign","rename_campaign");
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
			new Campaign_Rename_Remove_No_Manage (),
			new Campaign_Rename_Remove_Can_Manage (),
		);
	}
	
}

class Campaign_Create_Campaign_No_Manage extends TestStep{
	public function getName(){ return "Create a selection campaign with a user who can only access selection data, but cannot manage the selections campaigns";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test", "test_createCampaign_can_access", "");
		if($error <> null) return "Cannot login with user test_createCampaign_can_access";
		try{
			PNApplication::$instance->selection->create_campaign("createCampaign_Create_Campaign_No_Manage");
			return "Can create a campaign";
		} catch (Exception $e){}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Campaign_Create_Campaign_Can_Manage extends TestStep{
	public function getName(){return "Create a selection campaign with a user who can manage the selections campaigns"; }
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_manage", "");
		if($error <> null) return "Cannot login with user test_createCampaign_manage";
		try{
			$id = PNApplication::$instance->selection->create_campaign("Campaign_Create_Campaign_Can_Manage");
			$scenario_data["campaign_created_can_manage"] = $id;
		} catch (Exception $e){
			return "Cannot create campaign. Error was: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Campaign_Select_Campaign_No_Manage extends TestStep{
	public function getName(){return "Select a selection campaign with a user who can only access selection data, but cannot manage selection campaign";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_can_access", "");
		if($error <> null) return "Cannot login with user test_createCampaign_can_access";
		PNApplication::$instance->selection->set_campaign_id($scenario_data["campaign_created_can_manage"]);
		$current_id = PNApplication::$instance->selection->get_campaign_id();
		if($current_id != $scenario_data["campaign_created_can_manage"]) return "Cannot select a campaign";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Campaign_Select_Campaign_Can_Manage extends TestStep{
	public function getName(){return "Select a selection campaign with a user who can manage the selections campaigns";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_manage", "");
		if($error <> null) return "Cannot login with user test_createCampaign_manage";
		PNApplication::$instance->selection->set_campaign_id($scenario_data["campaign_created_can_manage"]);
		$current_id = PNApplication::$instance->selection->get_campaign_id();
		if($current_id != $scenario_data["campaign_created_can_manage"]) return "Cannot select a campaign";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Campaign_Rename_Remove_No_Manage extends TestStep{
	public function getName(){return "Rename and remove a selection campaign with a user who can only access selection data, but cannot manage selection campaign";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_can_access", "");
		if($error <> null) return "Cannot login with user test_createCampaign_can_access";
		try{
			PNApplication::$instance->selection->rename_campaign($scenario_data["campaign_created_can_manage"],"campaign_created_can_manage_2");
			return "Can rename a campaign";
		} catch(Exception $e){}
		try{
			PNApplication::$instance->selection->remove_campaign($scenario_data["campaign_created_can_manage"]);
			return "Can remove a campaign";
		} catch(Exception $e){}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class Campaign_Rename_Remove_Can_Manage extends TestStep{
	public function getName(){return "Rename and remove a selection campaign with a user who can manage the selections campaigns";}
	public function run(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_createCampaign_manage", "");
		if($error <> null) return "Cannot login with user test_createCampaign_manage";
		try{
			PNApplication::$instance->selection->rename_campaign($scenario_data["campaign_created_can_manage"],"campaign_created_can_manage_2");
		} catch(Exception $e){
			return "Cannot rename a campaign. Error was: ".$e->getMessage();
		}
		try{
			PNApplication::$instance->selection->remove_campaign($scenario_data["campaign_created_can_manage"]);
		} catch(Exception $e){
			return "Cannot remove a campaign. Error was: ".$e->getMessage();
		}
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

?>