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
	public function execute(&$component) {
		$id = $_POST["id"];
		try { SQLQuery::create()->remove_key("Role", $id); }
		catch (Exception $e) {
			PNApplication::error($e->getMessage());
		}
		echo PNApplication::has_errors() ? "false" : "true";
	}
}
?>