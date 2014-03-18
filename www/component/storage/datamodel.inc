<?php
/* @var $model DataModel */
$model->addTable("Storage")
	->addPrimaryKey("id")
	->addString("mime", 200, 0, true)
	->addInteger("expire")
	->addInteger("revision", 32, 1, null, false, false)
	->addString("username",100,0,true)
	->addString("domain",100,0,true)
	;
?>