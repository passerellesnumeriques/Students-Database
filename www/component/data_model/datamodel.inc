<?php 
/* @var $model DataModel */
$model->addTable("DataLocks")
	->addPrimaryKey("id")
	->addInteger("timestamp")
	->addString("locker_domain")
	->addString("locker_username")
	->addString("table")
	->addString("row_key",1024,0,true,false)
	->addString("column")
	;

$model->addTable("ApplicationConfig")
	->addString("name", 255, 1, false, true)
	->addString("value", 4096, 0, true, false)
	->setPrimary("name")
	;
?>