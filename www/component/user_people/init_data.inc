<?php 
global $admin_id;
SQLQuery::getDataBaseAccessWithoutSecurity()->execute("INSERT INTO People (`first_name`,`last_name`,`types`) VALUE ('Temporary','Administrator','/user/')");
$people_id = SQLQuery::getDataBaseAccessWithoutSecurity()->getInsertID();
SQLQuery::getDataBaseAccessWithoutSecurity()->execute("INSERT INTO UserPeople (`user`,`people`) VALUE ('$admin_id','$people_id')");
?>