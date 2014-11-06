<?php 
/* @var $model DataModel */

$model->addTable("StudentsGroupType")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->addBoolean("specialization_dependent",false)
	->addBoolean("builtin",false)
	->addBoolean("sub_groups",false)
	->addReadAccess("consult_curriculum", true)
	->addReadAccessFromForeignTable("StudentsGroup")
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	;

$model->addTable("StudentsGroup")
	->addPrimaryKey("id")
	->addForeignKey("type", "StudentsGroupType", true, false, true)
	->addForeignKey("parent", "StudentsGroup", true, false, true, true)
	->addForeignKey("period", "BatchPeriod", true, false, true)
	->addForeignKey("specialization", "Specialization", true, false, true, true)
	->addString("name", 100, 1, false, false)
	->addReadAccess("consult_curriculum", true)
	->addReadAccessFromForeignTable("period")
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			$period = PNApplication::$instance->curriculum->getBatchPeriod($fields["period"]);
			$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
			$group_type = PNApplication::$instance->students_groups->getGroupTypeName($fields["type"]);
			$message = $group_type." <i>".toHTML($fields["name"])."</i> created for period ".toHTML($period["name"])." of batch ".toHTML($batch["name"]);
			if (isset($fields["specialization"]) && $fields["specialization"] <> null) {
				$spe = PNApplication::$instance->curriculum->getSpecializationName($fields["specialization"]);
				$message .= " with specialization ".toHTML($spe);
			}
			PNApplication::$instance->news->post("students", "students", array("batch".$batch["id"],"period".$fields["period"]), "activity", $message);
		} catch (Exception $e) { PNApplication::error($e); }
	})
	->onRemove(function($row,$sm){
		$period = PNApplication::$instance->curriculum->getBatchPeriod($row["period"]);
		$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
		$group_type = PNApplication::$instance->students_groups->getGroupTypeName($row["type"]);
		$message = $group_type." <i>".toHTML($row["name"])."</i> removed for period ".toHTML($period["name"])." of batch ".toHTML($batch["name"]);
		if (isset($row["specialization"]) && $row["specialization"] <> null) {
			$spe = PNApplication::$instance->curriculum->getSpecializationName($row["specialization"]);
			$message .= " with specialization ".toHTML($spe);
		}
		PNApplication::$instance->news->post("students", "students", array("batch".$period["batch"],"period".$row["period"]), "activity", $message);
	})
	->setRowDescriptionProvider(function($row) {
		$period = PNApplication::$instance->curriculum->getBatchPeriod($row["period"]);
		$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
		$group_type = PNApplication::$instance->students_groups->getGroupTypeName($row["type"]);
		return $group_type." <i>".toHTML($row["name"])."</i> during period <i>".toHTML($period["name"])."</i> of batch <i>".toHTML($batch["name"])."</i>";
	})
	;

$model->addTable("StudentGroup")
	->addForeignKey("people", "People", true, false, true)
	->addForeignKey("group", "StudentsGroup", true, false, true)
	->setKey(array("people","group"))
	->addIndex("people")
	->addIndex("group")
	->addReadFilter(function(&$q, $table_alias) { // allow a student to read its own groups
		$q->whereValue($table_alias,"people",PNApplication::$instance->user_management->people_id);
	})
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true) // TODO
	->addInsertAccess("manage_batches", true)// TODO
	->addRemoveAccess("manage_batches", true)// TODO
	->onInsert(function($fields_values, $id, $sub_model_instance){
		$group = PNApplication::$instance->students_groups->getGroup($fields_values["group"]);
		$group_type = PNApplication::$instance->students_group->getGroupType($group["type"]);
		$period = PNApplication::$instance->curriculum->getBatchPeriod($group["period"]);
		$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
		$news = PNApplication::$instance->news->getRecentPost("students", "students", "% student% of batch ".toHTML($batch["name"])." assigned to ".toHTML($group_type["name"])." ".toHTML($group["name"])." for period ".toHTML($period["name"]));
		if ($news == null)
			PNApplication::$instance->news->post("students", "students", array("batch".$batch["id"],"period".$period["id"],"group".$group["id"]), "activity", "1 student of batch ".toHTML($batch["name"])." assigned to ".toHTML($group_type["name"])." ".toHTML($group["name"])." for period ".toHTML($period["name"]));
		else {
			$i = strpos($news["html"]," ");
			$nb = intval(substr($news["html"],0,$i));
			$nb++;
			PNApplication::$instance->news->updatePost($news["id"], "$nb students of batch ".toHTML($batch["name"])." assigned to ".toHTML($group_type["name"])." ".toHTML($group["name"])." for period ".toHTML($period["name"]));
		}
	})
	->onRemove(function($row, $sub_model_instance){
		$group = PNApplication::$instance->students_groups->getGroup($row["group"]);
		$group_type = PNApplication::$instance->students_group->getGroupType($group["type"]);
		$period = PNApplication::$instance->curriculum->getBatchPeriod($group["period"]);
		$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
		$news = PNApplication::$instance->news->getRecentPost("students", "students", "% student% of batch ".toHTML($batch["name"])." unassigned from ".toHTML($group_type["name"])." ".toHTML($group["name"])." for period ".toHTML($period["name"]));
		if ($news == null)
			PNApplication::$instance->news->post("students", "students", array("batch".$batch["id"],"period".$period["id"],"group".$group["id"]), "activity", "1 student of batch ".toHTML($batch["name"])." unassigned from ".toHTML($group_type["name"])." ".toHTML($group["name"])." for period ".toHTML($period["name"]));
		else {
			$i = strpos($news["html"]," ");
			$nb = intval(substr($news["html"],0,$i));
			$nb++;
			PNApplication::$instance->news->updatePost($news["id"], "$nb students of batch ".toHTML($batch["name"])." unassigned from ".toHTML($group_type["name"])." ".toHTML($group["name"])." for period ".toHTML($period["name"]));
		}
	})
	;

?>