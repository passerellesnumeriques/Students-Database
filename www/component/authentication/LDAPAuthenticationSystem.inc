<?php
require_once("AuthenticationSystem.inc");
/**
 * Implementation of AuthenticationSystem using LDAP
 */ 
class LDAPAuthenticationSystem extends AuthenticationSystem implements AuthenticationSystem_UserList {

	/**
	 * Initialize the LDAP authentication system
	 * @param string $server hostname of the LDAP server
	 * @param array $dcs list of DC
	 */
	public function __construct($server, $dcs) {
		$this->server = $server;
		$this->dcs = $dcs;
	}
	
	/** @var string hostname of the LDAP server */
	private $server;
	/** @var array list of DC */
	private $dcs;
	
	public function authenticate($username, $password, &$error) {
		if( (!isset($password) === true) || $password === '') {
		  $error = "Password is empty.";
		  return null;
		}	
	
		$conn = @ldap_connect($this->server);
		if ($conn === false) {
			$error = "Unable to connect.";
			return null;
		}
		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($conn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

		$domain = "";
		foreach ($this->dcs as $dc) {
			if ($domain <> "") $domain .= ".";
			$domain .= $dc;
		}
		if (@ldap_bind($conn, $username."@".$domain, $password) === true) {
			$token = base64_encode($username."@".$password);
			return $token;
		}
		$error = ldap_error($conn);
		return null;
	}

	public function getUserList($token) {
		$token = base64_decode($token);
		$i = strpos($token, "@");
		$token_username = substr($token,0,$i);
		$token_password = substr($token,$i+1);
		$conn = @ldap_connect($this->server);
		if ($conn === false) throw new Exception('Unable to connect to the LDAP server.');
		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($conn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
		$domain = "";
		$ldap_base_dn = "";
		foreach ($this->dcs as $dc) {
			if ($domain <> "") { $domain .= "."; $ldap_base_dn .= ","; }
			$domain .= $dc;
			$ldap_base_dn .= "DC=".$dc;
		}
		if (@ldap_bind($conn, $token_username."@".$domain, $token_password) !== true) throw new Exception('Error authenticating '.$token_username.'@'.$domain.' on LDAP server');
		$search_filter = '(&(objectCategory=person)(samaccountname=*))';
		$attributes = array();
		$attributes[] = 'samaccountname';
		$attributes[] = 'givenname';
		$attributes[] = 'sn';
		$attributes[] = 'memberof';
		$result = ldap_search($conn, $ldap_base_dn, $search_filter, $attributes);
		if ($result === false) throw new Exception('Unable to search users on LDAP.');
		$entries = ldap_get_entries($conn, $result);
		$users = array();
		for ($x=0; $x<$entries['count']; $x++){
			if (!isset($entries[$x]["samaccountname"][0])) continue;
			$username = $entries[$x]["samaccountname"][0];
			$info = array("People"=>array());
			if (isset($entries[$x]["givenname"][0]))
			$info["People"]["first_name"] = $entries[$x]["givenname"][0];
			if (isset($entries[$x]["sn"][0]))
			$info["People"]["last_name"] = $entries[$x]["sn"][0];
			$groups = array();
			if (isset($entries[$x]["memberof"])) {
				for ($i = 0; $i < $entries[$x]["memberof"]['count']; $i++) {
					$group = $entries[$x]["memberof"][$i];
					if (substr($group,0,3) <> "CN=") continue;
					$group = substr($group,3);
					$j = strpos($group, ",");
					if ($j === false) continue;
					$group = substr($group, 0, $j);
					array_push($groups, $group);
				}
			}
			array_push($users, array("username"=>$username,"groups"=>$groups,"info"=>$info));
		}
		return $users;
	}
}
?>