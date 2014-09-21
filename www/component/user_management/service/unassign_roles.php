<?php
class service_unassign_roles extends Service {
	public function documentation() {
?>Remove the given roles to the given users.<?php		
	}
	public function getRequiredRights() {
		return array("assign_role");
	}
	public function inputDocumentation() {
?>
<ul>
	<li><code>users:[user_id]</code>: list of users</li>
	<li><code>roles:[id]</code>: list of roles' id</li>
</ul>
<?php 
	}
	public function outputDocumentation() {
?>return true on success.<?php 
	}
	public function execute(&$component, $input) {
		$users = $input["users"];
		$roles = $input["roles"];
		
		if (!$component->unassign_roles($users, $roles))
			echo "false";
		else
			echo "true";
	}
};
?>