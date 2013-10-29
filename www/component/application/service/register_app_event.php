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
or <code>events</code>: [{type:x,identifier:y}] a list of events to register
<?php
	}
	public function output_documentation() { echo "<code>id</code>: the id of the event listener, or <code>[ids...]</code> the list of ids"; }
	public function execute(&$component, $input) {
		if (isset($input["events"])) {
			$ids = array();
			foreach ($input["events"] as $ev) {
				$id = PNApplication::$instance->register_event($ev["type"], $ev["identifier"]);
				array_push($ids, $id);
			}
			echo json_encode($ids);
		} else {
			$id = PNApplication::$instance->register_event($input["type"], $input["identifier"]);
			echo "{id:".$id."}";
		}
	}
	
} 
?>