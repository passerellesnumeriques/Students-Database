<?php
require_once("Event.inc");
class user_management extends Component {

	/** @var string domain of the user logged in */
	public $domain = null;
	/** @var string username of the user logged in */
	public $username = null;
	/** @var string authentication token returned by the authentication system */
	public $auth_token = null;

	/** @var Event raised when a user logged in */
	public $logged_in;
	/** @var Event raised when the user logout */
	public $logged_out;

	function init() {
		$this->logged_in = new Event();
		$this->logged_out = new Event();
	}

	/**
	 * Authenticate a user, then if it succeeed, store information about the user. 
	 * @param string $domain domain of the user to authenticate
	 * @param string $username username of the user to authenticate
	 * @param string $password password of the user to authenticate
	 * @return string|null null on success or an error message
	 */
	public function login($domain, $username, $password) {
		if ($this->username <> null) {
			$this->logged_out->fire();
			session_destroy();
		}
		$auth = PNApplication::$instance->authentication->get_authentication_system($domain);
		if ($auth == null) return "Domain not implemented";
		$error = "";
		$this->auth_token = $auth->authenticate($username, $password, $error);
		if ($this->auth_token === null) return $error;
		$this->domain = $domain;
		$this->username = $username;
		$user = SQLQuery::create()->bypass_security()->select("Users")->where("domain",$domain)->where("username",$username)->execute_single_row();
		if ($user == null) // the user does not exist yet in the database, but exists on the authentication system 
			SQLQuery::create()->bypass_security()->insert("Users",array("domain"=>$domain,"username"=>$username));
		$this->logged_in->fire();
		return null;
	}

	/**
	 * Logout user: destroy the session.
	 */
	public function logout() {
		$this->logged_out->fire();
		setcookie(session_name(), "", time()-10000, "/dynamic/");
		session_destroy();
	}

