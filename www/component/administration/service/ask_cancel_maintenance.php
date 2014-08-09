<?php 
class service_ask_cancel_maintenance extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Ask the administrator to cancel the scheduled maintenance"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "<code>origin</code>: the name of the administrator"; }
	
	public function execute(&$component, $input) {
		$me = PNApplication::$instance->people->getPeople(PNApplication::$instance->user_management->people_id);
		$f = fopen("maintenance/ask_cancel","a");
		fwrite($f, $me["first_name"]." ".$me["last_name"]." asked you to cancel this maintenance<br/>");
		fclose($f);
		$origin = file_get_contents("maintenance/origin");
		echo "{origin:".json_encode($origin)."}";
	}
	
	
}
?>