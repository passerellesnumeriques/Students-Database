<?php 
/* @var $model DataModel */

$model->addTable("FinanceOperation")
	->addPrimaryKey("id")
	->addForeignKey("people", "People", true, false, true)
	->addDecimal("amount", 10, 2, null, null, false, false)
	->addTimestamp("date",false,false)
	->addReadAccess("consult_student_finance", true)
	->addWriteAccess("edit_student_finance", true)
	->addInsertAccess("edit_student_finance", true)
	->addRemoveAccess("edit_student_finance", true)
	->addRemoveAccessFromForeignTable("People")
	;

$model->addTable("ScheduledPayment")
	->addPrimaryKey("id")
	->addForeignKey("people", "People", true, false, true)
	->addEnum("frequency",array("Daily","Weekly","Monthly","Yearly","Custom"),false,false)
	->addString("description",250,0,false,false)
	;

$model->addTable("ScheduledPaymentDate")
	->addForeignKey("due_operation", "FinanceOperation", true, true, false, false, true)
	->setPrimary("due_operation")
	->addForeignKey("payment","ScheduledPayment", true, false, true, false, false)
	;

$model->addTable("ScheduledPaymentDateOperation")
	->addForeignKey("schedule","ScheduledPaymentDate",true,false,true,false,false)
	->addForeignKey("operation", "FinanceOperation", true, false, true,false,false)
	->setKey(array("schedule","operation"))
	;

$model->addTable("FinanceRegularPayment")
	->addPrimaryKey("id")
	->addString("name",30,1,false,true)
	->addEnum("frequency",array("Daily","Weekly","Monthly","Yearly"),false,false)
	->addReadAccess("consult_student_finance", true)
	->addWriteAccess("manage_finance", true)
	->addInsertAccess("manage_finance", true)
	->addRemoveAccess("manage_finance", true)
	;

?>