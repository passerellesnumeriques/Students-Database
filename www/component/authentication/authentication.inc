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
				require_once("WebAuthenticationSystem.inc");
				if ($local == "PNP")
					$url = "authentication";
				else
					$url = "authentication.pnp.studentscenter.org:800";
				return new WebAuthenticationSystem("http://$url/",function(&$info,$name,$value) {
					switch ($name) {
						case "fullname":
							$i = strpos($value, "/");
							if ($i !== false) {
								$j = strpos($value, "/", $i+1);
								if ($j !== false) {
									$info["People"]["first_name"] = substr($value,0,$i);
									$info["People"]["middle_name"] = substr($value,$i+1,$j-$i-1);
									$info["People"]["last_name"] = substr($value,$j+1);
								}
							}
							break;
					}
				});
			case "Dev": // TODO only for testing purposes
				require_once("WebAuthenticationSystem.inc");
				return new WebAuthenticationSystem("http://authentication.pnp.studentscenter.org:800/",function(&$info,$name,$value) {
					switch ($name) {
						case "fullname":
							$i = strpos($value, "/");
							if ($i !== false) {
								$j = strpos($value, "/", $i+1);
								if ($j !== false) {
									$info["People"]["first_name"] = substr($value,0,$i);
									$info["People"]["middle_name"] = substr($value,$i+1,$j-$i-1);
									$info["People"]["last_name"] = substr($value,$j+1);
								}
							}
							break;
					}
				});
			default:
				require_once("FakeAuthenticationSystem.inc");
				return new FakeAuthenticationSystem();
		}
	}

}
?>