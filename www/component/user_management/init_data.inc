<?php 
SQLQuery::getDataBaseAccessWithoutSecurity()->execute("INSERT INTO Role (`id`,`name`,`builtin`) VALUE (-1,'Administrator',1)");
$admin_id = PNApplication::$instance->user_management->createInternalUser("admin", "");
PNApplication::$instance->user_management->assign_roles(array($admin_id), array(-1), true);

SQLQuery::getDataBaseAccessWithoutSecurity()->execute("INSERT INTO People (`first_name`,`last_name`,`types`) VALUE ('Temporary','Administrator','/user/')");
$people_id = SQLQuery::getDataBaseAccessWithoutSecurity()->getInsertID();
SQLQuery::getDataBaseAccessWithoutSecurity()->execute("INSERT INTO UserPeople (`user`,`people`) VALUE ('$admin_id','$people_id')");
?>