<?php
/* @var $model DataModel */

/* Academic Calendar */

$model->addTable("AcademicYear")
	->addPrimaryKey("id")
	->addInteger("year", 16, 2000)
	->addString("name", 50, 1, false, true)
	->allowReadAccess()
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
	->allowReadAccess()
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
   	->onInsert(function($inserted) {
   		foreach ($inserted as $row)
	  		try {
	  			PNApplication::$instance->news->post("students", "students", array("batch".$row["id"]), "activity", "Batch ".toHTML($row["fields_values"]["name"])." created.");
	  		} catch (Exception $e) { PNApplication::error($e); }
   	})
   	->onRemove(function($table, $rows, $sub_model_instance) {
   		foreach ($rows as $row)
	   		try {
	   			PNApplication::$instance->news->post("students", "students", array("batch".$row["id"]), "activity", "Batch ".toHTML($row["name"])." removed.");
	   		} catch (Exception $e) { PNApplication::error($e); }
   	})
   	->onBeforeUpdate(function(&$keys_fields_values,$sub_model_instance) {
   		foreach ($keys_fields_values as $updated) {
   			$keys = $updated[0];
   			$fields_values = $updated[1];
   			if (isset($fields_values["name"]) || isset($fields_values["start_date"]) || isset($fields_values["end_date"])) {
   				$rows = SQLQuery::create()->bypassSecurity()->select("StudentBatch")->whereIn("StudentBatch","id",$keys)->execute();
   				foreach ($rows as $row) {
	   				if (isset($fields_values["name"]) && $fields_values["name"] <> $row["name"])
			   	  		try {
			   				PNApplication::$instance->news->post("students", "students", array("batch".$row["id"]), "activity", "Batch ".toHTML($row["name"])." renamed to ".toHTML($fields_values["name"]).".");
			   			} catch (Exception $e) { PNApplication::error($e); }
			   		if (isset($fields_values["start_date"]) && $fields_values["start_date"] <> $row["start_date"])
			   	  		try {
			   				PNApplication::$instance->news->post("students", "students", array("batch".$row["id"]), "activity", "Integration date of Batch ".toHTML($row["name"])." changed from ".$row["start_date"]." to ".$fields_values["start_date"].".");
			   			} catch (Exception $e) { PNApplication::error($e); }
			   		if (isset($fields_values["end_date"]) && $fields_values["end_date"] <> $row["end_date"])
			   	  		try {
			   				PNApplication::$instance->news->post("students", "students", array("batch".$row["id"]), "activity", "Graduation date of Batch ".toHTML($row["name"])." changed from ".$row["end_date"]." to ".$fields_values["end_date"].".");
			   			} catch (Exception $e) { PNApplication::error($e); }
   				}
   			}
   		}
   	})
 	->setRowDescriptionProvider(function($row) {
 		return "Batch <i>".toHTML($row["name"])."</i>";
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
 		return "Period <i>".toHTML($row["name"])."</i> of Batch <i>".toHTML($batch_name)."</i>";
 	})
	;
 	
$model->addTable("Specialization")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->setRoot()
	->allowReadAccess()
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
 	->onInsert(function($inserted) {
 		foreach ($inserted as $row)
			try {
				PNApplication::$instance->news->post("students", "curriculum", null, "activity", "Specialization ".toHTML($row["fields_values"]["name"])." created.");
			} catch (Exception $e) { PNApplication::error($e); }
 	})
 	->onRemove(function($table, $rows, $sub_model_instance){
 		foreach ($rows as $row)
 			PNApplication::$instance->news->post("students", "curriculum", null, "activity", "Specialization ".toHTML($row["name"])." removed.");
 	})
 	->onBeforeUpdate(function(&$keys_fields_values, $sub_model_instance){
 		foreach ($keys_fields_values as $updated) {
 			$keys = $updated[0];
 			$fields_values = $updated[1];
 			$prev_names = SQLQuery::create()->bypassSecurity()->select("Specialization")->whereIn("Specialization","id",$keys)->field("name")->executeSingleField();
 			foreach ($prev_names as $prev_name)
 				PNApplication::$instance->news->post("students", "curriculum", null, "activity", "Specialization ".toHTML($prev_name)." renamed into ".toHTML($fields_values["name"]));
 		}
 	})
 	->setRowDescriptionProvider(function($row) {
 		return "Specialization <i>".toHTML($row["name"])."</i>";
 	})
	;

