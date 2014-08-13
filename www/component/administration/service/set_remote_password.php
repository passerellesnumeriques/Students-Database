<?php 
class service_set_remote_password extends Service {
	
	public function getRequiredRights() { return array("manage_application"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$pass = $input["password"];
		if (strlen($pass) < 10) {
			PNApplication::error("Password too short");
			return;
		}
		$f = fopen("conf/".PNApplication::$instance->local_domain.".password","w");
		fwrite($f, sha1($pass));
		fclose($f);
		echo "true";
	}
	
}
?>