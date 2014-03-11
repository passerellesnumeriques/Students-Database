<?php 
/* @var $model DataModel */

$model->internalGetTable("Student")
	->setDisplayHandler(null, new \datamodel\MultipleDisplayHandler("Student", array(
		new \datamodel\ChoiceDataDisplay(null, "batch", "name", "Batch"),
		new \datamodel\ChoiceDataDisplay(null, "specialization", "name", "Specialization", false),
		new \datamodel\FieldsDisplayHandler("Student", array(
			"exclusion_reason"=>"Exclusion reason",
			"exclusion_date"=>"Exclusion date"
		),
		array(
			"exclusion_reason",
			"exclusion_date",
		),"people")
	)));
	
$model->internalGetTable("StudentClass")
	->setDisplayHandler("people", new \datamodel\StopDataDisplayHandler())
	->setDisplayHandler(null, new datamodel\MultipleDisplayHandler("Student", array(
		new datamodel\ReadOnlyDataDisplay(null, "Class", "class.name"),
		new datamodel\ReadOnlyDataDisplay(null, "Period", "class.period.name")
	), array("class")))
	;

?>