<?php 
SQLQuery::get_db_system_without_security()->execute("INSERT INTO Role (`id`,`name`) VALUE (-1,'Administrator')");
$admin_id = PNApplication::$instance->user_management->create_user(PNApplication::$instance->local_domain, "admin", true);
PNApplication::$instance->user_management->assign_roles(array($admin_id), array(-1), true);
?>