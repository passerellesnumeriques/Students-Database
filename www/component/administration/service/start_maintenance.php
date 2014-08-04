<?php 
class service_start_maintenance extends Service {
	
	public function getRequiredRights() { return array("manage_application"); }
	
	public function documentation() { echo "Put the software into maintenance mode."; }
	public function inputDocumentation() { echo "timing and password"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		if (file_exists("maintenance_time")) {
			PNApplication::error("A maintenance is already planned. You can remove the file 'maintenance_time' to cancel it.");
			return;
		}
		if (file_exists("maintenance/password")) {
			PNApplication::error("The application is already in maintenance mode.");
			return;
		}
		$timing = time()+intval($input["timing"])*60;
		$password = $input["password"];
		$f = fopen("maintenance_time","w");
		fwrite($f,$timing);
		fclose($f);
		$f = fopen("maintenance/password","w");
		fwrite($f,sha1($password));
		fclose($f);
		echo "true";
	}
	
}
?>