	/**
	 * Once needed, keep the processed list of rights of the user
	 */
	private $rights = array();
	/**
	 * Once needed, keep the information if the user is an administrator
	 */
	private $is_admin = array();
	/**
	 * Check if the currently logged user has the given right.
	 * @param string $right_name
	 * @param unknown $value
	 * @return boolean
	 */
	public function has_right($right_name, $value = true) {
		if (!isset($this->rights[PNApplication::$instance->current_domain])) {
			// retrieve all possible rights
			$all_rights = array();
			foreach (PNApplication::$instance->components as $c) {
				foreach ($c->get_readable_rights() as $cat) foreach ($cat->rights as $r) $all_rights[$r->name] = $r;
				foreach ($c->get_writable_rights() as $cat) foreach ($cat->rights as $r) $all_rights[$r->name] = $r;
			}

			// get roles of the user
			$roles = SQLQuery::create()->bypass_security()->select("UserRole")->field("role_id")->where("domain",$this->domain)->where("username",$this->username)->execute_single_field();
			// check if the user is an administrator
			foreach ($roles as $role_id)
				if ($role_id == 0) { $this->is_admin[PNApplication::$instance->current_domain] = true; break; }
			if (!isset($this->is_admin[PNApplication::$instance->current_domain])) {
				// get rights directly attached to the user
				$user_rights = SQLQuery::create()->bypass_security()->select("UserRights")->field("right")->field("value")->where("domain",$this->domain)->where("username",$this->username)->execute();
				$rights = array();
				if ($user_rights)
				foreach ($user_rights as $r)
					$rights[$r["right"]] = $all_rights[$r["right"]]->parse_value($r["value"]);
				// merge with rights attached to the roles of the user
				if (count($roles) > 0)
					$role_rights = SQLQuery::create()->bypass_security()->select("RoleRights")->field("right")->field("value")->where_in("role_id", $roles)->execute();
				else $role_rights = null;
				if ($role_rights)
				foreach ($role_rights as $r) {
					if (!isset($rights[$r["right"]]))
						$rights[$r["right"]] = $all_rights[$r["right"]]->parse_value($r["value"]);
					else
						$rights[$r["right"]] = $all_rights[$r["right"]]->get_higher_value($rights[$r["right"]], user_management::parse_value($r["value"]));
				}
				// check all rights exist, and compute implications
				user_management::compute_rights_implications($rights, $all_rights);

				// if we are not on the local domain, remove all writable rights, to make everything read-only
				if (PNApplication::$instance->current_domain != file_get_contents("local_domain")) {
					foreach (PNApplication::$instance->components as $cname=>$c)
						foreach ($c->get_writable_rights() as $rname=>$rtype)
							unset($rights[$rname]);
				}
				$this->rights[PNApplication::$instance->current_domain] = $rights;
			} else {
				// in case this is an admin, only put names of rights
				$is_local = PNApplication::$instance->current_domain == file_get_contents("local_domain");
				$rights = array();
				foreach (PNApplication::$instance->components as $cname=>$c) {
					foreach ($c->get_readable_rights() as $rname=>$rtype)
						array_push($rights, $rname);
					if ($is_local)
						foreach ($c->get_writable_rights() as $rname=>$rtype)
							array_push($rights, $rname);
				}
				$this->rights[PNApplication::$instance->current_domain] = $rights;
			}
		}
		if (isset($this->is_admin[PNApplication::$instance->current_domain])) return in_array($right_name, $this->rights[PNApplication::$instance->current_domain]);
		if (!isset($this->rights[PNApplication::$instance->current_domain][$right_name])) return false;
		return $this->rights[PNApplication::$instance->current_domain][$right_name] === $value;
	}
	/**
	 * Add implied rights to the given list
	 * @param Right[] $rights [right_name=>right_value] list of current rights, where will be added the implied rights
	 * @param Right[] $all_rights all the existing rights already populated
	 */
	public function compute_rights_implications(&$rights, &$all_rights) {
		$changed = false;
		do {
			$changed = false;
			foreach ($rights as $name=>$value) {
				$right = @$all_rights[$name];
				if ($right === null) {
					PNApplication::error("Unknown right '".$name."'");
					unset($rights[$name]);
					continue;
				}
				foreach ($right->implies as $if_value=>$implications)
					if ($value == $if_value)
					foreach ($implications as $rname=>$rvalue) {
						if (isset($rights[$rname])) {
							$higher = $all_rights[$rname]->get_higher_value($rights[$name], $rvalue);
							if ($rights[$rname] !== $higher) {
								$rights[$rname] = $higher;
								$changed = true;
							}
						} else {
							$rights[$rname] = $rvalue;
							$changed = true;
						}
					}
			}
		} while ($changed);
	}

}

/**
 * Category of rights (for diplay purpose only)
 */
class AccessRightCategory {
	public $display_name;
	public $rights;
	public function __construct($display_name, $rights) {
		$this->display_name = $display_name;
		$this->rights = $rights;
	}
}
/**
 * Abstract class of a right
 */
abstract class Right {
	public $name;
	public $display_name;
	public $implies;
	/**
	 * 
	 * @param string $name internal name
	 * @param string $display_name name for display purpose only
	 * @param array $implies [value=>array(implied_right_name=>implied_right_value)] means that if this right has <i>value</i> it implies all the given rights
	 */
	public function __construct($name, $display_name, $implies = array()) {
		$this->name = $name;
		$this->display_name = $display_name;
		$this->implies = $implies;
	}
	/**
	 * Parse the given string and return the typed value
	 * @param string $value
	 */
	public abstract function parse_value($value);
	/**
	 * Return the less restrictive value (higher right value)
	 * @param unknown $v1
	 * @param unknown $v2
	 */
	public abstract function get_higher_value($v1, $v2);
	/**
	 * Return true if the given value is the highest possible value
	 * @param unknown $value
	 */
	public abstract function is_highest($value);
}
/**
 * Implementation of Right for a boolean value.
 */
class BooleanRight extends Right {
	public function parse_value($value) {
		return $value === true || $value === "1" || $value === "true" || $value === "on";
	}
	public function get_higher_value($v1, $v2) {
		return $v1 || $v2;
	}
	public function is_highest($value) {
		return $value;
	}
}
?>