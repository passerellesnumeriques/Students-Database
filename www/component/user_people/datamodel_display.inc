<?php 
$display = new datamodel\TableDataDisplay("UserPeople", $model->getDataCategory("User"));
$display->addFollowAll();
$display->addJoinFromAll();
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);
?>