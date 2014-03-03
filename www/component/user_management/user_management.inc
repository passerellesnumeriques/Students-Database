<?php
require_once("Event.inc");

class user_management extends Component {

	/** @var string domain of the user logged in */
	public $domain = null;
	/** @var string username of the user logged in */
	public $username = null;
	/** @var string authentication token returned by the authentication system */
	public $auth_token = null;
	/** @var integer ID of the user logged in */
	public $user_id;

	/** @var Event raised when a user logged in */
	public $logged_in;
	/** @var Event raised when the user logout */
	public $logged_out;
	/** @var Event raised when a new user is created */
	public $local_user_created;
	
	function init() {
		$this->logged_in = new Event();
		$this->logged_out = new Event();
		$this->local_user_created = new Event();
	}
	
	public function getPluginImplementations() {
		require_once("UserManagementAdministrationPlugin.inc");
		return array(
			new UserManagementAdministartionPlugin()
		);
	}

	/**
	 * Authenticate a user, then if it succeeed, store information about the user. 
	 * @param string $domain domain of the user to authenticate
	 * @param string $username username of the user to authenticate
	 * @param string $password password of the user to authenticate
	 * @return string|null null on success or an error message
	 */
	public function login($domain, $username, $password) {
		if ($this->username <> null) return "You are already authenticated as ".$username;
		$auth = PNApplication::$instance->authentication->getAuthenticationSystem($domain);
		if ($auth == null) return "Domain not implemented";
		$error = "";
		$this->auth_token = $auth->authenticate($username, $password, $error);
		if ($this->auth_token === null) return $error;
		$user = SQLQuery::create()->bypassSecurity()->select("Users")->where("domain",$domain)->where("username",$username)->executeSingleRow();
		if ($user == null) {
			// the user does not exist yet in the database, but exists on the authentication system
			// TODO add an action for administrator ?
			return "You are not yet registered in this application. Please contact the Administrator of domain '".PNApplication::$instance->local_domain."' to create your user '".$domain."\\".$username."'.";
		} 
		$this->domain = $domain;
		$this->username = $username;
		$this->user_id = $user["id"];
		$this->logged_in->fire();
		return null;
	}

	/**
	 * The user has been authenticated using an external system (like Google, Facebook...) 
	 */
	public function external_login($user_id) {
		if ($this->username <> null) return "You are already authenticated as ".$username;
		$user = SQLQuery::create()->bypassSecurity()->select("Users")->where("id",$user_id)->executeSingleRow();
		if ($user == null)
			return "Invalid user";
		$this->domain = $user["domain"];
		$this->username = $user["username"];
		$this->user_id = $user_id;
		$this->logged_in->fire();
		return null;
	}

	/**
	 * Logout user: destroy the session.
	 */
	public function logout() {
		$this->logged_out->fire();
		setcookie(session_name(), "", time()-10000, "/dynamic/");
		setcookie(session_name(), "", time()-10000, "/");
		@session_destroy();
		$this->domain = null;
		$this->username = null;
		$this->auth_token = null;
		$this->user_id = null;
		$this->rights = array();
		$this->is_admin = array();
	}
	
	public function create_user($domain, $username, $bypass_security = false) {
		$res = SQLQuery::create()->bypassSecurity($bypass_security)->select("Users")->where("domain",$domain)->where("username",$username)->execute();
		if (count($res) > 0) {
			PNApplication::error("User ".$domain."\\".$username." already exists");
			return null;
		}
		$user_id = SQLQuery::create()->bypassSecurity($bypass_security)->insert("Users", array("domain"=>$domain,"username"=>$username));
		if ($domain == PNApplication::$instance->local_domain)
			$this->local_user_created->fire(array($user_id));
		return $user_id;
	}
	
	public function remove_user($user_id, $bypass_security = false) {
		SQLQuery::create()->bypassSecurity($bypass_security)->removeKey("Users", $user_id);
	}
	
