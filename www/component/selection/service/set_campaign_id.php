<?php
class service_set_campaign_id extends Service{
	public function get_required_rights(){return array("can_access_selection_data");}
	public function input_documentation(){
		echo "<ul>";
		echo "<li>campaign_id = the campaign_id to set</li>";
		echo "</ul>";
	}
	public function output_documentation(){
		echo "<ul>";
		echo "<li>{boolean} true if done</li>";
		echo "<li>{boolean} else false</li>";
		echo "</ul>";
	}
	public function documentation(){}//TODO
	public function execute(&$component,$input){
		if(isset($input['campaign_id'])){
			$component->setCampaignId($input["campaign_id"]);
			echo "true";
		}
		else echo "false";
	}
}	
?>