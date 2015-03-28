<?php 
class service_save_remote_access extends Service {
	
	public function getRequiredRights() { return array("manage_application"); }
	
	public function documentation() { echo "Save url and password to connect remotely to another domain."; }
	public function inputDocumentation() { echo "domain, url, password"; }
	public function outputDocumentation() { echo "version"; }
	
	public function execute(&$component, $input) {
		$domain = $input["domain"];
		$url = $input["url"];
		$password = $input["password"];
		
		if ($url == "" && $password == "") {
			// remove
			if (file_exists("data/domains_synch/$domain")) {
				require_once("component/application/Backup.inc");
				Backup::removeDirectory("data/domains_synch/$domain");
			}
			if (unlink("conf/$domain.remote"))
				echo "true";
			return;
		}
		
		if (substr($url,strlen($url)-1) <> "/") $url .= "/";
		
		// Step 1: get the version
		
		require_once 'HTTPClient.inc';
		$c = new HTTPClient();
		$req = new HTTPRequest();
		$req->setURL($url."dynamic/application/service/get_backup");
		$req->postForm(array("request"=>"get_list"));
		try {
			$responses = $c->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() == 403) {
				$version = trim($resp->getHeader("pn_version_changed"));
				if ($version == null) {
					PNApplication::error("We can connect to the URL, but this is not Students Management Software");
					return;
				}
			} else {
				if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
					throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
				PNApplication::error("We can connect to the URL, but this is not Students Management Software");
				return;
			}
		} catch (Exception $e) {
			PNApplication::error($e->getMessage());
			return;
		}
		
		// Step 2: connect using version cookie

		$req = new HTTPRequest();
		$req->setURL($url."dynamic/application/service/get_backup");
		$req->postForm(array("request"=>"get_list","password"=>$password));
		$req->setHeader("Cookie", "pnversion=$version");
		try {
			$responses = $c->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() < 200 || $resp->getStatus() >= 300) {
				if ($resp->getStatus() == 403)
					throw new Exception("We successfully connect, but the request was rejected: ".$resp->getStatusMessage());
				else
					throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
			}
		} catch (Exception $e) {
			PNApplication::error($e->getMessage());
			return;
		}

		if (file_exists("data/domains_synch/$domain/last_check"))
			@unlink("data/domains_synch/$domain/last_check");
		
		$f = fopen("conf/$domain.remote","w");
		fwrite($f, "<?php return array('url'=>'$url','password'=>\"".str_replace("\\","\\\\", str_replace('"','\\"', $password))."\"); ?>");
		fclose($f);
		echo "{version:".json_encode($version)."}";
	}
	
}
?>