	public function assign_user_rights($user_id, $rights) {
		$all_rights = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->get_readable_rights() as $cat) foreach ($cat->rights as $r) $all_rights[$r->name] = $r;
			foreach ($c->get_writable_rights() as $cat) foreach ($cat->rights as $r) $all_rights[$r->name] = $r;
		}
		foreach ($rights as $name=>$value)
			if (!isset($all_rights[$name])) {
				PNApplication::error("Unknown right '".$name."'");
				return false;			
			}
		
		// save in database: (1) remove all previous rights, (2) add all rights from the request
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM UserRights WHERE user='".$user_id."'");
		if (count($rights) > 0) {
			$sql = "INSERT INTO UserRights (`user`,`right`,`value`) VALUES ";
			$first = true;
			foreach ($rights as $name=>$value) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= "('".$user_id."','".SQLQuery::escape($name)."','".SQLQuery::escape($value)."')";
			}
			SQLQuery::getDataBaseAccessWithoutSecurity()->execute($sql);
		}
		return true;
	}
	
	/**
	 * Assign roles to users
	 * @param number[] $users users' ids
	 * @param number[] $roles roles' ids
	 * @return true on success
	 */
	public function assign_roles($users, $roles, $bypass_security = false) {
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		$lock_id = DataBaseLock::lockTable("UserRole", $locked_by, $bypass_security);
		if ($lock_id == null) {
			PNApplication::error("The user ".$locked_by." is currently working on users' roles. Please try again later");
			return false;
		}
		foreach ($users as $user) {
			$user_roles = SQLQuery::create()->bypassSecurity($bypass_security)->select("UserRole")->field("role")->where("user",$user)->executeSingleField();
			foreach ($roles as $role_id)
				if (!in_array($role_id, $user_roles))
				SQLQuery::create()->bypassSecurity($bypass_security)->insert("UserRole", array("user"=>$user,"role"=>$role_id));
		}
		DataBaseLock::unlock($lock_id);
		return true;
	}
	/**
	 * Unassign roles from users
	 * @param number[] $users users' ids
	 * @param number[] $roles roles' ids
	 * @return true on success
	 */
	public function unassign_roles($users, $roles) {
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		$lock_id = DataBaseLock::lockTable("UserRole", $locked_by);
		if ($lock_id == null) {
			PNApplication::error("The user ".$locked_by." is currently working on users' roles. Please try again later");
			return false;
		}
		foreach ($users as $user) {
			$user_roles = SQLQuery::create()->select("UserRole")->field("role")->where("user",$user)->executeSingleField();
			foreach ($roles as $role_id)
				if (in_array($role_id, $user_roles))
					SQLQuery::create()->removeKey("UserRole", array("user"=>$user,"role"=>$role_id)); 
		}
		DataBaseLock::unlock($lock_id);
		return true;
	}
	
	public function create_role($name, $bypass_security = false) {
		try { return SQLQuery::create()->bypassSecurity($bypass_security)->insert("Role",array("name"=>$name)); }
		catch (Exception $e) { PNApplication::error($e->getMessage()); return null; }
	}
	public function remove_role($role_id) {
		try { SQLQuery::create()->removeKey("Role", $role_id); return true; }
		catch (Exception $e) { PNApplication::error($e->getMessage()); return false; }
	}
	
	public function set_role_rights($role_id, $rights) {
		require_once("component/data_model/Model.inc");
		if (!DataModel::get()->getTable("RoleRights")->canRemoveAny()) {
			PNApplication::error("You are not allowed to set rights on roles");
			return false;
		}
		
		$r = SQLQuery::create()->select("Role")->field("name")->where("id",$role_id);
		if ($r == null || count($r) == 0) {
			PNApplication::error("Unknown role");
			return false;
		}
		
		$this->load_all_rights();
		foreach ($rights as $name=>$value) {
			if (!isset($this->all_rights[$name])) {
				PNApplication::error("Unknown right ".$name);
				return false;
			}
			$rights[$name] = $this->all_rights[$name]->parse_value($value);
		}
		
		// save in database: (1) remove all previous rights, (2) add all rights from the request
		SQLQuery::getDataBaseAccessWithoutSecurity()->execute("DELETE FROM RoleRights WHERE role='".SQLQuery::escape($role_id)."'");
		if (count($rights) > 0) {
			$sql = "INSERT INTO RoleRights (role,`right`,`value`) VALUES ";
			$first = true;
			foreach ($rights as $name=>$value) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= "('".SQLQuery::escape($role_id)."','".SQLQuery::escape($name)."','".SQLQuery::escape($value)."')";
			}
			SQLQuery::getDataBaseAccessWithoutSecurity()->execute($sql);
		}
		return true;
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
	 * Once needed, keep the list of all existing rights
	 */
	private $all_rights = null;
	
	private function load_all_rights() {
		if ($this->all_rights === null) {
			// retrieve all possible rights
			$this->all_rights = array();
			foreach (PNApplication::$instance->components as $c) {
				foreach ($c->get_readable_rights() as $cat) foreach ($cat->rights as $r) $this->all_rights[$r->name] = $r;
				foreach ($c->get_writable_rights() as $cat) foreach ($cat->rights as $r) $this->all_rights[$r->name] = $r;
			}
		}
	}
	
	/**
	 * Check if the currently logged user has the given right.
	 * @param string $right_name
	 * @param unknown $value
	 * @return boolean
	 */
	public function has_right($right_name, $value = true) {
		$this->load_all_rights();
		if (!isset($this->all_rights[$right_name])) {
			PNApplication::error("Unknown right '".$right_name."'");
			return false;
		}
		if (!isset($this->rights[PNApplication::$instance->current_domain])) {
			// get roles of the user
			$roles = SQLQuery::create()->bypassSecurity()->select("UserRole")->field("role")->where("user",$this->user_id)->executeSingleField();
			// check if the user is an administrator
			foreach ($roles as $role_id)
				if ($role_id == -1) { $this->is_admin[PNApplication::$instance->current_domain] = true; break; }
			if (!isset($this->is_admin[PNApplication::$instance->current_domain])) {
				// get rights directly attached to the user
				$user_rights = SQLQuery::create()->bypassSecurity()->select("UserRights")->field("right")->field("value")->where("user",$this->user_id)->execute();
				$rights = array();
				if ($user_rights)
				foreach ($user_rights as $r)
					$rights[$r["right"]] = $this->all_rights[$r["right"]]->parse_value($r["value"]);
				// merge with rights attached to the roles of the user
				if (count($roles) > 0)
					$role_rights = SQLQuery::create()->bypassSecurity()->select("RoleRights")->field("right")->field("value")->whereIn("role", $roles)->execute();
				else $role_rights = null;
				if ($role_rights)
				foreach ($role_rights as $r) {
					if (!isset($rights[$r["right"]]))
						$rights[$r["right"]] = $this->all_rights[$r["right"]]->parse_value($r["value"]);
					else
						$rights[$r["right"]] = $this->all_rights[$r["right"]]->get_higher_value($rights[$r["right"]], $this->all_rights[$r["right"]]->parse_value($r["value"]));
				}
				// check all rights exist, and compute implications
				$this->compute_rights_implications($rights);

				// if we are not on the local domain, remove all writable rights, to make everything read-only
				if (PNApplication::$instance->current_domain != PNApplication::$instance->local_domain) {
					foreach (PNApplication::$instance->components as $cname=>$c)
						foreach ($c->get_writable_rights() as $rname=>$rtype)
							unset($rights[$rname]);
				}
				$this->rights[PNApplication::$instance->current_domain] = $rights;
			} else {
				// in case this is an admin, only put names of rights
				$is_local = PNApplication::$instance->current_domain == PNApplication::$instance->local_domain;
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
	public function compute_rights_implications(&$rights) {
		$this->load_all_rights();
		$changed = false;
		do {
			$changed = false;
			foreach ($rights as $name=>$value) {
				$right = @$this->all_rights[$name];
				if ($right === null) {
					PNApplication::error("Unknown right '".$name."'");
					unset($rights[$name]);
					continue;
				}
				foreach ($right->implies as $if_value=>$implications)
					if ($value == $if_value)
					foreach ($implications as $rname=>$rvalue) {
						if (isset($rights[$rname])) {
							$higher = $this->all_rights[$rname]->get_higher_value($rights[$name], $rvalue);
							if ($rights[$rname] !== $higher) {
								$rights[$rname] = $higher;
								$changed = true;
							}
						} else {
							if (!isset($this->all_rights[$rname])) {
								PNApplication::error("Unknown right '".$rname."', implied from right '".$name."'");								
							} else {
								$rights[$rname] = $rvalue;
								$changed = true;
							}
						}
					}
			}
		} while ($changed);
	}
	
	public function &selectUsers($usernames = null, $domain = null) {
		$q = SQLQuery::create()->select("Users");
		if ($domain <> null) $q->database("students_".$domain);
		if ($usernames <> null) $q->whereIn("Users", "username", $usernames);
		$q->field("Users", "id", "user_id");
		$q->field("Users", "username", "username");
		return $q;
	}
	public function getSelectedUserID($row) {
		return $row["user_id"];
	}
	public function getSelectedUsername($row) {
		return $row["username"];
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