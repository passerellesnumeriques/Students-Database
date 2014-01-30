<?php
class CreateRenameRemoveCampaign extends TestServicesScenario{
	public function getName(){return "Manage campaign";}
	
	public function getUsers() {
		return array(
			new TestUser("test_CreateRenameRemove_can_access", array("can_access_selection_data"=>true)),
			new TestUser("test_CreateRenameRemoveCampaign_manage", array("manage_selection_campaign"=>true)),
		);
	}
	
	public function getSteps() {
		return array(
			new CreateRenameRemoveCampaign_Test_Create_No_Right(),
			new CreateRenameRemoveCampaign_Test_Create_With_Right(),
			new CreateRenameRemoveCampaign_Test_Rename_No_Right(),
			new CreateRenameRemoveCampaign_Test_Rename_With_Right(),
			new CreateRenameRemoveCampaign_Test_Remove_No_Right(),
			new CreateRenameRemoveCampaign_Test_Remove_With_Right(),
		);
	}
	//CreateRenameRemoveCampaign_Test_Create_With_Right
	//new CreateRenameRemoveCampaign_Test_Rename_With_Right(),
}

class CreateRenameRemoveCampaign_Test_Create_No_Right extends TestServicesStep{
	public function getName(){return "Create a campaign with a user who is not allowed but can access the selection data";}
	public function initializationStep(&$scenario_data){
		$err = PNApplication::$instance->user_management->login("Test","test_CreateRenameRemove_can_access","");
		if ($err <> null) return "Cannot login with test_CreateRenameRemove_can_access: ".$err;
		return null;
	}
	
	public function getServiceName(){return "createCampaign";}
	public function getServiceInput(&$scenario_data){return "{name:'test_error'}";}
	public function getJavascriptToCheckServiceOutput($scenario_data){
		return "if(!errors || errors.length == 0) return 'No error returned';
				if(result) return 'Can create a campaign';
				return null;
		";
	}
	public function finalizationStep(&$scenario_data){
		if(SQLQuery::create()->bypassSecurity()->select("SelectionCampaign")->field("id")->where("name","test_error")->executeSingleRow() <> null) return "No error but the campaign has been created in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class CreateRenameRemoveCampaign_Test_Create_With_Right extends TestServicesStep{
	public function getName(){return "Create a campaign with a user who can manage the selection campaigns";}
	public function initializationStep(&$scenario_data){
		$err = PNApplication::$instance->user_management->login("Test","test_CreateRenameRemoveCampaign_manage","");
		if ($err <> null) return "Cannot login with test_CreateRenameRemoveCampaign_manage: ".$err;
		return null;
	}
	
	public function getServiceName(){return "createCampaign";}
	public function getServiceInput(&$scenario_data){return "{name:'test_ok'}";}
	public function getJavascriptToCheckServiceOutput($scenario_data){
		return "if(errors) return 'Error: last error was '+errors[errors.length -1];
				if(!result) return 'Cannot create a campaign';
				return null;
		";
	}
	public function finalizationStep(&$scenario_data){
		$id = SQLQuery::create()->bypassSecurity()->select("SelectionCampaign")->field("id")->field("name")->where("name","test_ok")->execute();
		if(!isset($id[0]["id"])) return "The campaign was not created properly in the database";
		$scenario_data["id"] = $id[0]["id"];
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class CreateRenameRemoveCampaign_Test_Rename_No_Right extends TestServicesStep{
	public function getName(){return "Rename a campaign with a user who is not allowed but can access selection data";}
	public function initializationStep(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_CreateRenameRemove_can_access","");
		if ($error <> null) return "Cannot login with test_CreateRenameRemove_can_access: ".$error;
		return null;
	}
	
	public function getServiceName(){return "set_campaign_name";}
	public function getServiceInput(&$scenario_data){return "{id:'".$scenario_data["id"]."',name:'test_error_2'}";}
	public function getJavascriptToCheckServiceOutput($scenario_data){
		return "if(!errors || errors.length == 0) return 'No error returned';
				if(result) return 'Can rename a campaign';
				return null;
		";
	}
	public function finalizationStep(&$scenario_data){
		if(SQLQuery::create()->bypassSecurity()->select("SelectionCampaign")->field("name")->where("id",$scenario_data["id"])->executeSingleValue() == "test_error_2")
			return "Rename service returned an error but the name was set in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class CreateRenameRemoveCampaign_Test_Rename_With_Right extends TestServicesStep{
	public function getName(){return "Rename a campaign with a user who can manage selection campaigns";}
	public function initializationStep(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_CreateRenameRemoveCampaign_manage","");
		if ($error <> null) return "Cannot login with test_CreateRenameRemoveCampaign_manage: ".$error;
		return null;
	}
	
	public function getServiceName(){return "set_campaign_name";}
	public function getServiceInput(&$scenario_data){return "{id:'".$scenario_data["id"]."',name:'test_ok_2'}";}
	public function getJavascriptToCheckServiceOutput($scenario_data){
		return "if(errors) return 'Error. Last error was '+errors[errors.length -1];
				if(!result) return 'No error was returned but empty result';
				return null;
		";
	}
	public function finalizationStep(&$scenario_data){
		if(SQLQuery::create()->bypassSecurity()->select("SelectionCampaign")->field("name")->where("id",$scenario_data["id"])->executeSingleValue() <> "test_ok_2")
			return "Rename service returned no error but the name was not set in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class CreateRenameRemoveCampaign_Test_Remove_No_Right extends TestServicesStep{
	public function getName(){return "Remove a campaign with a user who is not allowed but can access selection data";}
	public function initializationStep(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_CreateRenameRemove_can_access","");
		if ($error <> null) return "Cannot login with test_CreateRenameRemove_can_access: ".$error;
		return null;
	}
	public function getServiceName(){return "removeCampaign";}
	public function getServiceInput(&$scenario_data){return "{id:'".$scenario_data["id"]."'}";}
	public function getJavascriptToCheckServiceOutput($scenario_data){
		return "if(!errors || errors.length == 0) return 'No error returned';
				if(result) return 'Can remove a campaign';
				return null;
		";
	}
	public function finalizationStep(&$scenario_data){
		$camp = SQLQuery::create()->bypassSecurity()->select("SelectionCampaign")->field("id")->where("id",$scenario_data['id'])->execute();
		if(!isset($camp[0]["id"])) return "Remove service returned an error but the campaign was removed in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}

class CreateRenameRemoveCampaign_Test_Remove_With_Right extends TestServicesStep{
	public function getName(){return "Remove a campaign with a user who can manage selection campaigns";}
	public function initializationStep(&$scenario_data){
		$error = PNApplication::$instance->user_management->login("Test","test_CreateRenameRemoveCampaign_manage","");
		if ($error <> null) return "Cannot login with test_CreateRenameRemoveCampaign_manage: ".$error;
		return null;
	}
	public function getServiceName(){return "removeCampaign";}
	public function getServiceInput(&$scenario_data){return "{id:'".$scenario_data["id"]."'}";}
	public function getJavascriptToCheckServiceOutput($scenario_data){
		return "if(errors) return 'Error. Last error was: '+errors[errors.length -1];
				if(!result) return 'No error was returned but empty result';
				return null;
		";
	}
	public function finalizationStep(&$scenario_data){
		$camp = SQLQuery::create()->bypassSecurity()->select("SelectionCampaign")->field("id")->where("id",$scenario_data['id'])->execute();
		if(isset($camp[0]["id"])) return "Remove service returned no error but the campaign was not removed in the database";
		PNApplication::$instance->user_management->logout();
		return null;
	}
}
?>