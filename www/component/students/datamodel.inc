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
	->onInsert(function($inserted){
		$batches = array();
		foreach ($inserted as $row) {
			if (!isset($batches[$row["fields_values"]["batch"]]))
				$batches[$row["fields_values"]["batch"]] = 1;
			else
				$batches[$row["fields_values"]["batch"]]++;
		}
		foreach ($batches as $batch_id=>$nb_new_students) {
			$batch = PNApplication::$instance->curriculum->getBatch($batch_id);
			$news = PNApplication::$instance->news->getRecentPost("students", "students", "Creation of % student% in batch ".toHTML($batch["name"]));
			if ($news == null)
				PNApplication::$instance->news->post("students", "students", array("batch".$batch_id), "activity", "Creation of $nb_new_students student".($nb_new_students > 1 ? "s" : "")." in batch ".toHTML($batch["name"]));
			else {
				$s = substr($news["html"],12);
				$i = strpos($s," ");
				$nb = intval(substr($s,0,$i));
				$nb += $nb_new_students;
				PNApplication::$instance->news->updatePost($news["id"], "Creation of $nb students in batch ".toHTML($batch["name"]));
			}
		}
	})
	->setRowDescriptionProvider(function($row) {
		$p = PNApplication::$instance->people->getPeople($row["people"], true);
		return "Student ".toHTML($p["first_name"]." ".$p["last_name"]);
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