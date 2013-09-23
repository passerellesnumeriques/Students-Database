<?php
class page_profile_people extends Page {
	public function get_required_rights() {
		return array(
			"consult_students_list",
			function() { return $_GET["people"] == PNApplication::$instance->user_people->user_people_id; }
		);
	}
	public function execute() {
	}
}
?>