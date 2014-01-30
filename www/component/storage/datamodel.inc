<?php
/* @var $model DataModel */
$model->addTable("Storage")
	->addPrimaryKey("id")
	->addString("mime", 200, 0, true)
	->addInteger("expire")
	->addString("username",100,0,true)
	;
?>