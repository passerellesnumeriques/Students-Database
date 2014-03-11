<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$display = new datamodel\TableDataDisplay("InformationSession", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("name", "Name"));
$display->addDataDisplay(new CalendarEventDateDataDisplay("Date", "date"));
$model->addTableDataDisplay($display);
?>