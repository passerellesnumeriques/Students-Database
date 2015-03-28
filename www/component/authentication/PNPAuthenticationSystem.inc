<?php
require_once("AuthenticationSystem.inc");
/**
 * Connect remotely to the PN Philippines authentication system. 
 */
class PNPAuthenticationSystem extends AuthenticationSystem implements AuthenticationSystem_UserList, AuthenticationSystem_ResetPassword {

	/**
	 * Initialize the authentication system
	 */
	public function __construct() {
	}

	public function authenticate($username, $password, &$error) {
		if (PNApplication::$instance->local_domain == "PNP") {
			$url = "authentication";
		} else {
			$url = "authentication.pnp.studentscenter.org:800";
		}
		require_once 'HTTPClient.inc';
		$c = new HTTPClient();
		$req = new HTTPRequest();
		$req->setURL($url);
		$req->postForm(array("service"=>"authenticate","username"=>$username,"password"=>$password));
		$req->setHeader("User-Agent", "Students Management Software - Authentication from ".PNApplication::$instance->local_domain);
		try {
			$responses = $c->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
				throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
		} catch (Exception $e) {
			$error = $e->getMessage();
			return null;
		}
		$xml = @simplexml_load_string($resp->getBody());
		if ($xml === false || $xml === null) {
			$error = "Invalid response from remote system: ".toHTML($result);
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
	
	public function extendTokenExpiration($token) {
		$this->callService("extend_session", $token);
	}
	
	/**
	 * Call a service on the authentication server
	 * @param string $service name of the service
	 * @param string $token authentication token for subsequent services
	 * @param array $params parameters to send
	 * @return NULL|SimpleXMLElement the XML returned by the server, or null in case of failure
	 */
	private function callService($service, $token, $params) {
		if (PNApplication::$instance->local_domain == "PNP") {
			$url = "authentication";
		} else {
			$url = "authentication.pnp.studentscenter.org:800";
		}
		require_once 'HTTPClient.inc';
		$c = new HTTPClient();
		$req = new HTTPRequest();
		$req->setURL($url);
		$req->postForm(array_merge(array("service"=>$service,"token"=>$token), $params));
		$req->setHeader("User-Agent", "Students Management Software - Authentication from ".PNApplication::$instance->local_domain);
		try {
			$responses = $c->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
				throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
		} catch (Exception $e) {
			PNApplication::error("Error calling service ".$service." on authentication system: ".$e->getMessage());
			return null;
		}
		
		$xml = @simplexml_load_string($resp->getBody());
		if ($xml === false) {
			PNApplication::error("Invalid response from authentication system");
			return null;
		}
		return $xml;
	}
	/**
	 * Call a service requiring privileges on the authentication server
	 * @param string $service name of the service
	 * @param string $token authentication token for subsequent services
	 * @param array $params parameters to send
	 * @return NULL|SimpleXMLElement the XML returned by the server, or null in case of failure
	 */
	private function callServiceWithPrivileges($service, $token, $params) {
		if (PNApplication::$instance->local_domain == "PNP") {
			$url = "authentication";
		} else {
			$url = "authentication.pnp.studentscenter.org:800";
		}
		require_once 'HTTPClient.inc';
		$c = new HTTPClient();
		$req = new HTTPRequest();
		$req->setURL($url."/privileges/$service.sh");
		$req->postForm(array_merge(array("token"=>$token), $params));
		$req->setHeader("User-Agent", "Students Management Software - Authentication from ".PNApplication::$instance->local_domain);
		try {
			$responses = $c->send($req);
			$resp = $responses[count($responses)-1];
			if ($resp->getStatus() < 200 || $resp->getStatus() >= 300)
				throw new Exception("Server response: ".$resp->getStatus()." ".$resp->getStatusMessage());
		} catch (Exception $e) {
			PNApplication::error("Error calling service ".$service." on authentication system: ".$e->getMessage());
			return null;
		}
		
		$xml = @simplexml_load_string($resp->getBody());
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
	
	public function resetPassword($token, $username, $password) {
		$res = $this->callServiceWithPrivileges("reset_password", $token, array("username"=>$username,"password"=>$password));
		if ($res->getName() == "ok") return true;
		if ($res->getName() == "error")
			foreach ($res->attributes() as $name=>$value)
				if ($name == "message") return $value;
		return "Invalid response from authentication server".$res;
	}
}
?>