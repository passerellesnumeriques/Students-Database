<?php 
class service_save_role_rights extends Service {
	public function documentation() {
?>Save the list of rights associated with the given role.<?php		
	}
	public function getRequiredRights() {
		return array("manage_roles");
	}
	public function inputDocumentation() {
?>
<ul>
	<li><code>role_id</code>: id of the role</li>
	<li><code>lock</code>: lock id</li>
	<li>list of rights to save: <code><i>right_name=right_value</i></code>
</ul>
<?php 
	}
	public function outputDocumentation() {
?>return true on success.<?php 
	}
	public function execute(&$component, $input) {
		$role_id = $input["role_id"];
		require_once("component/data_model/DataBaseLock.inc");
		if (DataBaseLock::checkLock($input["lock"], "RoleRights", null, null) <> null) {
			PNApplication::error("You do not have the data locked, meaning you cannot modify them. This may be due to a long inactivity. Please refresh the page and try again");
			return;
		}
		
		$rights = array();
		foreach ($input as $name=>$value) {
			if ($name == "role_id" || $name == "lock") continue;
			$rights[$name] = $value;
		}

		if ($component->setRoleRights($role_id, $rights))
			echo "true";
		else
			echo "false";
	}
}
?>