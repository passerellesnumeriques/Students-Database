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
	->setRowDescriptionProvider(function($row) {
		return "Students Group Type <i>".toHTML($row["name"])."</i>";
	})
	;

$model->addTable("StudentsGroup")
	->addPrimaryKey("id")
	->addForeignKey("type", "StudentsGroupType", true, false, true)
	->addForeignKey("parent", "StudentsGroup", true, false, true, true)
	->addForeignKey("period", "BatchPeriod", true, false, true)
	->addForeignKey("specialization", "Specialization", true, false, true, true)
	->addString("name", 100, 1, false, false)
	->addIndex("type")
	->addReadAccess("consult_curriculum", true)
	->addReadAccessFromForeignTable("period")
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) {
		$type = SQLQuery::create()->bypassSecurity()->select("StudentsGroupType")->whereValue("StudentsGroupType","id",$row["type"])->field("name")->executeSingleValue();
		$period = PNApplication::$instance->curriculum->getBatchPeriod($row["period"]);
		$period = DataModel::get()->internalGetTable("BatchPeriod")->getRowDescription($period);
		return toHTML($type)." ".toHTML($row["name"])." in ".$period;
	})
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
$model->internalGetTable("BatchPeriodSpecialization")
	->onBeforeRemove(function($row,$sm) {
		$groups = SQLQuery::create()->bypassSecurity()
			->select("StudentsGroup")
			->whereValue("StudentsGroup","period",$row["period"])
			->whereValue("StudentsGroup","specialization",$row["specialization"])
			->execute();
		if (count($groups) == 0) return null; // ok
		$s = "The following students groups are attached to it: ";
		$first = true;
		foreach ($groups as $g) {
			if ($first) $first = false; else $s .= ", ";
			$s .= toHTML($g["name"]);
		}
		return $s;
	});

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
	->setRowDescriptionProvider(function($row) {
		$student = PNApplication::$instance->people->getPeople($row["people"],true);
		$q = SQLQuery::create()
			->select("StudentsGroup")
			->whereValue("StudentsGroup","id",$row["group"])
			->join("StudentsGroup","StudentsGroupType",array("type"=>"id"));
		PNApplication::$instance->curriculum->joinBatchPeriod($q, "StudentsGroup", "period");
		$group = $q
			->field("StudentsGroup","name","name")
			->field("StudentsGroupType","name","type")
			->field("BatchPeriod", "name", "period_name")
			->limit(0,1)->executeSingleRow();
		return "Assignment of student <i>".toHTML($student["first_name"]." ".$student["last_name"])."</i> to ".toHTML($group["type"])." <i>".toHTML($group["name"])."</i> in <i>".toHTML($group["period_name"])."</i>";
	})
	->onInsert(function($fields_values, $id, $sub_model_instance){
		$group = PNApplication::$instance->students_groups->getGroup($fields_values["group"]);
		$group_type = PNApplication::$instance->students_groups->getGroupType($group["type"]);
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
		$group_type = PNApplication::$instance->students_groups->getGroupType($group["type"]);
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