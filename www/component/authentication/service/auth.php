<?php 
class service_auth extends Service {
	public function documentation() {
?>Authenticate a user on a given domain.<?php 		
	}
	public function get_required_rights() {
		return array(); // everyone can access
	}
	public function input_documentation() {
?>
<ul>
	<li><code>domain</code>: domain of the user to authenticate</li>
	<li><code>username</code></li>
	<li><code>password</code></li>
</ul>
<?php 
	}
	public function output_documentation() {
?>On success, the service returns a <i>token</i> that may be used for subsequent requests.<?php 
	}
	public function execute(&$component, $input) {
		$domain = $input["domain"];
		$username = $input["username"];
		$password = $input["password"];
		
		$auth = $component->getAuthenticationSystem($domain);
		if ($auth == null) {
			PNApplication::error(get_locale("Invalid domain"));
			return true;
		}
		$error = "";
		$token = $auth->authenticate($username, $password,$error);
		if ($token == null) {
			PNApplication::error(get_locale("Authentication failed").": ".$error);
			return true;
		}
		echo "{token:".json_encode($token)."}";
	}
}
?>