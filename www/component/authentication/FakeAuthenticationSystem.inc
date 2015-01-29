<?php
require_once("AuthenticationSystem.inc");
/**
 * Implementation of a fake authentication system, for testing purposes. The authentication always succeed, whatever username or password provided, except for the username <i>invalid</i>.
 */ 
class FakeAuthenticationSystem extends AuthenticationSystem implements AuthenticationSystem_UserList {
	
	public function authenticate($username, $password, &$error) {
		if ($username <> "invalid")
			return "1";
		$error = "Invalid username";
		return null;
	}
	
	public function getUserList($token) {
		return array(
			array("username"=>"fake.user1","info"=>array("People"=>array("first_name"=>"Fake","last_name"=>"User 1"))),
			array("username"=>"fake.user2","info"=>array("People"=>array("first_name"=>"Fake","last_name"=>"User 2"))),
			array("username"=>"fake.user3","info"=>array("People"=>array("first_name"=>"Fake","last_name"=>"User 3"))),
		);
	}
	
}
?>