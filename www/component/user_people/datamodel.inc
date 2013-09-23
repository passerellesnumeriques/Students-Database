<?php
/* @var $model DataModel */
$model->addTable("UserPeople")
	->addForeignKey("user", "Users", true, false, false)
	->addForeignKey("people", "People", true, false, false)
	->addIndex("user")
	->allowReadAccess();
?>