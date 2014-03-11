<?php 
$model->internalGetTable("StudentBatch")
	->setDisplayHandler(null, new datamodel\MultipleDisplayHandler("Student", array(
		new datamodel\SimpleDataDisplay(null, "name", "Batch Name"),
		new datamodel\ReadOnlyDataDisplay(null, "Integration Date", "start_date"),
		new datamodel\ReadOnlyDataDisplay(null, "Graduation Date", "end_date")
	)));
$model->internalGetTable("AcademicPeriod")
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Student",
		array(
			"name"=>"Period Name",
		)
	));
$model->internalGetTable("AcademicClass")
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Student", array("name"=>"Class Name")));

?>