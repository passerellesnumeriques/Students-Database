<?php
require_once("AuthenticationSystem.inc");
/**
 * Connect remotely to the PN Philippines authentication system. 
 */
class PNPAuthenticationSystem extends AuthenticationSystem implements AuthenticationSystem_UserList {

	/**
	 * Initialize the authentication system
	 */
	public function __construct() {
		if (PNApplication::$instance->local_domain == "PNP")
			$this->system_url = "authentication";
		else
			$this->system_url = "authentication.pnp.studentscenter.org:800";
	}

	public function authenticate($username, $password, &$error) {
		set_time_limit(45);
		$c = curl_init($this->system_url);
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($c, CURLOPT_HEADER, FALSE);
		curl_setopt($c, CURLOPT_POST, TRUE);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("service"=>"authenticate","username"=>$username,"password"=>$password));
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 25);
		$result = curl_exec($c);
		set_time_limit(30);
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
		set_time_limit(300);
		$c = curl_init($this->system_url);
		if (file_exists("conf/proxy")) include("conf/proxy");
		curl_setopt($c, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($c, CURLOPT_HEADER, FALSE);
		curl_setopt($c, CURLOPT_POST, TRUE);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 240);
		curl_setopt($c, CURLOPT_POSTFIELDS, array_merge(array("service"=>$service,"token"=>$token), $params));
		$result = curl_exec($c);
		set_time_limit(30);
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
				$value = "".$value;
				switch ($name) {
					case "username": $username = $value; break;
					case "fullname":
						$i = strpos($value, "/");
						if ($i !== false) {
							$j = strpos($value, "/", $i+1);
							if ($j !== false) {
								$info["People"]["first_name"] = substr($value,0,$i);
								$info["People"]["middle_name"] = substr($value,$i+1,$j-$i-1);
								$info["People"]["last_name"] = substr($value,$j+1);
							}
						}
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