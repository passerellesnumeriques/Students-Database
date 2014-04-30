<?php
require_once("AuthenticationSystem.inc");
/**
 * Connect remotely to the PN Philippines authentication system. 
 */
class WebAuthenticationSystem extends AuthenticationSystem {

	public function __construct($system_url) {
		$this->system_url = $system_url;
	}

	/**
	 * @var string URL to access to the authentication system using HTTP protocol.
	 */
	private $system_url;

	public function authenticate($username, $password, &$error) {
		$c = curl_init($this->system_url);
		curl_setopt($c, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($c, CURLOPT_HEADER, FALSE);
		curl_setopt($c, CURLOPT_POST, TRUE);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("service"=>"authenticate","username"=>$username,"password"=>$password));
		$result = curl_exec($c);
		if ($result === false) {
			$error = curl_error($c);
			curl_close($c);
			return null;
		}
		curl_close($c);
		$xml = simplexml_load_string($result);
		if ($xml === false) {
			$error = "Invalid response from remote system";
			return null;
		}
		if ($xml->getName() <> "authentication") {
			$error = "Unexpected response from remote system";
			return null;
		}
		$result = null;
		$token = null;
		foreach ($xml->attributes() as $name=>$value) {
			if ($name == "result") $result = "".$value;
			else if ($name == "token") $token = "".$value;
		}
		if ($result <> "ok") {
			$error = "Invalid username or password";
			return null;
		}
		return $token;
	}
	
	private function callService($service, $token, $params) {
		$c = curl_init($this->system_url);
		curl_setopt($c, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($c, CURLOPT_HEADER, FALSE);
		curl_setopt($c, CURLOPT_POST, TRUE);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_POSTFIELDS, array_merge(array("service"=>$service,"token"=>$token), $params));
		$result = curl_exec($c);
		curl_close($c);
		if ($result === false) return null;
		$xml = simplexml_load_string($result);
		if ($xml === false) return null;
		return $xml;
	}
	
	public function getUserList($token) {
		$xml = $this->callService("get_users", $token, array());
		if ($xml === null) return null;
		$users = array();
		foreach ($xml->children() as $node) {
			if ($node->getName() <> "user") continue;
			$username = null;
			foreach ($node->attributes() as $name=>$value) {
				switch ($name) {
					case "username": $username = "".$value; break;
				}
			}
			if ($username == null) continue;
			array_push($users, array("username"=>$username));
		}
		return $users;
	}
	
}
?>