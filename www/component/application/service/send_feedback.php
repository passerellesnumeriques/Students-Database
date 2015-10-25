<?php 
class service_send_feedback extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Send a feedback"; }
	public function inputDocumentation() { echo "title, text"; }
	public function outputDocumentation() { echo "none"; }

	/**
	 * @param application $component
	 */
	public function execute(&$component, $input) {
		require_once 'HTTPClient.inc';
		$req = new HTTPRequest();
		$req->setURL("https://api.github.com/repos/passerellesnumeriques/Students-Database/issues");
		$req->setHeader("Accept", "application/vnd.github.v3+json");
		$req->setHeader("User-Agent", "students-management-software");
		$req->postData("application/json", json_encode(array("body"=>$input["text"], "title"=>$input["title"])));
		$client = new HTTPClient();
		try {
			$responses = $client->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
				throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
			return;
		} catch (Exception $e) {
			PNApplication::error("Error sending feedback: ".$e->getMessage());
			return;
		}
	}
	
}
?>