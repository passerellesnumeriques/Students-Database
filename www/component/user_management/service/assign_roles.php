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
	<li><code>users:[user_id]</code>: list of users</li>
	<li><code>roles:[id]</code>: list of roles' id</li>
</ul>
<?php 
	}
	public function output_documentation() {
?>return true on success.<?php 
	}
	/**
	 * @param user_management $component
	 */
	public function execute(&$component, $input) {
		$users = $input["users"];
		$roles = $input["roles"];

		if (!$component->assign_roles($users, $roles))
			echo "false";
		else
			echo "true";
	}
};
?>