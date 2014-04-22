<?php
/* @var $model DataModel */

/* Academic Calendar */

$model->addTable("AcademicYear")
	->addPrimaryKey("id")
	->addInteger("year", 16, 2000)
	->addString("name", 50, 1, false, true)
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRoot()
	->setRowDescriptionProvider(function($row) {
		return "Academic Year ".$row["name"];
	})
	;
$model->addTable("AcademicPeriod")
	->addPrimaryKey("id")
	->addForeignKey("year", "AcademicYear", true, false, true)
	->addString("name", 50, 1, false, false)
	->addDate("start", false, false, "2004-01-01")
	->addDate("end", false, false, "start")
	->addInteger("weeks",8,1,200, false, false)
	->addInteger("weeks_break",8,0,200,false,false)
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) {
		$year = SQLQuery::create()->select("AcademicYear")->whereValue("AcademicYear", "id", $row["year"])->executeSingleRow();
		return "Academic Period ".$row["name"]." in Academic Year ".$year["name"];
	})
	;

/* Batches */

$model->addTable("StudentBatch")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->addDate("start_date", false, false, "2004-01-01", "end_date")
	->addDate("end_date", false, false, "start_date", null)
	->setRoot()
	->addReadAccess("consult_curriculum", true)
	->addColumnWriteAccess("name","edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
   	->onInsert(function($fields,$id,$sub_model_instance) {
  		try {
  			PNApplication::$instance->news->post("students", "batch", null, "Batch ".$fields["name"]." created.");
  		} catch (Exception $e) { PNApplication::error($e); }
   	})
 	->setRowDescriptionProvider(function($row) {
 		return "Batch <i>".htmlentities($row["name"])."</i>";
 	})
 	;
 	
$model->addTable("BatchPeriod")
	->addPrimaryKey("id")
	->addForeignKey("batch", "StudentBatch", true, false, true)
	->addForeignKey("academic_period", "AcademicPeriod", true, false, true)
	->addString("name", 50, 1, false, false)
	->addReadAccess("consult_curriculum", true)
	->addColumnWriteAccess("name","edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
 	->setRowDescriptionProvider(function($row) {
 		$batch_name = SQLQuery::create()->bypassSecurity()->select("StudentBatch")->whereValue("StudentBatch","id",$row["batch"])->field("name")->executeSingleValue();
 		return "Period <i>".htmlentities($row["name"])."</i> of Batch <i>$batch_name</i>";
 	})
	;
 	
$model->addTable("Specialization")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->setRoot()
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
 	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			PNApplication::$instance->news->post("students", "curriculum", null, "Specialization ".$fields["name"]." created.");
		} catch (Exception $e) { PNApplication::error($e); }
 	})
 	->setRowDescriptionProvider(function($row) {
 		return "Specialization <i>".htmlentities($row["name"])."</i>";
 	})
	;

$model->addTable("BatchPeriodSpecialization")
	->addForeignKey("period", "BatchPeriod", true, false, true, false, false)
	->addForeignKey("specialization", "Specialization", true, false, true, false, false)
	->setKey(array("period","specialization"))
	->addReadAccess("consult_curriculum", true)
	->setRowDescriptionProvider(function($row) {
		$spe_name = SQLQuery::create()->select("Specialization")->whereValue("Specialization","id",$row["specialization"])->field("name")->executeSingleValue();
		$period = SQLQuery::create()->select("BatchPeriod")->whereValue("BatchPeriod","id",$row["period"])->executeSingleRow();
		$batch_name = SQLQuery::create()->select("StudentBatch")->whereValue("StudentBatch","id",$period["batch"])->field("name")->executeSingleValue();
		return "Specialization <i>".htmlentities($spe_name)."</i> on Period <i>".htmlentities($period["name"])."</i> of batch <i>".htmlentities($batch_name)."</i>";
	})
	;
	
