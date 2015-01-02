<?php 
/* @var $model DataModel */

$model->addTable("FinanceOperation")
	->addPrimaryKey("id")
	->addForeignKey("people", "People", true, false, true)
	->addDecimal("amount", 10, 2, null, null, false, false)
	->addTimestamp("date",false,false)
	;

?>