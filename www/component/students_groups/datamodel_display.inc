<?php
/* @var $model DataModel */

$category = $model->getDataCategory("Student");
$display = new datamodel\TableDataDisplay("StudentGroup", $category);
$display->addDataDisplay(new datamodel\ReadOnlyDataDisplay("Period", "group.period.name", "center", "group.period"));
$display->addDataDisplay(new datamodel\ReadOnlyDataDisplay("Group Type", "group.type.name", "center", "group.type"));
$display->addDataDisplay(new datamodel\ReadOnlyDataDisplay("Group", "group.name", "center", "group"));
$display->addFollow("people");
$model->addTableDataDisplay($display);
		
?>