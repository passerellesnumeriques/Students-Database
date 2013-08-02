<?php
require_once("AuthenticationSystem.inc"); 
class FakeAuthenticationSystem extends AuthenticationSystem {
	
	public function authenticate($username, $password, &$error) {
		return "1";
	}
	
}
?>