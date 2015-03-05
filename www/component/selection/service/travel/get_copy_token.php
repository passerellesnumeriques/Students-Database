<?php 
class service_travel_get_copy_token extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function documentation() { echo "Get a token for a travel copy"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "token on success, to be used to download the database"; }
	
	public function execute(&$component, $input) {
		$ts = time();
		$rand1 = rand(0,10000);
		$rand2 = rand(0,10000);
		$value = $ts."/".$rand1."/".session_id()."/".$ts."/".PNApplication::$instance->user_management->username."/".$rand2;
		$id = PNApplication::$instance->application->createTemporaryData($value);
		$token = $rand1."-".$ts."-".$rand2."-".$id;
		echo "{token:".json_encode($token)."}";
	}
	
}
?>