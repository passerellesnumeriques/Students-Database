<?php 
/* @var $model DataModel */
$model->addTable("GoogleUser")
	->addForeignKey("user", "Users", true, false, false, false, true)
	->addString("google_id",100,1,true,false)
	->addString("google_login", 255, 10,true,false)
	->setPrimary("user")
	;
?>