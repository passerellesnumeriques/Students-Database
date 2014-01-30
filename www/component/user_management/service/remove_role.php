<?php 
class service_remove_role extends Service {
	public function documentation() {
?>Remove a role.<?php		
	}
	public function get_required_rights() {
		return array("manage_roles");
	}
	public function input_documentation() {
?>
<ul>
	<li><code>id</code>: id of the role to remove</li>
</ul>
<?php 
	}
	public function output_documentation() {
?>return true on success.<?php 
	}
	public function execute(&$component, $input) {
		if ($component->remove_role($input["id"]))
			echo "true";
		else
			echo "false";
	}
}
?>