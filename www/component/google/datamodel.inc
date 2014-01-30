<?php 
/* @var $model DataModel */
$model->addTable("GoogleUser")
	->addForeignKey("user", "Users", true, false, true)
	->addString("google_id",100,1,false,false)
	;
?>