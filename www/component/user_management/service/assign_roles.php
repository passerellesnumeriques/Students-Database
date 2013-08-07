<?php
class service_assign_roles extends Service {
	public function documentation() {
?>Assign the given roles to the given users.<?php		
	}
	public function get_required_rights() {
		return array("assign_roles");
	}
	public function input_documentation() {
?>
<ul>
	<li><code>users:[{domain:"",username:""}]</code>: list of users</li>
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
		
		DataBase::$conn->execute("LOCK TABLES UserRole WRITE");
		foreach ($users as $user) {
			$sql = "SELECT role_id FROM UserRole WHERE ";
			$sql .= "`domain`='".DataBase::$conn->escape_string($user->domain)."'";
			$sql .= " AND ";
			$sql .= "`username`='".DataBase::$conn->escape_string($user->username)."'";
			$res = DataBase::$conn->execute($sql);
			$list = array_merge($roles);
			if ($res)
				while (($r = DataBase::$conn->next_row($res)) <> null)
				for($i = 0; $i < count($list); $i++)
				if ($list[$i] == $r["role_id"]) {
				array_splice($list, $i, 1);
				break;
			}
			if (count($list) == 0) continue;
			foreach ($list as $role_id)
				DataBase::$conn->execute("INSERT INTO UserRole (`domain`,`username`,`role_id`) VALUE ('".DataBase::$conn->escape_string($user[0])."','".DataBase::$conn->escape_string($user[1])."',".$role_id.")");
		}
		DataBase::$conn->execute("UNLOCK TABLES");
		echo "true";
	}
};
?>