<?php 
class service_save_department_roles extends Service {
	
	public function getRequiredRights() { return array("manage_staff"); }
	
	public function documentation() { echo "Save the default roles to give to staff of the given department"; }
	public function inputDocumentation() { echo "<code>department</code>: the department id, and <code>roles</code>: list of roles' ID"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$dep_id = $input["department"];
		$roles = $input["roles"];
		
		SQLQuery::startTransaction();
		// check the department is valid
		$row = SQLQuery::create()->select("PNDepartment")->whereValue("PNDepartment","id",$dep_id)->executeSingleRow();
		if ($row == null) {
			PNApplication::error("Invalid department ID");
			return;
		}
		//check the roles are value
		if (count($roles) > 0) {
			$rows = PNApplication::$instance->user_management->getRoles($roles);
			if (count($rows) <> count($roles)) {
				PNApplication::error("Invalid roles (".count($rows)."/".count($roles)." are valid)");
				return;
			}
		}
		// remove current associated roles, then add the new ones
		$rows = SQLQuery::create()->select("PNDepartmentDefaultRoles")->whereValue("PNDepartmentDefaultRoles", "department", $dep_id)->execute();
		if (count($rows) > 0)
			SQLQuery::create()->removeRows("PNDepartmentDefaultRoles", $rows);
		$rows = array();
		foreach ($roles as $r) array_push($rows, array("department"=>$dep_id, "user_role"=>$r));
		if (count($rows) > 0)
			SQLQuery::create()->insertMultiple("PNDepartmentDefaultRoles", $rows);
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>