<?php 
class service_lock_campaign extends Service {
	
	public function getRequiredRights() { return array("manage_selection_campaign"); }
	
	public function documentation() { echo "Lock the current selection campaign"; }
	public function inputDocumentation() { echo "reason"; }
	public function outputDocumentation() { echo "token on success, to be used to download the database"; }
	
	public function mayUpdateSession() { return true; } // to update the status in the selection component
	
	public function execute(&$component, $input) {
		if ($component->freezeCampaign($input["reason"])) {
			$ts = time();
			$rand1 = rand(0,10000);
			$rand2 = rand(0,10000);
			$value = $ts."/".$rand1."/".session_id()."/".$ts."/".PNApplication::$instance->user_management->username."/".$rand2;
			$id = PNApplication::$instance->application->createTemporaryData($value);
			$token = $rand1."-".$ts."-".$rand2."-".$id;
			$uid = md5("".rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000));
			$rows = SQLQuery::create()->bypassSecurity()->select("TravelVersion")->execute();
			if (count($rows) > 0) SQLQuery::create()->bypassSecurity()->removeRows("TravelVersion",$rows);
			SQLQuery::create()->bypassSecurity()->insert("TravelVersion",array("uid"=>$uid,"user"=>PNApplication::$instance->user_management->user_id));
			echo "{token:".json_encode($token).",uid:".json_encode($uid)."}";
		}
	}
	
}
?>