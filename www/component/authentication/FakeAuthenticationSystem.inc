<?php
require_once("AuthenticationSystem.inc");
/**
 * Implementation of a fake authentication system, for testing purposes. The authentication always succeed, whatever username or password provided, except for the username <i>invalid</i>.
 */ 
class FakeAuthenticationSystem extends AuthenticationSystem {
	
	public function authenticate($username, $password, &$error) {
		if ($username <> "invalid")
			return "1";
		$error = "Invalid username";
		return null;
	}
	
}
?>