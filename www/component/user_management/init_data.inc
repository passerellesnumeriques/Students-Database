<?php
// administrator role 
SQLQuery::getDataBaseAccessWithoutSecurity()->execute("INSERT INTO Role (`id`,`name`,`builtin`) VALUE (-1,'Administrator',1)");

// admin people
$people_id = PNApplication::$instance->people->createPeople(array("first_name"=>"Temporary","last_name"=>"Administrator","sex"=>"M"), array("user"), true);

// admin user
$admin_id = PNApplication::$instance->user_management->createInternalUser("admin", "", $people_id);
PNApplication::$instance->user_management->assignRoles(array($admin_id), array(-1), true);
?>