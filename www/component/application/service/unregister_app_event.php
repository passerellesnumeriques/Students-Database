<?php
class service_unregister_app_event extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Unregister an application event"; }
	public function input_documentation() {
?>
<ul>
	<li><code>id</code>: id of the listener</li>
</ul>
or
<ul>
	<li><code>ids</code>: list of listeners' id</li>
</ul>
<?php
	}
	public function output_documentation() { echo "true"; }
	public function execute(&$component, $input) {
		if (isset($input["ids"]))
			foreach ($input["ids"] as $id)
				PNApplication::$instance->unregister_event($id);
		else
			PNApplication::$instance->unregister_event($input["id"]);
		echo "true";
	}
	
} 
?>