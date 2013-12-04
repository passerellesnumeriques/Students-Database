<?php 
/* @var $model DataModel */
$model->addTable("FacebookUser")
	->addForeignKey("user", "Users", true, false, true)
	->addString("facebook_id",100,1,false,false)
	;
?>