$model->addTable("BatchPeriodSpecialization")
	->addForeignKey("period", "BatchPeriod", true, false, true, false, false)
	->addForeignKey("specialization", "Specialization", true, false, true, false, false)
	->setKey(array("period","specialization"))
	->addReadAccess("consult_curriculum", true)
	->addReadAccessFromForeignTable("BatchPeriod")
	->addRemoveAccessFromForeignTable("BatchPeriod")
	->addRemoveAccessFromForeignTable("Specialization")
	->setRowDescriptionProvider(function($row) {
		$spe_name = SQLQuery::create()->select("Specialization")->whereValue("Specialization","id",$row["specialization"])->field("name")->executeSingleValue();
		$period = SQLQuery::create()->select("BatchPeriod")->whereValue("BatchPeriod","id",$row["period"])->executeSingleRow();
		$batch_name = SQLQuery::create()->select("StudentBatch")->whereValue("StudentBatch","id",$period["batch"])->field("name")->executeSingleValue();
		return "Specialization <i>".toHTML($spe_name)."</i> on Period <i>".toHTML($period["name"])."</i> of batch <i>".toHTML($batch_name)."</i>";
	})
	;
	
 	
/* Subjects */
 	
$model->addTable("CurriculumSubjectCategory")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, false)
	->addDate("obsolete",true,false)
	->setRoot()
	->allowReadAccess()
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
 	->onInsert(function($inserted) {
 		foreach ($inserted as $row)
			try {
				PNApplication::$instance->news->post("students", "curriculum", null, "activity", "Category of subjects <i>".toHTML($row["fields_values"]["name"])."</i> created for the curricula.");
			} catch (Exception $e) { PNApplication::error($e); }
 	})
 	// TODO other news
 	->setRowDescriptionProvider(function($row) {
 		return "Subjects' category ".toHTML($row["name"]);
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
	->addReadAccessFromForeignTable("period")
	->addWriteAccess("edit_curriculum", true)
	->addInsertAccess("edit_curriculum", true)
	->addRemoveAccess("edit_curriculum", true)
 	->onInsert(function($inserted) {
 		foreach ($inserted as $row)
			try {
				$category = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectCategory")->whereValue("CurriculumSubjectCategory","id",$row["fields_values"]["category"])->field("CurriculumSubjectCategory","name")->executeSingleValue();
				$period = SQLQuery::create()->bypassSecurity()
					->select("BatchPeriod")
					->whereValue("BatchPeriod","id",$row["fields_values"]["period"])
					->field("BatchPeriod","name","period_name")
					->join("BatchPeriod", "StudentBatch", array("batch"=>"id"))
					->field("StudentBatch", "name", "batch_name")
					->field("StudentBatch", "id", "batch_id")
					->executeSingleRow()
					;
				$message = "Subject <i>".toHTML($row["fields_values"]["name"])."</i> (code <i>".toHTML($row["fields_values"]["code"])."</i>) created in category ".toHTML($category)." for period ".toHTML($period["period_name"])." of batch ".toHTML($period["batch_name"]);
				if (isset($row["fields_values"]["specialization"]) && $row["fields_values"]["specialization"] <> null) {
					$spe = SQLQuery::create()->bypassSecurity()->select("Specialization")->whereValue("Specialization", "id", $row["fields_values"]["specialization"])->field("Specialization","name")->executeSingleValue();
					$message .= " in specialization ".toHTML($spe);
				}
				PNApplication::$instance->news->post("students", "curriculum", array("batch".$period["batch_id"],"period".$row["fields_values"]["period"]), "activity", $message);
			} catch (Exception $e) { PNApplication::error($e); }
 	})
 	->onRemove(function($table,$rows,$sm){
 		foreach ($rows as $row) {
	 		$period = SQLQuery::create()->bypassSecurity()
		 		->select("BatchPeriod")
		 		->whereValue("BatchPeriod","id",$row["period"])
		 		->field("BatchPeriod","name","period_name")
		 		->join("BatchPeriod", "StudentBatch", array("batch"=>"id"))
		 		->field("StudentBatch", "name", "batch_name")
		 		->field("StudentBatch", "id", "batch_id")
		 		->executeSingleRow()
		 		;
	 		$message = "Subject <i>".toHTML($row["name"])."</i> (code <i>".toHTML($row["code"])."</i>) removed for period ".toHTML($period["period_name"])." of batch ".toHTML($period["batch_name"]);
	 		PNApplication::$instance->news->post("students", "curriculum", array("batch".$period["batch_id"],"period".$row["period"]), "activity", $message);
 		}
 	})
 	->onBeforeUpdate(function(&$keys_fields_values, $sub_model_instance){
 		foreach ($keys_fields_values as $updated) {
 			$keys = $updated[0];
 			$fields_values = $updated[1];
 			foreach ($keys as $key) {
 				$prev = SQLQuery::create()->bypassSecurity()->select("CurriculumSubject")->whereValue("CurriculumSubject","id",$key)->executeSingleRow();
 				$period = SQLQuery::create()->bypassSecurity()
	 				->select("BatchPeriod")
	 				->whereValue("BatchPeriod","id",$prev["period"])
	 				->executeSingleRow()
	 				;
 				$message = "";
 				if (isset($fields_values["code"]) && $fields_values["code"] <> $prev["code"])
 					$message .= "<li>Code changed to <i>".toHTML($fields_values["code"])."</i></li>";
 				if (isset($fields_values["name"]) && $fields_values["name"] <> $prev["name"])
 					$message .= "<li>Name changed to <i>".toHTML($fields_values["name"])."</i></li>";
 				if ((isset($fields_values["hours"]) && isset($fields_values["hours_type"])) && ($fields_values["hours"] <> $prev["hours"] || $fields_values["hours_type"] <> $prev["hours_type"]))
 					$message .= "<li>Number of hours changed from ".$prev["hours"]." ".$prev["hours_type"]." to ".$fields_values["hours"]." ".$fields_values["hours_type"]."</li>";
 				if (isset($fields_values["coefficient"]) && $fields_values["coefficient"] <> $prev["coefficient"])
 					$message .= "<li>Coefficient changed from ".$prev["coefficient"]." to ".$fields_values["coefficient"]."</li>";
 				if ($message <> "") {
 					$message = "Subject <i>".toHTML($prev["name"])."</i> (code <i>".toHTML($prev["code"])."</i>) updated:<ul>".$message."</ul>";
 					PNApplication::$instance->news->post("students", "curriculum", array("batch".$period["batch"],"period".$prev["period"]), "activity", $message);
 				}
  			}
 		}
 	})
 	->setRowDescriptionProvider(function($row) {
 		$r = SQLQuery::create()
 			->select("BatchPeriod")
 			->whereValue("BatchPeriod", "id", $row["period"])
 			->join("BatchPeriod","StudentBatch",array("batch"=>"id"))
 			->field("BatchPeriod","name","period_name")
 			->field("StudentBatch","name","batch_name")
 			->executeSingleRow();
 		return "Subject ".toHTML($row["name"])." in period ".toHTML($r["period_name"])." of batch ".toHTML($r["batch_name"]);
 	})
 	;
$model->internalGetTable("BatchPeriodSpecialization")
	->onBeforeRemove(function($table, $rows, $sm) {
		foreach ($rows as $row) {
			$subjects = SQLQuery::create()->bypassSecurity()
				->select("CurriculumSubject")
				->whereValue("CurriculumSubject","period",$row["period"])
	 			->whereValue("CurriculumSubject","specialization",$row["specialization"])
	 			->execute();
	 		if (count($subjects) == 0) continue; // ok
	 		$spe_name = SQLQuery::create()->bypassSecurity()->select("Specialization")->whereValue("Specialization","id",$row["specialization"])->field("name")->executeSingleValue();
	 		$period_name = SQLQuery::create()->bypassSecurity()->select("BatchPeriod")->whereValue("BatchPeriod","id",$row["period"])->field("name")->executeSingleValue();
	 		$s = "Specialization $spe_name cannot be removed from $period_name because the following subjects are defined to its curriculum: ";
	 		$first = true;
	 		foreach ($subjects as $subject) {
	 			if ($first) $first = false; else $s .= ", ";
	 			$s .= toHTML($subject["name"]);
	 		}
	 		return $s;
		}
		return null;
 	});
?>