<?php 
/* @var DataModel $model */
$model->addTable("TemporaryData")
	->addPrimaryKey("id")
	->addString("value",1000,0,true,false)
	->addTimestamp("expiration",false,false)
	;
?>