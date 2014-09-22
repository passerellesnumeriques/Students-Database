<?php 
class service_reset_password extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	/**
	 * @param $component user_management
	 */
	public function execute(&$component, $input) {
		$username = $input["username"];
		$password = $input["password"];
		$domain = isset($input["domain"]) ? $input["domain"] : PNApplication::$instance->local_domain;
		$token = isset($input["token"]) ? $input["token"] : null;
		
		if ($component->isInternalUser($domain, $username)) {
			if ($username <> $component->username || $component->domain <> PNApplication::$instance->local_domain) {
				if (!$component->has_right("manage_users")) {
					PNApplication::error("Access denied");
					return;
				}
			}
			$db = SQLQuery::getDataBaseAccessWithoutSecurity();
			$res = $db->execute("UPDATE `InternalUser` SET `password`=SHA1('".$db->escapeString($password)."') WHERE `username`='".$db->escapeString($username)."'");
			if ($res === false)
				echo "false";
			else
				echo "true";
			return;
		}
		
		if ($token == null && $domain == PNApplication::$instance->user_management->domain)
			$token = PNApplication::$instance->user_management->auth_token;
		$as = PNApplication::$instance->authentication->getAuthenticationSystem($domain);
		$res = $as->resetPassword($token, $username, $password);
		if ($res === true) {
			echo "true";
			return;
		}
		if ($res === null)
			PNApplication::error("Functionality not supported by authentication system");
		else
			PNApplication::error($res);
		echo "false";
		return;
	}
	
}
?>