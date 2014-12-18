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
	->addReadAccessFromForeignTable("StudentGroup")
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) {
		$type = SQLQuery::create()->bypassSecurity()->select("StudentsGroupType")->whereValue("StudentsGroupType","id",$row["type"])->field("name")->executeSingleValue();
		$period = PNApplication::$instance->curriculum->getBatchPeriod($row["period"]);
		$period = DataModel::get()->internalGetTable("BatchPeriod")->getRowDescription($period);
		return toHTML($type)." ".toHTML($row["name"])." in ".$period;
	})
	->onInsert(function($inserted) {
		foreach ($inserted as $row)
			try {
				$period = PNApplication::$instance->curriculum->getBatchPeriod($row["fields_values"]["period"]);
				$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
				$group_type = PNApplication::$instance->students_groups->getGroupTypeName($row["fields_values"]["type"]);
				$message = $group_type." <i>".toHTML($row["fields_values"]["name"])."</i> created for period ".toHTML($period["name"])." of batch ".toHTML($batch["name"]);
				if (isset($row["fields_values"]["specialization"]) && $row["fields_values"]["specialization"] <> null) {
					$spe = PNApplication::$instance->curriculum->getSpecializationName($row["fields_values"]["specialization"]);
					$message .= " with specialization ".toHTML($spe);
				}
				PNApplication::$instance->news->post("students", "students", array("batch".$batch["id"],"period".$row["fields_values"]["period"]), "activity", $message);
			} catch (Exception $e) { PNApplication::error($e); }
	})
	->onRemove(function($table, $rows, $sm){
		foreach ($rows as $row) {
			$period = PNApplication::$instance->curriculum->getBatchPeriod($row["period"]);
			$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
			$group_type = PNApplication::$instance->students_groups->getGroupTypeName($row["type"]);
			$message = $group_type." <i>".toHTML($row["name"])."</i> removed for period ".toHTML($period["name"])." of batch ".toHTML($batch["name"]);
			if (isset($row["specialization"]) && $row["specialization"] <> null) {
				$spe = PNApplication::$instance->curriculum->getSpecializationName($row["specialization"]);
				$message .= " with specialization ".toHTML($spe);
			}
			PNApplication::$instance->news->post("students", "students", array("batch".$period["batch"],"period".$row["period"]), "activity", $message);
		}
	})
	->setRowDescriptionProvider(function($row) {
		$period = PNApplication::$instance->curriculum->getBatchPeriod($row["period"]);
		$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
		$group_type = PNApplication::$instance->students_groups->getGroupTypeName($row["type"]);
		return $group_type." <i>".toHTML($row["name"])."</i> during period <i>".toHTML($period["name"])."</i> of batch <i>".toHTML($batch["name"])."</i>";
	})
	;
$model->internalGetTable("BatchPeriodSpecialization")
	->onBeforeRemove(function($table,$rows,$sm) {
		foreach ($rows as $row) {
			$groups = SQLQuery::create()->bypassSecurity()
				->select("StudentsGroup")
				->whereValue("StudentsGroup","period",$row["period"])
				->whereValue("StudentsGroup","specialization",$row["specialization"])
				->execute();
			if (count($groups) == 0) continue; // ok
			$spe_name = SQLQuery::create()->bypassSecurity()->select("Specialization")->whereValue("Specialization","id",$row["specialization"])->field("name")->executeSingleValue();
			$period_name = SQLQuery::create()->bypassSecurity()->select("BatchPeriod")->whereValue("BatchPeriod","id",$row["period"])->field("name")->executeSingleValue();
			$s = "Specialization $spe_name cannot be removed from $period_name because the following students groups are attached to it: ";
			$first = true;
			foreach ($groups as $g) {
				if ($first) $first = false; else $s .= ", ";
				$s .= toHTML($g["name"]);
			}
			return $s;
		}
		return null;
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
	->onInsert(function($inserted){
		$groups = array();
		foreach ($inserted as $row)
			if (!isset($groups[$row["fields_values"]["group"]]))
				$groups[$row["fields_values"]["group"]] = 1;
			else
				$groups[$row["fields_values"]["group"]]++;
		foreach ($groups as $group_id=>$nb_assigned) {
			$group = PNApplication::$instance->students_groups->getGroup($group_id);
			$group_type = PNApplication::$instance->students_groups->getGroupType($group["type"]);
			$period = PNApplication::$instance->curriculum->getBatchPeriod($group["period"]);
			$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
			$news = PNApplication::$instance->news->getRecentPost("students", "students", "% student% of batch ".toHTML($batch["name"])." assigned to ".toHTML($group_type["name"])." ".toHTML($group["name"])." for period ".toHTML($period["name"]));
			if ($news == null)
				PNApplication::$instance->news->post("students", "students", array("batch".$batch["id"],"period".$period["id"],"group".$group["id"]), "activity", "$nb_assigned student".($nb_assigned > 1 ? "s" : "")." of batch ".toHTML($batch["name"])." assigned to ".toHTML($group_type["name"])." ".toHTML($group["name"])." for period ".toHTML($period["name"]));
			else {
				$i = strpos($news["html"]," ");
				$nb = intval(substr($news["html"],0,$i));
				$nb += $nb_assigned;
				PNApplication::$instance->news->updatePost($news["id"], "$nb students of batch ".toHTML($batch["name"])." assigned to ".toHTML($group_type["name"])." ".toHTML($group["name"])." for period ".toHTML($period["name"]));
			}
		}
	})
	->onRemove(function($table, $rows, $sub_model_instance){
		foreach ($rows as $row) {
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
		}
	})
	;

?>