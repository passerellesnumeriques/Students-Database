<?php 
class service_rename_role extends Service {
	public function documentation() {
?>Rename a role.<?php		
	}
	public function get_required_rights() {
		return array("manage_roles");
	}
	public function input_documentation() {
?>
<ul>
	<li><code>id</code>: id of the role to rename</li>
	<li><code>name</code>: new name</li>
</ul>
<?php 
	}
	public function output_documentation() {
?>return true on success.<?php 
	}
	public function execute(&$component, $input) {
		$id = $input["id"];
		$name = $input["name"];
		try { SQLQuery::create()->update_by_key("Role", $id, array("name"=>$name)); }
		catch (Exception $e) {
			PNApplication::error($e->getMessage());
		}
		echo PNApplication::has_errors() ? "false" : "true";
	}
}
?>