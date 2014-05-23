<?php 
class service_create_role extends Service {
	public function documentation() {
?>Create a new role.<?php		
	}
	public function getRequiredRights() {
		return array("manage_roles");
	}
	public function inputDocumentation() {
?>
<ul>
	<li><code>name</code>: name of the new role</li>
</ul>
<?php 
	}
	public function outputDocumentation() {
?>return the id of the new role on success.<?php 
	}
	public function execute(&$component, $input) {
		$id = $component->create_role($input["name"]);
		echo $id <> null ? "{id:".$id."}" : "false";
	}
}
?>