<?php
/**
 * Component managing the list of staff
 */
class staff extends Component {
	
	public function getPluginImplementations() {
		require_once("StaffAdministrationPlugin.inc");
		require_once("StaffPeopleTypePlugin.inc");
		return array(
			new StaffAdministrationPlugin(),
			new StaffPeopleTypePlugin()
		);
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
	
}
?>