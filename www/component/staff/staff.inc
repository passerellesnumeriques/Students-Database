<?php
require_once("component/people/ProfileGeneralInfoPlugin.inc"); 
class staff extends Component implements ProfileGeneralInfoPlugin {
	
	public function get_people_profile_general_info_sections($people_id) {
		try {
			$staff = SQLQuery::create()->select("StaffPosition")->where("people",$people_id)->execute_single_row();
			if ($staff == null) return array();
			return array(array("staff_info","<img src='/static/staff/staff_16.png' style='vertical-align:bottom;padding-right:3px'/>PN Staff",5));
		} catch (Exception $ex) { return array(); }
	}
	
	public function generate_people_profile_general_info_section(&$page, $people_id, $section_id) {
		if ($section_id == "staff_info") {
			require_once("page/staff_info.inc");
			$id = $page->generate_id();
			echo "<div id='$id'></div>";
			$page->onload("new ".staff_info($page, $people_id, $id)."();");
		}
	}
	
	public function get_create_people_pages($people_type) {
		if ($people_type <> "staff") return array();
		return array(
			array("PN Staff Job Description", "component/staff/page/create_people__staff.inc",5)
		);
	}
	public function create_people($people_id, $people_type, $input, &$create_data) {
		if ($people_type <> "staff") return true;
		$create_data["staff_positions"] = array();
		try {
			foreach ($input["staff_positions"] as $p) {
				$id = SQLQuery::create()->insert("StaffPosition", array(
					"people"=>$people_id,
					"position"=>$p["position"],
					"department"=>$p["department"],
					"start"=>$p["start"],
					"end"=>$p["end"]
				));
				array_push($create_data["staff_positions"], $id);
			}
			return true;
		} catch (Exception $ex) {
			// rollback
			PNApplication::error($ex);
			return false;
		}
	}
	public function rollback_create_people($people_id, $people_type, $input, &$create_data) {
		if ($people_type <> "staff") return;
		SQLQuery::create()->remove_keys("StaffPosition", $create_data["staff_positions"]);
	}
	
}
?>