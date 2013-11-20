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
		if (!PNApplication::$instance->user_management->has_right("manage_staff", true)) return false;
		$create_data["staff_positions"] = array();
		try {
			SQLQuery::create()->bypass_security()->insert("Staff", array("people"=>$people_id));
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
	
	public function getPeopleImportDataDisplay() { return array(); }
	public function importPeopleData($input, $people_id) { return true; }
	
	public function filterPeopleReadAccess(&$q, $people_table_alias) {
		if (!PNApplication::$instance->user_management->has_right("consult_staff_list")) return null;
		$staff_alias = $q->get_table_alias("Staff");
		if ($staff_alias == null) {
			$staff_alias = $q->table_id();
			$q->join($people_table_alias, "Staff", array("id"=>"people"), $staff_alias);
		}
		return "`".$staff_alias."`.`people` IS NOT NULL";
		
	}
	public function prepareSelectPeopleWriteAccess(&$q, $people_table_alias) {
		$staff_alias = $q->get_table_alias("Staff");
		if ($staff_alias == null) {
			$staff_alias = $q->table_id();
			$q->join($people_table_alias, "Staff", array("id"=>"people"), $staff_alias);
		}
		$alias = $q->get_field_alias($staff_alias, "people");
		if ($alias == null) {
			$alias = $q->field_id();
			$q->field($staff_alias, "people", $alias);
		}
	}
	public function filterPeopleWriteAccess($people_rows) {
		$staff_alias = $q->get_table_alias("Staff");
		$alias = $q->get_field_alias($staff_alias, "people");
		$final = array();
		if (PNApplication::$instance->user_management->has_right("manage_staff"))
			foreach ($people_rows as $row)
				if ($row[$alias] <> null) array_push($final, $row); 
		return $final; 
	}
	public function prepareSelectPeopleRemoveAccess(&$q, $people_table_alias, &$locks) {
		$staff_alias = $q->get_table_alias("Staff");
		if ($staff_alias == null) {
			$staff_alias = $q->table_id();
			$q->join($people_table_alias, "Staff", array("id"=>"people"), $staff_alias);
		}
		$alias = $q->get_field_alias($staff_alias, "people");
		if ($alias == null) {
			$alias = $q->field_id();
			$q->field($staff_alias, "people", $alias);
		}
	}
	public function filterPeopleRemoveAccess($people_rows) { 
		$staff_alias = $q->get_table_alias("Staff");
		$alias = $q->get_field_alias($staff_alias, "people");
		$final = array();
		if (PNApplication::$instance->user_management->has_right("manage_staff"))
			foreach ($people_rows as $row)
				if ($row[$alias] <> null) array_push($final, $row); 
		return $final; 
	}
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage("PN Staff", "/dynamic/staff/page/staff", "/static/staff/staff_16.png"),
		);
	}
	
}
?>