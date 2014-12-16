<?php 
class service_download_backup_from_travel_install extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$username = @$_POST["username"];
		$session = @$_POST["session"];
		$token = @$_POST["token"];
		
		$i = strpos($token, "-");
		$rand1 = substr($token, 0, $i);
		$token = substr($token, $i+1);
		$i = strpos($token, "-");
		$ts = substr($token, 0, $i);
		$token = substr($token, $i+1);
		$i = strpos($token, "-");
		$rand2 = substr($token, 0, $i);
		$id = substr($token, $i+1);
		
		$value = PNApplication::$instance->application->getTemporaryData($id);
		if ($value <> $ts."/".$rand1."/".$session."/".$ts."/".$username."/".$rand2)
			die();

		// extend temporary data with token
		PNApplication::$instance->application->updateTemporaryData($id, $value);
		
		if ($_GET["type"] == "get_info") {
			// TODO generate the backup, with only needed stuff
			// TODO store it using storage
			// TODO send file size and id
		} else if ($_GET["type"] == "download") {
			$file_id = $_POST["id"];
			$from = $_POST["from"];
			$to = $_POST["to"];
			// TODO send part of the file according to given range
		}
	}
	
}
?>