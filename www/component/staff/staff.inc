<?php
/**
 * Component managing the list of staff
 */
class staff extends Component {
	
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "AdministrationPlugin":
				require_once("StaffAdministrationPlugin.inc");
				return array(new StaffAdministrationPlugin());
			case "PeopleTypePlugin":
				require_once("StaffPeopleTypePlugin.inc");
				return array(new StaffPeopleTypePlugin());
			case "CustomCalendarPlugin":
				require_once("StaffBirthdaysCalendar.inc");
				return array(new StaffBirthdaysCalendar());
		}
		return array();
	}
	
	public function createDepartment($name, $default_user_roles, $bypass_security = false) {
		$id = SQLQuery::create()->bypassSecurity($bypass_security)->insert("PNDepartment", array("name"=>$name));
		if ($default_user_roles <> null && count($default_user_roles) > 0) {
			$roles_ids = PNApplication::$instance->user_management->getRolesIdsFromNames($default_user_roles, $bypass_security);
			$to_insert = array();
			foreach ($roles_ids as $role_id)
				array_push($to_insert, array("department"=>$id, "user_role"=>$role_id));
			if (count($to_insert) > 0)
				SQLQuery::create()->bypassSecurity($bypass_security)->insertMultiple("PNDepartmentDefaultRoles", $to_insert);
		}
	}
	
	public function requestStaffsForDates($start = null, $end = null) {
		$q = SQLQuery::create()->select("StaffPosition");
		if ($start <> null) {
			$q->where("StaffPosition.start >= '".SQLQuery::escape($start)."'");
			if ($end <> null)
				$q->where("StaffPosition.start < '".SQLQuery::escape($end)."'");
		}
		$q->groupBy("people");
		return $q;
	}
	
	public function getCurrentStaffsQuery($include_people_info = false, $include_people_picture_info = false, $include_department_name = false) {
		$q = SQLQuery::create()->select("StaffPosition")->whereNull("StaffPosition", "end");
		if ($include_people_info)
			PNApplication::$instance->people->joinPeople($q, "StaffPosition", "people", $include_people_picture_info);
		else
			$q->field("StaffPosition","people","people_id");
		if ($include_department_name) {
			$q->join("StaffPosition", "PNDepartment", array("department"=>"id"));
			$q->field("PNDepartment","name","staff_department");
		}
		$q->field("StaffPosition","position","staff_position");
		return $q;
	}
	
}
?>