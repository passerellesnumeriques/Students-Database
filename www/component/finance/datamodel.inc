<?php 
/* @var $model DataModel */

$model->addTable("FinanceOperation")
	->addPrimaryKey("id")
	->addForeignKey("people", "People", true, false, true)
	->addDecimal("amount", 10, 2, null, null, false, false)
	->addDate("date",false,false)
	->addString("description",250,0,true,false)
	->addReadAccess("consult_student_finance", true)
	->addWriteAccess("edit_student_finance", true)
	->addInsertAccess("edit_student_finance", true)
	->addRemoveAccess("edit_student_finance", true)
	->addRemoveAccessFromForeignTable("People")
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

$model->addTable("Loan")
	->addPrimaryKey("id")
	->addString("reason",250,1,false,false)
	;

$model->addTable("ScheduledPaymentDate")
	->addForeignKey("due_operation", "FinanceOperation", true, true, false, false, true)
	->setPrimary("due_operation")
	->addForeignKey("loan","Loan", true, false, true, true, false)
	->addForeignKey("regular_payment","FinanceRegularPayment", true, false, true, true, false)
	->addReadAccess("consult_student_finance", true)
	->addInsertAccess("manage_finance", true)
	->addRemoveAccess("manage_finance", true)
	;

$model->addTable("PaymentOperation")
	->addForeignKey("due_operation","FinanceOperation",true,false,true,false,false)
	->addForeignKey("payment_operation", "FinanceOperation", true, true, true,false,true)
	->setPrimary("payment_operation")
	->addIndex("due_operation")
	->addReadAccess("consult_student_finance", true)
	->addInsertAccess("edit_student_finance", true)
	->addRemoveAccess("edit_student_finance", true)
	;

?>