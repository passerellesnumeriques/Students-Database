<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$category = new datamodel\DataDisplayCategory("Exam Center");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("ExamCenter", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("name", "Name"));
$display->addDataDisplay(new CalendarEventDateDataDisplay("Date", "date"));
$model->addTableDataDisplay($display);
?>