<?php
class page_profile_people extends Page {
	public function getRequiredRights() { return array(); }
	public function execute() {
		$people_id = $_GET["people"];
		$sub_models = null;
		if (isset($_GET["sub_models"])) $sub_models = json_decode($_GET["sub_models"], true);
		require_once("component/data_model/page/data_screen.inc");
		$container_id = $this->generateID();
		echo "<div id='$container_id' style='width:100%;height:100%'>";
		echo existingDataScreenFromKey($this, "People", null, $people_id, $sub_models);
		echo "</div>";
		$this->requireJavascript("boxes_layout.js");
		echo "<script type='text/javascript'>boxes_layout('$container_id',5);</script>";
	}
}
?>