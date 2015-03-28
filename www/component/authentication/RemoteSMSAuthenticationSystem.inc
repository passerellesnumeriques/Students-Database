<?php 
require_once("AuthenticationSystem.inc");
class RemoteSMSAuthenticationSystem extends AuthenticationSystem {
	
	public function __construct($domain, $url) {
		$this->domain = $domain;
		$this->url = $url;
	}
	
	private $domain;
	private $url;
	
	public function authenticate($username, $password, &$error) {
		$url = $this->url;
		if (substr($url,strlen($url)-1) <> "/") $url .= "/";
		require_once 'component/application/RemoteAccess.inc';
		$version = RemoteAccess::getDomainVersion($this->domain, $url);
		if ($version == null) {
			$error = "Unable to connect to ".$this->domain;
			return null;
		}
		$url .= "dynamic/authentication/service/auth";
		require_once 'HTTPClient.inc';
		$c = new HTTPClient();
		$req = new HTTPRequest();
		$req->setURL($url);
		$req->postForm(array("domain"=>$this->domain,"username"=>$username,"password"=>$password));
		$req->setHeader("User-Agent", "Students Management Software - Authentication from ".PNApplication::$instance->local_domain);
		$req->setHeader("Cookie", "pnversion=$version");
		try {
			$responses = $c->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
				throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
		} catch (Exception $e) {
			$error = "Error connecting to ".$this->domain.": ".$e->getMessage();
			return null;
		}
		
		$json = json_decode($resp->getBody(), true);
		if ($json === null) {
			$error = "Invalid response from ".$this->domain;
			return null;
		}
		if (isset($json["errors"]) && count($json["errors"]) > 0) {
			$error = $json["errors"][0];
			return null;
		}
		if ($json["result"] == null || !isset($json["result"]["token"])) {
			$error = "Invalid response from ".$this->domain;
			return null;
		}
		return $json["result"]["token"];
	}
	
}
?>