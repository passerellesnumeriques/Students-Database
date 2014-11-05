<?php 
/* @var $model DataModel */

$model->addTable("StudentsGroupType")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, false)
	->addBoolean("specialization_dependent",false)
	->addBoolean("builtin",false)
	;

$model->addTable("StudentsGroup")
	->addPrimaryKey("id")
	->addForeignKey("type", "StudentsGroupType", true, false, true)
	->addForeignKey("parent", "StudentsGroup", true, false, true, true)
	->addForeignKey("period", "BatchPeriod", true, false, true)
	->addString("name", 100, 1, false, false)
	;

$model->addTable("StudentGroup")
	->addForeignKey("student", "People", true, false, true)
	->addForeignKey("group", "StudentsGroup", true, false, true)
	->setKey(array("student","group"))
	;
?>