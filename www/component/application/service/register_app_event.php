<?php
class service_register_app_event extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Register to an application event"; }
	public function input_documentation() {
?>
<ul>
	<li><code>type</code>: type of the event</li>
	<li><code>identifier</code>: identifier of the event</li>
</ul>
<?php
	}
	public function output_documentation() { echo "id of the event listener"; }
	public function execute(&$component, $input) {
		$id = PNApplication::$instance->register_event($input["type"], $input["identifier"]);
		echo "{id:".$id."}";
	}
	
} 
?>