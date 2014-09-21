<?php 
/* @var $model DataModel */
$model->addTable("HelpSystemHidden")
	->addForeignKey("user", "Users", true, false, true)
	->addString("help_id",100,1,false,false)
	->setKey(array("user","help_id"))
	;
?>