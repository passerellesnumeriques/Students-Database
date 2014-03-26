<?php 
$display = new datamodel\TableDataDisplay("UserPeople", $model->getDataCategory("User"));
$display->addFollowAll();
$display->addJoinFrom("user");
$display->addJoinFrom("people", array("types"), function($columns) {
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("user", $types);
});
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);
?>