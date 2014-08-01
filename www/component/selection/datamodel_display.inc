<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */
$sm = $model->getSubModel("SelectionCampaign");

$category = new datamodel\DataDisplayCategory("Selection", "/static/selection/selection_16.png");
$model->addDataCategory($category);

include "datamodel_display_applicant.inc";
include "datamodel_display_is.inc";
include "datamodel_display_exam.inc";
include "datamodel_display_interview.inc";
?>