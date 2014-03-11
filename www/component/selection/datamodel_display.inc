<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$category = new datamodel\DataDisplayCategory("Selection");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("Application", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("application_id", "Applicant ID", true));
$model->addTableDataDisplay($display);
?>