<?php
require_once("AuthenticationSystem.inc");
/**
 * Connect remotely to the PN Philippines authentication system. 
 */
class WebAuthenticationSystem extends AuthenticationSystem {

	/**
	 * Initialize the authentication system
	 * @param string $system_url URL to access to the authentication system using HTTP protocol
	 * @param function $populate_info callback to populate user information from server response
	 */
	public function __construct($system_url, $populate_info = null) {
		$this->system_url = $system_url;
		$this->populate_info = $populate_info;
	}

	/** @var string URL to access to the authentication system using HTTP protocol */
	private $system_url;
	/** @var function callback to populate user information from server response */
	private $populate_info;

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
	
	/**
	 * Call a service on the authentication server
	 * @param string $service name of the service
	 * @param string $token authentication token for subsequent services
	 * @param array $params parameters to send
	 * @return NULL|SimpleXMLElement the XML returned by the server, or null in case of failure
	 */
	private function callService($service, $token, $params) {
		$c = curl_init($this->system_url);
		curl_setopt($c, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($c, CURLOPT_HEADER, FALSE);
		curl_setopt($c, CURLOPT_POST, TRUE);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_POSTFIELDS, array_merge(array("service"=>$service,"token"=>$token), $params));
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error("Error calling service ".$service." on authentication system: ".curl_error($c));
			curl_close($c);
			return null;
		}
		curl_close($c);
		$xml = @simplexml_load_string($result);
		if ($xml === false) {
			PNApplication::error("Invalid response from authentication system");
			return null;
		}
		return $xml;
	}
	
	public function getUserList($token) {
		$xml = $this->callService("get_users", $token, array());
		if ($xml === null) return null;
		$users = array();
		foreach ($xml->children() as $node) {
			if ($node->getName() <> "user") continue;
			$username = null;
			$info = array("People"=>array());
			foreach ($node->attributes() as $name=>$value) {
				switch ($name) {
					case "username": $username = "".$value; break;
					default:
						if ($this->populate_info <> null)
							call_user_func_array($this->populate_info, array(&$info, $name, "".$value));
						break;
				}
			}
			if ($username == null) continue;
			$groups = array();
			foreach ($node->children() as $n) {
				if ($n->getName() <> "group") continue;
				$group_name = null;
				foreach ($n->attributes() as $name=>$value) {
					switch ($name) {
						case "name": $group_name = "".$value; break;
					}
				}
				if ($group_name <> null)
					array_push($groups, $group_name);
			}
			array_push($users, array("username"=>$username,"groups"=>$groups,"info"=>$info));
		}
		return $users;
	}
	
}
?>