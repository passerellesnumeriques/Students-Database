<?php 
/* @var $model DataModel */
$model->addTable("DataLocks")
	->addPrimaryKey("id")
	->addInteger("timestamp")
	->addString("locker_domain")
	->addString("locker_username")
	->addString("table")
	->addInteger("row_key")
	->addString("column")
	;
?>