<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$category = new datamodel\DataDisplayCategory("Interview Center");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("InterviewCenter", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("name", "Name"));
$display->addDataDisplay(new datamodel\CountLinksDataDisplay("Applicants", "Applicant", "interview_center"));
$display->addDataDisplay(new datamodel\CountLinksDataDisplay("Sessions", "InterviewSession", "interview_center"));
$model->addTableDataDisplay($display);
?>