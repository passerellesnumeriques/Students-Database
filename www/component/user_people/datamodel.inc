<?php
$model->addTable("UserPeople")
	->addString("username", 100, 1, false)
	->addForeignKey("people", "People")
	->addIndex("username")
	->addLink("Users",array("username"=>"username","domain"=>array(PNApplication::$instance->current_domain)),true)
	->allowReadAccess();
?>