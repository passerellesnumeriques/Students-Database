<?php
use datamodel\FieldsDisplayHandler;

/* @var $model DataModel */
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
			"exclusion_reason",
			"exclusion_date",
		),"people")
	)))
	;
$model->addTable("StudentClass")
	->addForeignKey("people", "People", true, false, true)
	->addForeignKey("class", "AcademicClass", false, false, true)
	->setKey(array("people","class"))
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	->setDisplayHandler("people", new \datamodel\StopDataDisplayHandler())
	->setDisplayHandler(null, new datamodel\MultipleDisplayHandler("Student", "Student", array(
		new datamodel\ReadOnlyDataDisplay(null, "Class", "class.name"),
		new datamodel\ReadOnlyDataDisplay(null, "Period", "class.period.name")
	), array("class")))
	;
?>