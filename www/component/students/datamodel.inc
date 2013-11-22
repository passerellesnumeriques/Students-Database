<?php
use datamodel\FieldsDisplayHandler;

/* @var $model DataModel */
$model->addTable("StudentBatch")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->addDate("start_date", false, false, "2004-01-01", "end_date")
	->addDate("end_date", false, false, "start_date", null)
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
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
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	;

$model->addTable("Student")
	->addForeignKey("people", "People", true, true, false, false, true)
	->addForeignKey("batch", "StudentBatch", true, false, true, false)
	->addString("exclusion_reason", 5000, 0, true, false)
	->addDate("exclusion_date", true, false)
	->addForeignKey("specialization", "Specialization", false, false, true)
	->setPrimary("people")
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	->setDisplayHandler(null, new \datamodel\MultipleDisplayHandler("Student", "Student", array(
		new \datamodel\ChoiceDataDisplay(null, "batch", "name", "Batch"),
		new \datamodel\ChoiceDataDisplay(null, "specialization", "name", "Specialization", false),
		new \datamodel\FieldsDisplayHandler("Student", "", array(
			"exclusion_reason"=>"Exclusion reason",
			"exclusion_date"=>"Exclusion date"
		),
		array(
			"exclusion_reason"=>array("not_editable"),
			"exclusion_date"=>array("not_editable"),
		),"people")
	)))
	;
$model->addTable("AcademicPeriod")
	->addPrimaryKey("id")
	->addForeignKey("batch", "StudentBatch", true, false, true)
	->addString("name", 100, 1, false, false)
	->addDate("start_date", false, false, "2004-01-01", "end_date")
	->addDate("end_date", false, false, "start_date", null)
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Student", "Academic Period",
		array(
			"name"=>"Period Name",
			"start_date"=>"Period Start",
			"end_date"=>"Period End",
		)
	))
	;
$model->addTable("AcademicPeriodSpecialization")
	->addForeignKey("period", "AcademicPeriod", true, false, true, false, false)
	->addForeignKey("specialization", "Specialization", true, false, true, false, false)
	->setKey(array("period","specialization"))
	->addReadAccess("consult_students_list", true)
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	;
$model->addTable("AcademicClass")
	->addPrimaryKey("id")
	->addForeignKey("period", "AcademicPeriod", true, false, true)
	->addForeignKey("specialization", "Specialization", false, false, true)
	->addString("name", 100, 1, false, false)
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Student", "Class", array("name"=>"Class")))
	;
$model->addTable("StudentClass")
	->addForeignKey("people", "People", true, false, true)
	->addForeignKey("class", "AcademicClass", false, false, true)
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	->setDisplayHandler("people", new \datamodel\StopDataDisplayHandler())
	;
$model->addTable("CurriculumSubjectCategory")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, false)
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	;
$model->addTable("CurriculumSubject")
	->addPrimaryKey("id")
	->addForeignKey("category", "CurriculumSubjectCategory", true, false, true)
	->addForeignKey("period", "AcademicPeriod", true, false, true)
	->addForeignKey("specialization", "Specialization", true, false, true, true, false)
	->addString("code", 100, 1, false, false)
	->addString("name", 100, 1, false, false)
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	;
?>