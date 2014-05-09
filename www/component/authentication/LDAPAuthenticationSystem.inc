<?php
require_once("AuthenticationSystem.inc");
/**
 * Implementation of AuthenticationSystem using LDAP
 */ 
class LDAPAuthenticationSystem extends AuthenticationSystem {

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
	/** @var array keep track of tokens, with username and password to reconnect to LDAP server */
	private $tokens = array();
	
	public function authenticate($username, $password, &$error) {
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
			do {
				$token = "".rand();
			} while (isset($this->tokens[$token]));
			$this->tokens[$token] = array($username,$password);
			return $token;
		}
		$error = ldap_error($conn);
		return null;
	}

	public function getUserList($token) {
		if (!isset($this->tokens[$token])) return null;
		$conn = @ldap_connect($this->server);
		if ($conn === false) return null;
		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($conn, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
		$domain = "";
		$ldap_base_dn = "";
		foreach ($this->dcs as $dc) {
			if ($domain <> "") { $domain .= "."; $ldap_base_dn .= ","; }
			$domain .= $dc;
			$ldap_base_dn .= "DC=".$dc;
		}
		if (@ldap_bind($conn, $this->tokens[$token][0]."@".$domain, $this->tokens[$token][1]) !== true) return null;
		$search_filter = '(&(objectCategory=person)(samaccountname=*))';
		$attributes = array();
		$attributes[] = 'samaccountname';
		$attributes[] = 'givenname';
		$attributes[] = 'sn';
		$attributes[] = 'memberof';
		$result = ldap_search($conn, $ldap_base_dn, $search_filter, $attributes);
		if ($result === false) return null;
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