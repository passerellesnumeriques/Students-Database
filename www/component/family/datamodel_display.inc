<?php 
/* @var $model DataModel */

$category = new datamodel\DataDisplayCategory("Family Background", "/static/family/family_16.png");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("FamilyMember", $category);
$display->addJoinFrom("people",array("types"),function($columns){
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("student", $types) || in_array("applicant", $types);
});
$display->addFollow("family");
$model->addTableDataDisplay($display);

$display = new datamodel\TableDataDisplay("Family", $category);
$display->addJoinToPrimary();
//$display->addDataDisplay(new datamodel\SimpleDataDisplay("nb_children", "Children"));
$model->addTableDataDisplay($display);

?>