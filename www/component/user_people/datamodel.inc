<?php
/* @var $model DataModel */
$model->addTable("UserPeople")
	->addForeignKey("user", "Users", true, false, false, false, true)
	->addForeignKey("people", "People", true, false, false, false, true)
	->setPrimary("user")
	->addIndex("people")
	->allowReadAccess();
?>