<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */
$sm = $model->getSubModel("SelectionCampaign");

$category = new datamodel\DataDisplayCategory("Selection", "/static/selection/selection_16.png");
$model->addDataCategory($category);
/*
$display = new datamodel\TableDataDisplay("SelectionCampaign", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("name", "Campaign", true, false));
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);
*/
include "datamodel_display_applicant.inc";
include "datamodel_display_is.inc";
include "datamodel_display_exam.inc";
include "datamodel_display_interview.inc";
include "datamodel_display_si.inc";
?>