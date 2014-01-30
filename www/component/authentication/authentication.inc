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
		$local = PNApplication::$instance->local_domain;
		switch ($domain) {
			case "PNP":
				if ($local == "PNP") {
					require_once("FakeAuthenticationSystem.inc");
					return new FakeAuthenticationSystem();
				}
				require_once("PNP_Remote_AuthenticationSystem.inc");
				return new PNP_Remote_AuthenticationSystem();
			default:
				require_once("FakeAuthenticationSystem.inc");
				return new FakeAuthenticationSystem();
		}
	}

}
?>