<?php 
/* @var $model DataModel */

$category = new datamodel\DataDisplayCategory("Personal Information", "/static/people/profile_16.png", "/dynamic/people/page/profile?plugin=people&people=%People.id%");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("People", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("first_name", "First Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("middle_name", "Middle Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("last_name", "Last Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("khmer_first_name", "Khmer First Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("khmer_last_name", "Khmer Last Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("sex", "Gender"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("birth", "Birth Date"));
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

?>