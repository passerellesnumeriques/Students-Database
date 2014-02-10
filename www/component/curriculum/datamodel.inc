<?php
use datamodel\FieldsDisplayHandler;

/* @var $model DataModel */
$model->addTable("StudentBatch")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->addDate("start_date", false, false, "2004-01-01", "end_date")
	->addDate("end_date", false, false, "start_date", null)
	->setRoot()
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Student", "Batch",
		array(
			"name"=>"Batch Name",
			"start_date"=>"Integration Date",
			"end_date"=>"Graduation Date",
		)
	))
 	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			PNApplication::$instance->news->post("students", "batch", null, "Batch ".$fields["name"]." created.");
		} catch (Exception $e) { PNApplication::error($e); }
 	})
 	;
$model->addTable("Specialization")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->setRoot()
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
 	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			PNApplication::$instance->news->post("students", "curriculum", null, "Specialization ".$fields["name"]." created.");
		} catch (Exception $e) { PNApplication::error($e); }
 	})
	;

$model->addTable("AcademicPeriod")
	->addPrimaryKey("id")
	->addForeignKey("batch", "StudentBatch", true, false, true)
	->addString("name", 100, 1, false, false)
	->addDate("start_date", false, false, "2004-01-01", "end_date")
	->addDate("end_date", false, false, "start_date", null)
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Student", "Academic Period",
		array(
			"name"=>"Period Name",
			"start_date"=>"Period Start",
			"end_date"=>"Period End",
		)
	))
 	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			$batch = SQLQuery::create()->bypassSecurity()->select("StudentBatch")->whereValue("StudentBatch","id",$fields["batch"])->field("StudentBatch","name")->executeSingleValue();
			PNApplication::$instance->news->post("students", "batch", array("batch".$fields["batch"]), "Academic period ".$fields["name"]." created for batch ".$batch.".");
		} catch (Exception $e) { PNApplication::error($e); }
 	})
	;
$model->addTable("AcademicPeriodSpecialization")
	->addForeignKey("period", "AcademicPeriod", true, false, true, false, false)
	->addForeignKey("specialization", "Specialization", true, false, true, false, false)
	->setKey(array("period","specialization"))
	->addReadAccess("consult_students_list", true)
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	;
$model->addTable("AcademicClass")
	->addPrimaryKey("id")
	->addForeignKey("period", "AcademicPeriod", true, false, true)
	->addForeignKey("specialization", "Specialization", false, false, true)
	->addString("name", 100, 1, false, false)
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Student", "Class Name", array("name"=>"Class Name")))
 	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			$period = SQLQuery::create()->bypassSecurity()
				->select("AcademicPeriod")
				->whereValue("AcademicPeriod","id",$fields["period"])
				->field("AcademicPeriod","name","period_name")
				->join("AcademicPeriod", "StudentBatch", array("batch"=>"id"))
				->field("StudentBatch", "name", "batch_name")
				->field("StudentBatch", "id", "batch_id")
				->executeSingleRow()
				;
			$message = "Class <i>".$fields["name"]."</i> created for period ".$period["period_name"]." of batch ".$period["batch_name"];
			if (isset($fields["specialization"]) && $fields["specialization"] <> null) {
				$spe = SQLQuery::create()->bypassSecurity()->select("Specialization")->whereValue("Specialization", "id", $fields["specialization"])->field("Specialization","name")->executeSingleValue();
				$message .= " with specialization ".$spe;
			}
			PNApplication::$instance->news->post("students", "batch", array("batch".$period["batch_id"],"period".$fields["period"]), $message);
		} catch (Exception $e) { PNApplication::error($e); }
 	})
	;
$model->addTable("CurriculumSubjectCategory")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, false)
	->setRoot()
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
 	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			PNApplication::$instance->news->post("students", "curriculum", null, "Category of subject <i>".$fields["name"]."</i> created for the curricula.");
		} catch (Exception $e) { PNApplication::error($e); }
 	})
	;
$model->addTable("CurriculumSubject")
	->addPrimaryKey("id")
	->addForeignKey("category", "CurriculumSubjectCategory", true, false, true)
	->addForeignKey("period", "AcademicPeriod", true, false, true)
	->addForeignKey("specialization", "Specialization", true, false, true, true, false)
	->addString("code", 100, 1, false, false)
	->addString("name", 100, 1, false, false)
	->addInteger("hours", 16, 0, 2000, true, false)
	->addEnum("hours_type", array("Per week","Per period"), true)
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
 	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			$category = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectCategory")->whereValue("CurriculumSubjectCategory","id",$fields["category"])->field("CurriculumSubjectCategory","name")->executeSingleValue();
			$period = SQLQuery::create()->bypassSecurity()
				->select("AcademicPeriod")
				->whereValue("AcademicPeriod","id",$fields["period"])
				->field("AcademicPeriod","name","period_name")
				->join("AcademicPeriod", "StudentBatch", array("batch"=>"id"))
				->field("StudentBatch", "name", "batch_name")
				->field("StudentBatch", "id", "batch_id")
				->executeSingleRow()
				;
			$message = "Subject <i>".$fields["name"]."</i> (code <i>".$fields["code"]."</i>) created in category ".$category." for period ".$period["period_name"]." of batch ".$period["batch_name"];
			if (isset($fields["specialization"]) && $fields["specialization"] <> null) {
				$spe = SQLQuery::create()->bypassSecurity()->select("Specialization")->whereValue("Specialization", "id", $fields["specialization"])->field("Specialization","name")->executeSingleValue();
				$message .= " in specialization ".$spe;
			}
			PNApplication::$instance->news->post("students", "curriculum", array("batch".$period["batch_id"],"period".$fields["period"]), $message);
		} catch (Exception $e) { PNApplication::error($e); }
 	})
	;
?>