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
?>