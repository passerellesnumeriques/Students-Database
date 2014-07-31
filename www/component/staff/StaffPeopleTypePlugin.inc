<?php 
require_once("component/people/PeopleTypePlugin.inc");
class StaffPeopleTypePlugin implements PeopleTypePlugin {
	
	public function getSecurityPriority() { return 5; }
	
	public function getId() { return "staff"; }
	public function getName() { return "Staff"; }
	public function isStandalone() { return true; }
	
	public function canRead() { return PNApplication::$instance->user_management->has_right("consult_staff_list"); }
	public function canWrite() { return PNApplication::$instance->user_management->has_right("manage_staff"); }
	public function canRemove() { return PNApplication::$instance->user_management->has_right("manage_staff"); }
	
	public function getPeopleRowDescription($people) {
		return "PN Staff";
	}
	
	public function getBuiltinDefaultUserRoles($people) {
		$list = array("Staff");
		$last_department = SQLQuery::create()
			->select("Staff")
			->whereValue("Staff", "people", $people["id"])
			->join("Staff","StaffPosition",array("people"=>"people"))
			->orderBy("StaffPosition", "start", false)
			->join("StaffPosition", "PNDepartment", array("department"=>"id"))
			->limit(0, 1)
			->field("PNDepartment","id")
			->executeSingleValue();
		if ($last_department <> null) {
			$roles = SQLQuery::create()
				->noWarning()
				->select("PNDepartmentDefaultRoles")
				->whereValue("PNDepartmentDefaultRoles", "department", $last_department)
				->join("PNDepartmentDefaultRoles", "Role", array("user_role"=>"id"))
				->field("Role", "name")
				->executeSingleField();
			$list = array_merge($list, $roles);
		}
		return $list;
	}
}
?>