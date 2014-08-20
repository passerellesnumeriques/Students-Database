<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

/* Lab 2 */

/* We first need to create a DataDisplayCategory */
$category = new datamodel\DataDisplayCategory("People Information");
/* Adding Category to the Data Model */
$model->addDataCategory($category);

/* Then we create a TableDataDisplay for table LabPeople */
$display = new datamodel\TableDataDisplay("LabPeople", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("first_name", "First Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("last_name", "Last Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("sex", "Gender"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("birth", "Birth Date"));
$display->addDataDisplay(new datamodel\CalculationDataDisplay("Age","TIMESTAMPDIFF(YEAR,`birth`,CURDATE())"));
$display->addJoinToPrimary(); // Allow join from other tables via LabPeople's primary key
$model->addTableDataDisplay($display);


/* Let's create a new DataDisplayCategory to display informations about Football */
$category = new datamodel\DataDisplayCategory("Football");
$model->addDataCategory($category);

/* LabFootballTeam Display */
$display = new datamodel\TableDataDisplay("LabFootballTeam", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("name", "Team Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("color", "Team Color"));
$display->addJoinToPrimary();// Allow join from other tables via LabFootballTeam's primary key
$model->addTableDataDisplay($display);

/* LabFootballPlayer Display */
$display = new datamodel\TableDataDisplay("LabFootballPlayer", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("position", "Field Position"));
$display->addFollow("team");// Allow join towards LabFootballTeam table
$display->addJoinFrom("people"); // Allow join from labPeople table
/* note : if you want to allow joining/following for any tables use addFollowAll/addJoinFromAll */

$model->addTableDataDisplay($display);


?>