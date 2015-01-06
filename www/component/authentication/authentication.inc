<?php
/**
 * Provide functionalities to authenticate a user, using one authentication system per domain/PN Center.
 */
class authentication extends Component {

	/**
	 * Return the authentication system for the given domain.
	 * @param string $domain the domain
	 * @return AuthenticationSystem the authentication system of the given domain
	 * @not_tested
	 */
	public function getAuthenticationSystem($domain) {
		if ($domain <> PNApplication::$instance->local_domain) {
			// not local, let's check if we can contact remotely
			if (file_exists("conf/$domain.remote")) {
				$remote_access = include("conf/$domain.remote");
				$url = $remote_access['url'];
				require_once("RemoteSMSAuthenticationSystem.inc");
				return new RemoteSMSAuthenticationSystem($domain, $url);
			}
		}
		switch ($domain) {
			case "PNP":
				require_once("PNPAuthenticationSystem.inc");
				return new PNPAuthenticationSystem();
			case "PNC":
				require_once("LDAPAuthenticationSystem.inc");
				return new LDAPAuthenticationSystem("kep.pnc.lan", array("PNC","LAN"));
			default:
				require_once("FakeAuthenticationSystem.inc");
				return new FakeAuthenticationSystem();
		}
	}

}
?>