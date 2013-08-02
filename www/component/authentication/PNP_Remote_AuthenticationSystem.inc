<?php
require_once("AuthenticationSystem.inc");
class PNP_Remote_AuthenticationSystem extends AuthenticationSystem {

	public function __construct() {
		$this->base_url = "http://pncebu.dyndns.org:8000/authentication_system.php/";
	}

	private $base_url;

	public function authenticate($username, $password, &$error) {
		$c = curl_init($this->base_url."authenticate");
		curl_setopt($c, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($c, CURLOPT_HEADER, FALSE);
		curl_setopt($c, CURLOPT_POST, TRUE);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("username"=>$username,"password"=>$password));
		$result = curl_exec($c);
		if ($result === false) {
			$error = curl_error($c);
			curl_close($c);
			return null;
		}
		curl_close($c);
		$i = strpos($result, "\n");
		if ($i === FALSE) {
			$error = "Invalid response from remote system";
			return null;
		}
		return substr($result, $i+1);
	}

	public function get_user_list($token) { 
		$c = curl_init($this->base_url."user_list");
		curl_setopt($c, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($c, CURLOPT_HEADER, FALSE);
		curl_setopt($c, CURLOPT_POST, TRUE);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_POSTFIELDS, array("session"=>$token));
		$result = curl_exec($c);
		curl_close($c);
		if (substr($result, 0, 3) <> "<ok")
			return null;
		$users = array();
		$xml = simplexml_load_string($result);
		foreach ($xml->children() as $node) {
			if ($node->getName() <> "user") continue;
			$attrs = array("Student"=>false,"Staff"=>false);
			$username = null;
			foreach ($node->attributes() as $name=>$value) {
				if ($name == "username") $username = "".$value;
				else if ($name == "first_name" || $name == "middle_name" || $name == "last_name") {
					$s = "".$value;
					$words = explode(" ",$s);
					$s = "";
					foreach ($words as $w) {
						if (strlen($s) > 0) $s .= " ";
						$s .= strtoupper(substr($w,0,1)).strtolower(substr($w,1));
					}
					$attrs[$name] = $s;
				}
			}
			if ($username == null) continue;
			foreach ($node->children() as $n) {
				if ($n->getName() == "group") {
					$group = null;
					foreach ($n->attributes() as $name=>$value)
						if ($name == "name") { $group = "".$value; break; }
					if ($group == null) continue;
					if ($group == "staff")
						$attrs["Staff"] = true;
					else if (substr($group, 0, 5) == "csna_")
						$attrs["StudentsBatch"] = substr($group, 5);
					else if ($group == "students")
						$attrs["Student"] = true; 
				}
			}
			array_push($users, array($username, $attrs));
		}
		return $users;
	}
	
}
?>