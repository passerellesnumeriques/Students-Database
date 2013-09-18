<?php
class service_unassign_roles extends Service {
	public function documentation() {
?>Remove the given roles to the given users.<?php		
	}
	public function get_required_rights() {
		return array("assign_roles");
	}
	public function input_documentation() {
?>
<ul>
	<li><code>users:[user_id]</code>: list of users</li>
	<li><code>roles:[id]</code>: list of roles' id</li>
</ul>
<?php 
	}
	public function output_documentation() {
?>return true on success.<?php 
	}
	public function execute(&$component) {
		$users = json_decode($_POST["users"]);
		$roles = json_decode($_POST["roles"]);
		
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		$lock_id = DataBaseLock::lock_table("UserRole", $locked_by);
		if ($lock_id == null) {
			PNApplication::error("The user ".$locked_by." is currently working on users' roles. Please try again later");
			echo "false";
			return;
		}
		foreach ($users as $user) {
			$user_roles = SQLQuery::create()->select("UserRole")->field("role")->where("user",$user)->execute_single_field();
			foreach ($roles as $role_id)
				if (in_array($role_id, $user_roles))
					SQLQuery::create()->remove("UserRole", array("user"=>$user,"role"=>$role_id)); 
		}
		DataBaseLock::unlock($lock_id);
		echo "true";
	}
};
?>