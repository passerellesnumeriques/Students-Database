<?php
use datamodel\FieldsDisplayHandler;

/* @var $model DataModel */
$model->addTable("StudentBatch")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->addDate("start_date", true, false)
	->addDate("end_date", true, false)
	->restrictReadAccess("consult_students_list", true)
	->restrictWriteAccess("manage_batches", true)
	->restrictAddAccess("manage_batches", true)
	->restrictRemoveAccess("manage_batches", true)
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Student", "Batch",
		array(
			"name"=>"Batch Name",
			"start_date"=>"Integration Date",
			"end_date"=>"Graduation Date",
		)
	))
	;
$model->addTable("Specialization")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->restrictReadAccess("consult_students_list", true)
	->restrictWriteAccess("manage_batches", true)
	->restrictAddAccess("manage_batches", true)
	->restrictRemoveAccess("manage_batches", true)
	;

$model->addTable("Student")
	->addForeignKey("people", "People", true, true, false)
	->addForeignKey("batch", "StudentBatch", true, false, true, false)
	->addString("exclusion_reason", 5000, 0, true, false)
	->addDate("exclusion_date", true, false)
	->addForeignKey("specialization", "Specialization", false, false, true)
	->restrictReadAccess("consult_students_list", true)
	->restrictWriteAccess("manage_batches", true)
	->restrictAddAccess("manage_batches", true)
	->restrictRemoveAccess("manage_batches", true)
	->setDisplayHandler(null, new \datamodel\MultipleDisplayHandler("Student", "Student", array(
		new \datamodel\ChoiceDataDisplay(null, "batch", "name", "Batch", "people"),
		new \datamodel\FieldsDisplayHandler("Student", "", array(
			"exclusion_reason"=>"Exclusion reason",
			"exclusion_date"=>"Exclusion date"
		),null,"people")
	)))
	;
$model->addTable("AcademicPeriod")
	->addPrimaryKey("id")
	->addForeignKey("batch", "StudentBatch", true, false, true)
	->addString("name", 100, 1, false, false)
	->addDate("start_date", true, false)
	->addDate("end_date", true, false)
	->restrictReadAccess("consult_students_list", true)
	->restrictWriteAccess("manage_batches", true)
	->restrictAddAccess("manage_batches", true)
	->restrictRemoveAccess("manage_batches", true)
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Student", "Academic Period",
		array(
			"name"=>"Period Name",
			"start_date"=>"Period Start",
			"end_date"=>"Period End",
		)
	))
	;
$model->addTable("AcademicClass")
	->addPrimaryKey("id")
	->addForeignKey("period", "AcademicPeriod", true, false, true)
	->addForeignKey("specialization", "Specialization", false, false, true)
	->addString("name", 100, 1, false, false)
	->restrictReadAccess("consult_students_list", true)
	->restrictWriteAccess("manage_batches", true)
	->restrictAddAccess("manage_batches", true)
	->restrictRemoveAccess("manage_batches", true)
	;
$model->addTable("StudentClass")
	->addForeignKey("people", "People", true, false, true)
	->addForeignKey("class", "AcademicClass", false, false, true)
	->restrictReadAccess("consult_students_list", true)
	->restrictWriteAccess("manage_batches", true)
	->restrictAddAccess("manage_batches", true)
	->restrictRemoveAccess("manage_batches", true)
	->setDisplayHandler("people", new \datamodel\StopDataDisplayHandler())
	;
?>