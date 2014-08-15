<?php

/* @var $model DataModel */


/* doc : www\component\data_model\TableDefinition.inc */

$model->addTable("LabTable") 
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, false)
        ->addString("country", 100, 1, false, false)
        ->addReadAccess("see_lab_component_info",true);

        
/* Lab 2 */
        
$model->addTable("LabPeople") 
	->addPrimaryKey("id")
	->addString("first_name", 100, 1, false, false)
	->addString("last_name", 100, 1, false, false)
	->addEnum("sex", array("M","F"), false)
	->addDate("birth", true)
        ->addReadAccess("see_lab_component_info",true);
        
$model->addTable("LabFootballTeam") 
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, false)
        ->addString("color", 100, 1, false, false)
        ->addReadAccess("see_lab_component_info",true);
        
$model->addTable("LabFootballPlayer") 
	->addPrimaryKey("id")
	->addString("position", 100, 1, false, false)
        ->addForeignKey("people","LabPeople",true,false,true,false)
        ->addForeignKey("team","LabFootballTeam",false,false,true,true)
        ->addReadAccess("see_lab_component_info",true);
        
?>