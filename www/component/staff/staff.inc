<?php
require_once("component/people/PeoplePlugin.inc"); 
require_once("component/administration/AdministrationPlugin.inc");
class staff extends Component implements PeoplePlugin, AdministrationPlugin {
	
	public function preparePeopleProfilePagesRequest(&$q, $people_id) {}
	public function getPeopleProfilePages($people_id, $people, $q) {}
	
	public function getPeopleProfileGeneralInfoSections($people_id, $people, $q) {
		try {
			$staff = SQLQuery::create()->select("StaffPosition")->where("people",$people_id)->execute_single_row();
			if ($staff == null) return array();
			return array(
				array(
					"/static/staff/staff_16.png",
					"PN Staff",
					"component/staff/page/staff_info.inc.inc",
					5
				)
			);
		} catch (Exception $ex) { return array(); }
	}
	
	public function getCreatePeopleSupportedTypes() { return array("staff"); }
	public function isCreatePeopleAllowed($people_type) {
		if ($people_type <> "staff") return false;
		return PNApplication::$instance->user_management->has_right("manage_staff", true);
	}
	public function getCreatePeoplePages($people_types) {
		if (!in_array("staff", $people_types)) return array();
		return array(
			array("/static/staff/staff_16.png", "PN Staff Job Description", "component/staff/page/create_people__staff.inc",5)
		);
	}
	public function createPeople($people_id, $people_types, $input, &$create_data) {
		if (!in_array("staff", $people_types)) return true;
		$create_data["staff_positions"] = array();
		try {
			foreach ($input["staff_positions"] as $p) {
				$id = SQLQuery::create()->bypass_security()->insert("StaffPosition", array(
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
	public function rollbackCreatePeople($people_id, $people_types, $input, &$create_data) {
		if (!in_array("staff", $people_types)) return;
		SQLQuery::create()->bypass_security()->remove_keys("StaffPosition", $create_data["staff_positions"]);
	}
	
	public function filterPeopleReadAccess(&$q, $people_table_alias) {}
	public function prepareSelectPeopleWriteAccess(&$q, $people_table_alias) {}
	public function filterPeopleWriteAccess($people_rows) { return $people_rows; }
	public function prepareSelectPeopleRemoveAccess(&$q, $people_table_alias, &$locks) {}
	public function filterPeopleRemoveAccess($people_rows) { return $people_rows; }
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage("PN Staff", "/dynamic/staff/page/staff", "/static/staff/staff_16.png"),
		);
	}
	
}
?>