<?php

/* @var $model DataModel */


$model->addTable("LabTable") /* cf www\component\data_model\TableDefinition.inc */
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, false)
        ->addString("country", 100, 1, false, false)
        ->addReadAccess("see_lab_component_info",true);
?>