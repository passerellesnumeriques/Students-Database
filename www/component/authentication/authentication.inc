<?php
class authentication extends Component {

	/**
	 * Return the authentication system for the given domain.
	 * @param string $domain
	 * @return AuthenticationSystem
	 * @not_tested
	 */
	public function get_authentication_system($domain) {
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