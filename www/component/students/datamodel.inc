<?php
/* @var $model DataModel */

$model->addTable("Student")
	->addForeignKey("people", "People", true, false, false, false, true)
	->addForeignKey("batch", "StudentBatch", true, false, true, false)
	->addString("exclusion_reason", 5000, 0, true, false)
	->addDate("exclusion_date", true, false)
	->addForeignKey("specialization", "Specialization", false, false, true)
	->addString("university_id", 50, 0, true, false)
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
	->onInsert(function($fields_values, $id, $sub_model_instance){
		$batch = PNApplication::$instance->curriculum->getBatch($fields_values["batch"]);
		$news = PNApplication::$instance->news->getRecentPost("students", "students", "Creation of % student% in batch ".toHTML($batch["name"]));
		if ($news == null)
			PNApplication::$instance->news->post("students", "students", array("batch".$batch["id"]), "activity", "Creation of 1 student in batch ".toHTML($batch["name"]));
		else {
			$s = substr($news["html"],12);
			$i = strpos($s," ");
			$nb = intval(substr($s,0,$i));
			$nb++;
			PNApplication::$instance->news->updatePost($news["id"], "Creation of $nb students in batch ".toHTML($batch["name"]));
		} 
	})
	;

// allow a student to access to its batch
$model->internalGetTable("StudentBatch")->addReadFilter(function(&$q, $table_alias) {
	$alias = $q->getTableAlias("Student");
	if ($alias == null) {
		$alias = $q->generateTableAlias();
		$q->join("StudentBatch","Student",array("id"=>"batch"),$alias,array("people"=>PNApplication::$instance->user_management->people_id));
	}
	$q->whereValue($alias, "people", PNApplication::$instance->user_management->people_id);
});
// allow a student to access to the periods of its batch
$model->internalGetTable("BatchPeriod")->addReadFilter(function(&$q, $table_alias) {
	$alias = $q->getTableAlias("Student");
	if ($alias == null) {
		$alias = $q->generateTableAlias();
		$q->join("BatchPeriod","Student",array("batch"=>"batch"),$alias,array("people"=>PNApplication::$instance->user_management->people_id));
	}
	$q->whereValue($alias, "people", PNApplication::$instance->user_management->people_id);
});
	
?>