<?php
class service_remove_campaign extends Service{
	public function get_required_rights(){return array("manage_selection_campaign");}
	public function input_documentation(){
		echo "<ul>";
		echo "<li>id = the campaign id</li>";
		echo "</ul>";
	}
	public function output_documentation(){
		echo "<ul>";
		echo "<li>{boolean} true if done</li>";
		echo "<li>{boolean} else false</li>";
		echo "</ul>";
	}
	public function documentation(){echo "Remove a selection campaign from the database and all the submodel tables";}
	public function execute(&$component,$input){
		if(isset($input["id"])){
			try{
				PNApplication::$instance->selection->removeCampaign($input["id"]);
			} catch(Exception $e) {
				PNApplication::error($e);
			}
			$to_echo = PNApplication::has_errors() ? "false" : "true";
			echo $to_echo;
		}
		else echo "false";
	}
}	
?>