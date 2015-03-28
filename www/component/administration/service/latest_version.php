<?php 
class service_latest_version extends Service {
	
	public function getRequiredRights() { return array("manage_application"); }
	
	public function documentation() { echo "Return the latest available version"; }
	public function inputDocumentation() { echo "none"; }
	public function outputDocumentation() { echo "<code>version</code>"; }
	
	public function execute(&$component, $input) {
#DEV
PNApplication::$instance->development->current_request()->no_process_time_warning = true;
#END
		require_once("update_urls.inc");
		$url = getLatestVersionURL();
		require_once 'HTTPClient.inc';
		$c = new HTTPClient();
		$req = new HTTPRequest();
		$req->setURL($url);
		try {
			$responses = $c->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
				throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
		} catch (Exception $e) {
			PNApplication::error("Error retrieving latest version from SourceForge: ".$e->getMessage());
			return;
		}
		echo "{version:".json_encode($resp->getBody())."}";
	}
	
}
?>