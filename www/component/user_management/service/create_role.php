<?php 
class service_create_role extends Service {
	public function documentation() {
?>Create a new role.<?php		
	}
	public function get_required_rights() {
		return array("manage_roles");
	}
	public function input_documentation() {
?>
<ul>
	<li><code>name</code>: name of the new role</li>
</ul>
<?php 
	}
	public function output_documentation() {
?>return the id of the new role on success.<?php 
	}
	public function execute(&$component, $input) {
		$name = $input["name"];
		$id = null;
		try { $id = SQLQuery::create()->insert("Role",array("name"=>$name)); }
		catch (Exception $e) {
			PNApplication::error($e->getMessage());
		}
		echo $id <> null ? "{id:".$id."}" : "false";
	}
}
?>