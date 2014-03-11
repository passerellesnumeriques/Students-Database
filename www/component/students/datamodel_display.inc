<?php 
/* @var $model DataModel */

$category = $model->getDataCategory("Student");

$display = new datamodel\TableDataDisplay("Student", $category);
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("batch", "name", "Batch"));
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("specialization", "name", "Specialization", false));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("exclusion_reason", "Exclusion Reason", true));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("exclusion_date", "Exclusion Date", true));
$model->addTableDataDisplay($display);
	
?>