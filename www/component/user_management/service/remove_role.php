<?php 
class service_remove_role extends Service {
	public function documentation() {
?>Remove a role.<?php		
	}
	public function getRequiredRights() {
		return array("manage_roles");
	}
	public function inputDocumentation() {
?>
<ul>
	<li><code>id</code>: id of the role to remove</li>
</ul>
<?php 
	}
	public function outputDocumentation() {
?>return true on success.<?php 
	}
	public function execute(&$component, $input) {
		if ($component->removeRole($input["id"]))
			echo "true";
		else
			echo "false";
	}
}
?>