<?php 
/* @var DataModel $model */

$model->addTable("Note")
	->addPrimaryKey("id")
	->addString("text",10000,0,false,false)
	->addForeignKey("author", "People", true, false, true)
	->addTimestamp("timestamp",false,false)
	;

?>