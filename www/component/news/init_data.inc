<?php 
SQLQuery::getDataBaseAccessWithoutSecurity()->execute("INSERT INTO News (`section`,`domain`,`username`,`type`,`html`,`timestamp`,`update_timestamp`) VALUE ('application','".PNApplication::$instance->local_domain."','admin','update','Welcome in PN Students Management Software','".(time()-10)."','".(time()-10)."')");
?>