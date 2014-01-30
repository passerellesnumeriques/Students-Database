<?php
class service_get_temp extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Retrieve a temporary file"; }
	public function input_documentation() { echo "<code>id</code>: identifier of the temporary file to retrieve"; }
	public function output_documentation() { echo "The temporary file"; }
	public function get_output_format($input) { return "application/octet-stream"; }
	public function execute(&$component, $input) {
		$id = $_GET["id"];
		
		$res = SQLQuery::getDataBaseAccessWithoutSecurity()->execute("SELECT * FROM `Storage` WHERE `id`='".SQLQuery::escape($id)."'");
		if (!$res) return;
		$res = SQLQuery::getDataBaseAccessWithoutSecurity()->nextRow($res);
		if ($res == null) return;
		if ($res["expire"] == null || $res["username"] <> PNApplication::$instance->user_management->username) return;
		$path = $component->get_data_path($id);
		if (!file_exists($path)) return;
		$data = file_get_contents($path);
		if ($data === false) return;
		echo $data;
	}
} 
?>