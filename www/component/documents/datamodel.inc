<?php
/* @var $model DataModel */
$model->addTable("Document")
	->addPrimaryKey("id")
	->addString("name",250,1,false,false)
	;
$model->addTable("DocumentVersion")
	->addPrimaryKey("id")
	->addForeignKey("document", "Document", true, false, true)
	->addForeignKey("file", "Storage", true, false, false)
	->addTimestamp("timestamp",false,false)
	->addForeignKey("user", "Users", false, false, true)
	;
$model->addTable("AttachedDocument")
	->addForeignKey("document", "Document", true, true, false, false, true)
	->setPrimary("document")
	->addString("table",200,1,false)
	->addInteger("sub_model",32,1,null,true,false)
	->addInteger("key",32,1,null,false,false)
	->addString("type",100,1,true,false)
	;
?>