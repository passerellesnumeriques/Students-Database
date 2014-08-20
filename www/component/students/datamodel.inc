<?php
/* @var $model DataModel */

$model->addTable("Student")
	->addForeignKey("people", "People", true, true, false, false, true)
	->addForeignKey("batch", "StudentBatch", true, false, true, false)
	->addString("exclusion_reason", 5000, 0, true, false)
	->addDate("exclusion_date", true, false)
	->addForeignKey("specialization", "Specialization", false, false, true)
	->setPrimary("people")
	->addIndex("batch")
	->setRoot()
	->addReadFilter(function(&$q, $table_alias) {
		$q->whereValue($table_alias,"people",PNApplication::$instance->user_management->people_id);
	})
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	;

$model->addTable("StudentClass")
	->addForeignKey("people", "People", true, false, true)
	->addForeignKey("class", "AcademicClass", false, false, true)
	->setKey(array("people","class"))
	->addIndex("class")
	->addReadFilter(function(&$q, $table_alias) {
		$q->whereValue($table_alias,"people",PNApplication::$instance->user_management->people_id);
	})
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	;

$model->internalGetTable("StudentBatch")->addReadFilter(function(&$q, $table_alias) {
	$alias = $q->getTableAlias("Student");
	if ($alias == null) {
		$alias = $q->generateTableAlias();
		$q->join("StudentBatch","Student",array("id"=>"batch"),$alias,array("people"=>PNApplication::$instance->user_management->people_id));
	}
	$q->whereValue($alias, "people", PNApplication::$instance->user_management->people_id);
});
$model->internalGetTable("BatchPeriod")->addReadFilter(function(&$q, $table_alias) {
	$alias = $q->getTableAlias("Student");
	if ($alias == null) {
		$alias = $q->generateTableAlias();
		$q->join("BatchPeriod","Student",array("batch"=>"batch"),$alias,array("people"=>PNApplication::$instance->user_management->people_id));
	}
	$q->whereValue($alias, "people", PNApplication::$instance->user_management->people_id);
});
	
?>