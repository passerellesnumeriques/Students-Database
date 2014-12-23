<?php 
/* @var DataModel $model */

$model->addTable("Note")
	->addPrimaryKey("id")
	->addString("title",100,0,false,false)
	->addString("text",10000,0,false,false)
	->addForeignKey("author", "People", true, false, true)
	->addTimestamp("timestamp",false,false)
	->addRemoveAccessFromForeignTable("People")
	;

?>