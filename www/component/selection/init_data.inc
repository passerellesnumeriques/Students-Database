<?php
// User roles
$selection_staff_role_id = PNApplication::$instance->user_management->createRole("Selection Staff", true, true);
$staff_role_id = PNApplication::$instance->user_management->getRolesIdsFromNames(array("Staff"), true);
$staff_role_id = $staff_role_id[0];

// assign rights to roles
PNApplication::$instance->user_management->addRoleRights($staff_role_id, array("can_access_selection_data"=>true), true);
PNApplication::$instance->user_management->addRoleRights($selection_staff_role_id, array(
	"see_information_session"=>true,
	"see_exam_subject"=>true,
	"see_exam_center"=>true,
	"see_exam_rules"=>true,
	"see_interview_center"=>true,
	"see_interview_criteria"=>true,
	"see_applicant_info"=>true,
), true);

// Selection Department for staff
PNApplication::$instance->staff->createDepartment("Selection", array("Selection Staff"), true);
// Organization types 
SQLQuery::create()->bypassSecurity()
#DEV
	->noWarning()
#END
	->insert("OrganizationType", array("creator"=>"Selection", "name"=>"High School", "builtin"=>true));
SQLQuery::create()->bypassSecurity()
#DEV
	->noWarning()
#END
	->insert("OrganizationType", array("creator"=>"Selection", "name"=>"NGO", "builtin"=>true));
?>