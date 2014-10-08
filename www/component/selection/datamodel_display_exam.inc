<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$category = new datamodel\DataDisplayCategory("Exam Center");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("ExamCenter", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("name", "Name"));
$display->addDataDisplay(new datamodel\CountLinksDataDisplay("Applicants", "Applicant", "exam_center"));
$display->addDataDisplay(new datamodel\CountLinksDataDisplay("Rooms", "ExamCenterRoom", "exam_center"));
$display->addDataDisplay(new datamodel\CountLinksDataDisplay("Sessions", "ExamSession", "exam_center"));
$display->addDataDisplay(new datamodel\CountDataValueDataDisplay("Eligible for Interview", "exam_passer", "1", "Applicant", "exam_center"));
$model->addTableDataDisplay($display);
?>