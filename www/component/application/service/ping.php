<?php
class service_ping extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() { echo "Extend the session, and get recent events"; }
	public function inputDocumentation() { echo "nothing"; }
	public function outputDocumentation() { echo "nothing"; }
	public function execute(&$component, $input) {
		if (rand(0,5) == 0) {
			// from time to time, extend the authentication system token life (if needed)
			if (PNApplication::$instance->user_management->domain <> null)
				PNApplication::$instance->authentication->getAuthenticationSystem(PNApplication::$instance->user_management->domain)->extendTokenExpiration(PNApplication::$instance->user_management->auth_token);
		}
		if (rand(0,10) == 0) {
			if (PNApplication::$instance->cron->getLastCronExecution() < time()-30*60) {
				// execute cron tasks if needed, as it seems not yet configured, or having problems
				PNApplication::$instance->cron->executeMostNeededCronTask();
			}
		}
		echo "{ok:true";
		if (file_exists("maintenance_time")) {
			$maintenance = intval(file_get_contents("maintenance_time"));
			echo ",maintenance_coming:".$maintenance;
		}
		PNApplication::$instance->user_management->updateLastConnection();
		echo "}";
	}
	
} 
?>