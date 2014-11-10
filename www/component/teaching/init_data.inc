<?php 
$role_id = PNApplication::$instance->user_management->create_role("Teacher", true, true);
PNApplication::$instance->user_management->add_role_rights($role_id, array("consult_curriculum"=>true,"consult_students_list"=>true), true);
?>