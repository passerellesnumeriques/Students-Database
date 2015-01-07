<?php 
/* @var $model DataModel */

#!SELECTION_TRAVEL
$category = $model->getDataCategory("User");

$display = new datamodel\TableDataDisplay("GoogleUser", $category);
$display->addDataDisplay(new datamodel\ExistsDataDisplay("Google Connected"), "user");
$display->addJoinFrom("user");
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);
#END
?>