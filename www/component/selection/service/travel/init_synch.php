<?php 
class service_travel_init_synch extends Service {
	
	public function getRequiredRights() { return array("manage_selection_campaign"); }
	
	public function documentation() { echo "Inform the server that the user wants to initiate a synchronization"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "synch_key: a key to be send by the travel version to identify"; }
	
	public function execute(&$component, $input) {
		$row = SQLQuery::create()->bypassSecurity()->select("TravelVersion")->executeSingleRow();
		if ($row == null) {
			PNApplication::error("The campaign has not been locked for travelling");
			return;
		}
		if ($row["user"] <> PNApplication::$instance->user_management->user_id) {
			$people_id = PNApplication::$instance->user_management->getPeopleFromUser($row["user"]);
			$people = PNApplication::$instance->people->getPeople($people_id, true);
			PNApplication::error("This is ".$people["first_name"]." ".$people["last_name"]." who is travelling. Only ".($people["sex"] == "M" ? "him" : "her")." can synchronize the database");
			return;
		}
		// reset stored things
		if ($row["database_diff"] <> null) PNApplication::$instance->storage->remove_data($row["database_diff"]);
		$rows = SQLQuery::create()->bypassSecurity()->select("TravelVersionSynchStorage")->execute();
		SQLQuery::create()->bypassSecurity()->removeRows("TravelVersionSynchStorage", $rows);
		foreach ($rows as $row)
			PNApplication::$instance->storage->remove_data($row["storage"]);
		PNApplication::clearErrors();
		// generate key and save it
		$key = md5("".rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000).rand(0,10000));
		SQLQuery::create()->bypassSecurity()->updateByKey("TravelVersion", $row["user"], array("synch_key"=>$key,"synch_key_expiration"=>time()+3*60*60,"database_diff"=>null));
		echo "{synch_key:".json_encode($key)."}";
	}
	
}
?>