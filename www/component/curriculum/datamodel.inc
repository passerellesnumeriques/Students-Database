<?php
use datamodel\FieldsDisplayHandler;

/* @var $model DataModel */
$model->addTable("StudentBatch")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->addDate("start_date", false, false, "2004-01-01", "end_date")
	->addDate("end_date", false, false, "start_date", null)
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
			PNApplication::$instance->news->post("education", "batch", null, "Batch ".$fields["name"]." created.");
		} catch (Exception $e) { PNApplication::error($e); }
 	})
 	;
$model->addTable("Specialization")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->addReadAccess("consult_students_list", true)
	->addWriteAccess("manage_batches", true)
	->addInsertAccess("manage_batches", true)
	->addRemoveAccess("manage_batches", true)
 	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			PNApplication::$instance->news->post("education", "curriculum", null, "Specialization ".$fields["name"]." created.");
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
			$batch = SQLQuery::create()->bypass_security()->select("StudentBatch")->where_value("StudentBatch","id",$fields["batch"])->field("StudentBatch","name")->execute_single_value();
			PNApplication::$instance->news->post("education", "curriculum", array("batch".$fields["batch"]), "Academic period ".$fields["name"]." created for batch ".$batch.".");
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
			$period = SQLQuery::create()->bypass_security()
				->select("AcademicPeriod")
				->where_value("AcademicPeriod","id",$fields["period"])
				->field("AcademicPeriod","name","period_name")
				->join("AcademicPeriod", "StudentBatch", array("batch"=>"id"))
				->field("StudentBatch", "name", "batch_name")
				->field("StudentBatch", "id", "batch_id")
				->execute_single_row()
				;
			$message = "Class <i>".$fields["name"]."</i> created for period ".$period["period_name"]." of batch ".$period["batch_name"];
			if (isset($fields["specialization"]) && $fields["specialization"] <> null) {
				$spe = SQLQuery::create()->bypass_security()->select("Specialization")->where_value("Specialization", "id", $fields["specialization"])->field("Specialization","name")->execute_single_value();
				$message .= " with specialization ".$spe;
			}
			PNApplication::$instance->news->post("education", "batch", array("batch".$period["batch_id"],"period".$fields["period"]), $message);
		} catch (Exception $e) { PNApplication::error($e); }
 	})
	;
$model->addTable("CurriculumSubjectCategory")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, false)
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
 	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			PNApplication::$instance->news->post("education", "curriculum", null, "Category of subject <i>".$fields["name"]."</i> created for the curricula.");
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
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
 	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			$category = SQLQuery::create()->bypass_security()->select("CurriculumSubjectCategory")->where_value("CurriculumSubjectCategory","id",$fields["category"])->field("CurriculumSubjectCategory","name")->execute_single_value();
			$period = SQLQuery::create()->bypass_security()
				->select("AcademicPeriod")
				->where_value("AcademicPeriod","id",$fields["period"])
				->field("AcademicPeriod","name","period_name")
				->join("AcademicPeriod", "StudentBatch", array("batch"=>"id"))
				->field("StudentBatch", "name", "batch_name")
				->field("StudentBatch", "id", "batch_id")
				->execute_single_row()
				;
			$message = "Subject <i>".$fields["name"]."</i> (code <i>".$fields["code"]."</i>) created in category ".$category." for period ".$period["period_name"]." of batch ".$period["batch_name"];
			if (isset($fields["specialization"]) && $fields["specialization"] <> null) {
				$spe = SQLQuery::create()->bypass_security()->select("Specialization")->where_value("Specialization", "id", $fields["specialization"])->field("Specialization","name")->execute_single_value();
				$message .= " in specialization ".$spe;
			}
			PNApplication::$instance->news->post("education", "curriculum", array("batch".$period["batch_id"],"period".$fields["period"]), $message);
		} catch (Exception $e) { PNApplication::error($e); }
 	})
	;
?>