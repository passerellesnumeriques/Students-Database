<?php
class service_create_campaign extends Service{
	public function getRequiredRights(){return array("manage_selection_campaign");}
	public function documentation(){echo "Create a selection campaign";}
	public function inputDocumentation(){ echo "name[,programs]"; }
	public function outputDocumentation(){ echo "true on success"; }
	
	public function mayUpdateSession() { return true; }
	
	public function execute(&$component,$input){
		try{
			$key = $component->createCampaign($input["name"], @$input["programs"]);
		} catch(Exception $e) {
			PNApplication::error($e);
		}
		if(PNApplication::hasErrors()) echo "false";
		else echo "true";
	}
}	
?>