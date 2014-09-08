<?php
class service_remove_campaign extends Service{
	public function getRequiredRights(){return array("manage_selection_campaign");}
	public function inputDocumentation(){
		echo "<ul>";
		echo "<li>id = the campaign id</li>";
		echo "</ul>";
	}
	public function outputDocumentation(){
		echo "<ul>";
		echo "<li>{boolean} true if done</li>";
		echo "<li>{boolean} else false</li>";
		echo "</ul>";
	}
	public function documentation(){echo "Remove a selection campaign from the database and all the submodel tables";}
	public function mayUpdateSession() { return true; }
	public function execute(&$component,$input){
		if(isset($input["id"])){
			try{
				PNApplication::$instance->selection->removeCampaign($input["id"]);
			} catch(Exception $e) {
				PNApplication::error($e);
			}
			$to_echo = PNApplication::hasErrors() ? "false" : "true";
			echo $to_echo;
		}
		else echo "false";
	}
}	
?>