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
		set_time_limit(45);
		$url = $this->url;
		if (substr($url,strlen($url)-1) <> "/") $url .= "/";
		require_once 'component/application/RemoteAccess.inc';
		$version = RemoteAccess::getDomainVersion($this->domain, $url);
		if ($version == null) {
			$error = "Unable to connect to ".$this->domain;
			return null;
		}
		$url .= "dynamic/authentication/service/auth";
		$c = curl_init($url);
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_HEADER, FALSE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("domain"=>$this->domain,"username"=>$username,"password"=>$password));
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$version","User-Agent: Students Management Software - Authentication request from ".PNApplication::$instance->local_domain));
		$result = curl_exec($c);
		if ($result === false) {
			$error = "Error connecting to ".$this->domain.": ".curl_error($c);
			curl_close($c);
			return null;
		}
		curl_close($c);
		$json = json_decode($result, true);
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