<?php
/* @var $model DataModel */
$model->addTable("Storage")
	->addPrimaryKey("id")
	->addString("mime", 200, 0, true)
	->addInteger("expire")
	->addInteger("revision", 32, 1, null, false, false)
	->addString("type", 100, 1, false, false)
	;
?>