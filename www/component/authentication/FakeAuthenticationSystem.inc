<?php
require_once("AuthenticationSystem.inc"); 
class FakeAuthenticationSystem extends AuthenticationSystem {
	
	public function authenticate($username, $password, &$error) {
		if ($username <> "invalid")
			return "1";
		$error = "Invalid username";
		return null;
	}
	
}
?>