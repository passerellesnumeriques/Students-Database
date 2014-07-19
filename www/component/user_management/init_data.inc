<?php 
SQLQuery::getDataBaseAccessWithoutSecurity()->execute("INSERT INTO Role (`id`,`name`,`builtin`) VALUE (-1,'Administrator',1)");
global $admin_id;
$admin_id = PNApplication::$instance->user_management->createInternalUser("admin", "");
PNApplication::$instance->user_management->assign_roles(array($admin_id), array(-1), true);
?>