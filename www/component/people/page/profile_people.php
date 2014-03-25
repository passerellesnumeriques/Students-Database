<?php
class page_profile_people extends Page {
	public function get_required_rights() { return array(); }
	public function execute() {
		$people_id = $_GET["people"];
		$sub_models = null;
		if (isset($_GET["sub_models"])) $sub_models = json_decode($_GET["sub_models"], true);
		require_once("component/data_model/page/data_screen.inc");
		echo existingDataScreenFromKey($this, "People", null, $people_id, $sub_models);
	}
}
?>