$model->addTable("AcademicClass")
	->addPrimaryKey("id")
	->addForeignKey("period", "BatchPeriod", true, false, true)
	->addForeignKey("specialization", "Specialization", false, false, true)
	->addString("name", 100, 1, false, false)
	->addReadAccess("consult_curriculum", true)
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
			$message = "Class <i>".$fields["name"]."</i> created for period ".$period["period_name"]." of batch ".$period["batch_name"];
			if (isset($fields["specialization"]) && $fields["specialization"] <> null) {
				$spe = SQLQuery::create()->bypassSecurity()->select("Specialization")->whereValue("Specialization", "id", $fields["specialization"])->field("Specialization","name")->executeSingleValue();
				$message .= " with specialization ".$spe;
			}
			PNApplication::$instance->news->post("students", "batch", array("batch".$period["batch_id"],"period".$fields["period"]), $message);
		} catch (Exception $e) { PNApplication::error($e); }
 	})
 	->setRowDescriptionProvider(function($row) {
 		$period = SQLQuery::create()->select("BatchPeriod")->whereValue("BatchPeriod","id",$row["period"])->executeSingleRow();
		$batch_name = SQLQuery::create()->select("StudentBatch")->whereValue("StudentBatch","id",$period["batch"])->field("name")->executeSingleValue();
 		return "Class <i>".htmlentities($row["name"])."</i> during period <i>".htmlentities($period["name"])."</i> of batch <i>".htmlentities($batch_name)."</i>";
 	})
	;
 	
/* Subjects */
 	
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
 	->setRowDescriptionProvider(function($row) {
 		return "Subjects' category ".htmlentities($row["name"]);
 	})
	;
$model->addTable("CurriculumSubject")
	->addPrimaryKey("id")
	->addForeignKey("category", "CurriculumSubjectCategory", true, false, true)
	->addForeignKey("period", "BatchPeriod", true, false, true)
	->addForeignKey("specialization", "Specialization", true, false, true, true, false)
	->addString("code", 100, 1, false, false)
	->addString("name", 100, 1, false, false)
	->addInteger("hours", 16, 0, 2000, true, false)
	->addEnum("hours_type", array("Per week","Per period"), true)
	->addInteger("coefficient", 8, 0, 50, true, false)
	->addReadAccess("consult_curriculum", true)
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
 	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			$category = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectCategory")->whereValue("CurriculumSubjectCategory","id",$fields["category"])->field("CurriculumSubjectCategory","name")->executeSingleValue();
			$period = SQLQuery::create()->bypassSecurity()
				->select("BatchPeriod")
				->whereValue("BatchPeriod","id",$fields["period"])
				->field("BatchPeriod","name","period_name")
				->join("BatchPeriod", "StudentBatch", array("batch"=>"id"))
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
 	->setRowDescriptionProvider(function($row) {
 		$r = SQLQuery::create()
 			->select("BatchPeriod")
 			->whereValue("BatchPeriod", "id", $row["period"])
 			->join("BatchPeriod","StudentBatch",array("batch"=>"id"))
 			->field("BatchPeriod","name","period_name")
 			->field("StudentBatch","name","batch_name")
 			->executeSingleRow();
 		return "Subject ".htmlentities($row["name"])." in period ".htmlentities($r["period_name"])." of batch ".htmlentities($r["batch_name"]);
 	})
 	;
 	
/* Teachers */
 	
$model->addTable("TeacherDates")
	->addPrimaryKey("id")
 	->addForeignKey("people", "People", true, false, true)
 	->addDate("start", false, false, "2004-01-01","end")
 	->addDate("end", true, false, "start", null)
 	->addReadAccess("consult_curriculum", true)
 	->addWriteAccess("edit_curriculum", true)
 	->addInsertAccess("edit_curriculum", true)
 	->addRemoveAccess("edit_curriculum", true)
 	;

$model->addTable("TeacherAssignment")
	->addForeignKey("class", "AcademicClass", true, false, true)
	->addForeignKey("subject", "CurriculumSubject", true, false, true)
	->addForeignKey("people", "People", true, false, true)
	->setKey(array("class","subject","people"))
 	->addReadAccess("consult_curriculum", true)
 	->addWriteAccess("edit_curriculum", true)
 	->addInsertAccess("edit_curriculum", true)
 	->addRemoveAccess("edit_curriculum", true)
	;
?>