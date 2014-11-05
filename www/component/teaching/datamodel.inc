<?php 
/* @var $model DataModel */

$model->addTable("StudentsGroupType")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, false)
	->addBoolean("specialization_dependent",false)
	->addBoolean("builtin",false)
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
			$period = SQLQuery::create()->bypassSecurity()
				->select("BatchPeriod")
				->whereValue("BatchPeriod","id",$fields["period"])
				->field("BatchPeriod","name","period_name")
				->join("BatchPeriod", "StudentBatch", array("batch"=>"id"))
				->field("StudentBatch", "name", "batch_name")
				->field("StudentBatch", "id", "batch_id")
				->executeSingleRow()
				;
			$group_type = SQLQuery::create()->bypassSecurity()->select("StudentsGroupType")->whereValue("StudentsGroupType","id",$fields["type"])->field("name")->executeSingleValue();
			$message = $group_type." <i>".toHTML($fields["name"])."</i> created for period ".toHTML($period["period_name"])." of batch ".toHTML($period["batch_name"]);
			if (isset($fields["specialization"]) && $fields["specialization"] <> null) {
				$spe = SQLQuery::create()->bypassSecurity()->select("Specialization")->whereValue("Specialization", "id", $fields["specialization"])->field("Specialization","name")->executeSingleValue();
				$message .= " with specialization ".toHTML($spe);
			}
			PNApplication::$instance->news->post("students", "students", array("batch".$period["batch_id"],"period".$fields["period"]), "activity", $message);
		} catch (Exception $e) { PNApplication::error($e); }
	})
	->onRemove(function($row,$sm){
		$period = SQLQuery::create()->bypassSecurity()
			->select("BatchPeriod")
			->whereValue("BatchPeriod","id",$row["period"])
			->field("BatchPeriod","name","period_name")
			->join("BatchPeriod", "StudentBatch", array("batch"=>"id"))
			->field("StudentBatch", "name", "batch_name")
			->field("StudentBatch", "id", "batch_id")
			->executeSingleRow()
			;
		$group_type = SQLQuery::create()->bypassSecurity()->select("StudentsGroupType")->whereValue("StudentsGroupType","id",$row["type"])->field("name")->executeSingleValue();
		$message = $group_type." <i>".toHTML($row["name"])."</i> removed for period ".toHTML($period["period_name"])." of batch ".toHTML($period["batch_name"]);
		if (isset($row["specialization"]) && $row["specialization"] <> null) {
			$spe = SQLQuery::create()->bypassSecurity()->select("Specialization")->whereValue("Specialization", "id", $row["specialization"])->field("Specialization","name")->executeSingleValue();
			$message .= " with specialization ".toHTML($spe);
		}
		PNApplication::$instance->news->post("students", "students", array("batch".$period["batch_id"],"period".$row["period"]), "activity", $message);
	})
	->setRowDescriptionProvider(function($row) {
		$period = SQLQuery::create()->select("BatchPeriod")->whereValue("BatchPeriod","id",$row["period"])->executeSingleRow();
		$batch_name = SQLQuery::create()->select("StudentBatch")->whereValue("StudentBatch","id",$period["batch"])->field("name")->executeSingleValue();
		$group_type = SQLQuery::create()->bypassSecurity()->select("StudentsGroupType")->whereValue("StudentsGroupType","id",$row["type"])->field("name")->executeSingleValue();
		return $group_type." <i>".toHTML($row["name"])."</i> during period <i>".toHTML($period["name"])."</i> of batch <i>".toHTML($batch_name)."</i>";
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
		$group = PNApplication::$instance->teaching->getGroup($fields_values["group"]);
		$group_type = PNApplication::$instance->teaching->getGroupType($group["type"]);
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
		$group = PNApplication::$instance->teaching->getGroup($row["group"]);
		$group_type = PNApplication::$instance->teaching->getGroupType($group["type"]);
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


$model->addTable("SubjectTeaching")
	->addPrimaryKey("id")
	->addForeignKey("subject", "CurriculumSubject", true, false, true)
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	;
$model->internalGetTable("CurriculumSubject")->addReadAccessFromForeignTable("SubjectTeaching");
$model->addTable("SubjectTeachingGroups")
	->addForeignKey("subject_teaching", "SubjectTeaching", true, false, true)
	->addForeignKey("group", "StudentsGroup", true, false, true)
	->setKey(array("subject_teaching", "group"))
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	;

$model->addTable("TeacherAssignment")
	->addForeignKey("subject_teaching", "SubjectTeaching", true, false, true, false)
	->addForeignKey("people", "People", true, false, true, false)
	->addInteger("hours", 16, 0, 2000, true, false)
	->addEnum("hours_type", array("Per week","Per period"), true)
	->setKey(array("subject_teaching","people"))
	->addReadAccess("consult_curriculum", true)
	->addReadFilter(function(&$q, $table_alias) {
		$q->whereValue($table_alias,"people",PNApplication::$instance->user_management->people_id);
	})
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	// TODO news
	;

?>