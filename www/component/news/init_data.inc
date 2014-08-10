<?php 
SQLQuery::getDataBaseAccessWithoutSecurity()->execute("INSERT INTO News (`section`,`domain`,`username`,`html`,`timestamp`,`update_timestamp`) VALUE ('application','".PNApplication::$instance->local_domain."','admin','Welcome in PN Students Management Software','".(time()-10)."','".(time()-10)."')");
?>