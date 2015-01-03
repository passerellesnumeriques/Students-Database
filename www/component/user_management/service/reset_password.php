<?php 
class service_reset_password extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Change password for a user"; }
	public function inputDocumentation() {
?>
<ul>
<li><code>username</code>: user to change</li>
<li><code>password</code>: new password</li>
<li><code>domain</code>: optional, if not set the local domain is used</li>
<li><code>token</code>: optional, token from authentication system of the domain.</li>
</ul>
<?php
	}
	public function outputDocumentation() { echo "true on success"; }
	
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
				if (!$component->hasRight("manage_users")) {
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