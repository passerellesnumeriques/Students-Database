<?php 
global $admin_id;
SQLQuery::get_db_system_without_security()->execute("INSERT INTO People (`first_name`,`last_name`) VALUE ('Temporary','Administrator')");
$people_id = SQLQuery::get_db_system_without_security()->get_insert_id();
SQLQuery::get_db_system_without_security()->execute("INSERT INTO UserPeople (`user`,`people`) VALUE ('$admin_id','$people_id')");
?>