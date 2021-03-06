<?php 
/* @var $model DataModel */
$model->addTable("CurriculumSubjectGrading")
	->addForeignKey("subject", "CurriculumSubject", true, false, false, false, true)
	->addDecimal("max_grade",3,2,0,100,false,false)
	->addDecimal("passing_grade",3,2,0,100,false,false)
	->addBoolean("only_final_grade", false)
	->setPrimary("subject")
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_students_grades", true)
	->addInsertAccess("edit_students_grades", true)
	->addRemoveAccess("edit_students_grades", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;

$model->addTable("CurriculumSubjectEvaluationType")
	->addPrimaryKey("id")
	->addForeignKey("subject", "CurriculumSubject", true, false, true, false, false)
	->addString("name", 100, 1, false, false)
	->addInteger("weight",8,0,200,false,false)
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_students_grades", true)
	->addInsertAccess("edit_students_grades", true)
	->addRemoveAccess("edit_students_grades", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) {
		$q = PNApplication::$instance->curriculum->getSubjectQuery($row["subject"]);
		PNApplication::$instance->curriculum->joinBatchPeriod($q, "CurriculumSubject", "period");
		PNApplication::$instance->curriculum->joinBatch($q, "BatchPeriod", "batch");
		$q->field("CurriculumSubject","name","subject_name");
		$q->field("BatchPeriod","name","period_name");
		$q->field("StudentBatch","name","batch_name");
		$r = $q->executeSingleRow();
		return "Evaluations and grades for subject ".toHTML($r["subject_name"])." in ".toHTML($r["period_name"])." of batch ".toHTML($r["batch_name"]);
	})
	;

$model->addTable("CurriculumSubjectEvaluation")
	->addPrimaryKey("id")
	->addForeignKey("type", "CurriculumSubjectEvaluationType", true, false, true, false, false)
	->addString("name", 100, 1, false, false)
	->addInteger("weight",8,0,200,false,false)
	->addDecimal("max_grade",5,2,0,null,false,false)
	->addReadAccess("consult_students_grades", true)
	->addReadAccessFromForeignTable("CurriculumSubjectEvaluationType")
	->addWriteAccess("edit_students_grades", true)
	->addInsertAccess("edit_students_grades", true)
	->addRemoveAccess("edit_students_grades", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;

$model->addTable("StudentSubjectEvaluationTypeGrade")
	->addForeignKey("people", "People", true, false, true, false, false)
	->addForeignKey("type", "CurriculumSubjectEvaluationType", true, false, true, false, false)
	->addDecimal("grade",3,2,0,100,true,false)
	->setKey(array("people","type"))
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_students_grades", true)
	->addInsertAccess("edit_students_grades", true)
	->addRemoveAccess("edit_students_grades", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;

$model->addTable("StudentSubjectEvaluationGrade")
	->addForeignKey("people", "People", true, false, true, false, false)
	->addForeignKey("evaluation", "CurriculumSubjectEvaluation", true, false, true, false, false)
	->addDecimal("grade",3,2,0,100,true,false)
	->setKey(array("people","evaluation"))
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_students_grades", true)
	->addInsertAccess("edit_students_grades", true)
	->addRemoveAccess("edit_students_grades", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;

$model->addTable("StudentSubjectGrade")
	->addForeignKey("people", "People", true, false, true, false, false)
	->addForeignKey("subject", "CurriculumSubject", true, false, true, false, false)
	->addDecimal("grade",3,2,0,100,true,false)
	->addString("comment", 4000, 0, true, false)
	->setKey(array("people","subject"))
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_students_grades", true)
	->addInsertAccess("edit_students_grades", true)
	->addRemoveAccess("edit_students_grades", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) {
		$q = PNApplication::$instance->curriculum->getSubjectQuery($row["subject"]);
		PNApplication::$instance->curriculum->joinBatchPeriod($q, "CurriculumSubject", "period");
		PNApplication::$instance->curriculum->joinBatch($q, "BatchPeriod", "batch");
		$q->field("CurriculumSubject","name","subject_name");
		$q->field("BatchPeriod","name","period_name");
		$q->field("StudentBatch","name","batch_name");
		$r = $q->executeSingleRow();
		$p = PNApplication::$instance->people->getPeople($row["people"], true);
		return "Grade of student ".toHTML($p["first_name"]." ".$p["last_name"])." for subject ".toHTML($r["subject_name"])." in ".toHTML($r["period_name"])." of batch ".toHTML($r["batch_name"]);
	})
	;

$model->addTable("StudentTranscriptGeneralComment")
	->addForeignKey("people", "People", true, false, true, false, false)
	->addForeignKey("period", "BatchPeriod", true, false, true, false, false)
	->addString("comment", 4000, 0, false, false)
	->setKey(array("people","period"))
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_students_grades", true)
	->addInsertAccess("edit_students_grades", true)
	->addRemoveAccess("edit_students_grades", true)
	->addRemoveAccess("edit_curriculum", true)
	;

$model->addTable("TranscriptConfig")
	->addForeignKey("period", "BatchPeriod", true, false, true, false, false)
	->addForeignKey("specialization", "Specialization", true, false, true, true, false)
	->setKey(array("period","specialization"))
	->addString("grading_system",50,1,true,false)
	->addBoolean("nb_hours", true)
	->addEnum("hours_type", array("Per week","Per period"), true, false)
	->addBoolean("coefficient", true)
	->addBoolean("batch_average", true)
	->addBoolean("batch_lowest", true)
	->addBoolean("batch_highest", true)
	->addBoolean("batch_rank", true)
	->addBoolean("class_average", true)
	->addBoolean("class_lowest", true)
	->addBoolean("class_highest", true)
	->addBoolean("class_rank", true)
	->addBoolean("comment", true)
	->addBoolean("general_appreciation", true)
	->addBoolean("subject_code", true)
	->addBoolean("grades_details", true)
	->addString("subject_category_background", 7,7, true, false)
	->addString("subject_category_color", 7,7, true, false)
	->addEnum("subject_category_weight", array("normal","bold"), true, false)
	->addInteger("subject_category_size", 8, 8,36, true, false)
	->addString("columns_titles_background", 7,7, true, false)
	->addString("columns_titles_color", 7,7, true, false)
	->addEnum("columns_titles_weight", array("normal","bold"), true, false)
	->addInteger("columns_titles_size", 8, 8,36, true, false)
	->addString("general_comment_title_background", 7,7, true, false)
	->addString("general_comment_title_color", 7,7, true, false)
	->addEnum("general_comment_title_weight", array("normal","bold"), true, false)
	->addInteger("general_comment_title_size", 8, 8,36, true, false)
	->addInteger("general_comment_size", 8, 8,36, true, false)
	->addString("total_background", 7,7, true, false)
	->addString("total_color", 7,7, true, false)
	->addInteger("total_size", 8, 8,36, true, false)
	->addString("location",100,1,true,false)
	->addString("signatory_name",100,1,true,false)
	->addString("signatory_title",100,1,true,false)
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_transcripts_design", true)
	->addInsertAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_transcripts_design", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;

$model->addTable("TranscriptSubjects")
	->addForeignKey("period", "BatchPeriod", true, false, true, false, false)
	->addForeignKey("specialization", "Specialization", true, false, true, true, false)
	->addForeignKey("subject","CurriculumSubject", true, false, false, false)
	->setKey(array("period","specialization","subject"))
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_transcripts_design", true)
	->addInsertAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_transcripts_design", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;

$model->addTable("PublishedTranscript")
	->addPrimaryKey("id")
	->addForeignKey("period", "BatchPeriod", true, false, true, false, false)
	->addForeignKey("specialization", "Specialization", true, false, true, true, false)
	->addString("name", 100, 1, false, false)
	->addString("grading_system",50,1,true,false)
	->addBoolean("nb_hours", true)
	->addEnum("hours_type", array("Per week","Per period"), true, false)
	->addBoolean("coefficient", true)
	->addBoolean("batch_average", true)
	->addBoolean("batch_lowest", true)
	->addBoolean("batch_highest", true)
	->addBoolean("batch_rank", true)
	->addBoolean("class_average", true)
	->addBoolean("class_lowest", true)
	->addBoolean("class_highest", true)
	->addBoolean("class_rank", true)
	->addBoolean("comment", true)
	->addBoolean("general_appreciation", true)
	->addBoolean("subject_code", true)
	->addBoolean("grades_details", true)
	->addString("subject_category_background", 7,7, true, false)
	->addString("subject_category_color", 7,7, true, false)
	->addEnum("subject_category_weight", array("normal","bold"), true, false)
	->addInteger("subject_category_size", 8, 8,36, true, false)
	->addString("columns_titles_background", 7,7, true, false)
	->addString("columns_titles_color", 7,7, true, false)
	->addEnum("columns_titles_weight", array("normal","bold"), true, false)
	->addInteger("columns_titles_size", 8, 8,36, true, false)
	->addString("general_comment_title_background", 7,7, true, false)
	->addString("general_comment_title_color", 7,7, true, false)
	->addEnum("general_comment_title_weight", array("normal","bold"), true, false)
	->addInteger("general_comment_title_size", 8, 8,36, true, false)
	->addInteger("general_comment_size", 8, 8,36, true, false)
	->addString("total_background", 7,7, true, false)
	->addString("total_color", 7,7, true, false)
	->addInteger("total_size", 8, 8,36, true, false)
	->addString("location",100,1,true,false)
	->addString("signatory_name",100,1,true,false)
	->addString("signatory_title",100,1,true,false)
	->allowReadAccess()
	->addWriteAccess("edit_transcripts_design", true)
	->addInsertAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_transcripts_design", true)
	->onInsert(function($inserted) {
		foreach ($inserted as $row)
			try {
				$period = PNApplication::$instance->curriculum->getBatchPeriod($row["fields_values"]["period"]);
				$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
				PNApplication::$instance->news->post("students", "transcripts", array("batch".$batch["id"],"period".$period["id"]), "activity", "Transcript published for Batch ".toHTML($batch["name"]).", period ".toHTML($period["name"]));
			} catch (Exception $e) { PNApplication::error($e); }
	})
	->setRowDescriptionProvider(function($row) { return ""; })
	;

$model->addTable("PublishedTranscriptStudentSubjectGrade")
	->addForeignKey("id", "PublishedTranscript", true, false, true, false, false)
	->addForeignKey("subject", "CurriculumSubject", true, false, true, false, false)
	->addForeignKey("people", "People", true, false, true, false, false)
	->addDecimal("grade",3,2,0,100,true,false)
	->addString("comment", 4000, 0, true, false)
	->setKey(array("id","subject","people"))
	->addReadAccess("consult_students_grades", true)
	->addReadFilter(function(&$q,$table_alias) {
		$q->whereValue($table_alias,"people",PNApplication::$instance->user_management->people_id);
	})
	->addWriteAccess("edit_transcripts_design", true)
	->addInsertAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;

$model->addTable("PublishedTranscriptStudentGeneralComment")
	->addForeignKey("id", "PublishedTranscript", true, false, true, false, false)
	->addForeignKey("people", "People", true, false, true, false, false)
	->addString("comment", 4000, 0, false, false)
	->setKey(array("id","people"))
	->addReadAccess("consult_students_grades", true)
	->addReadFilter(function(&$q,$table_alias) {
		$q->whereValue($table_alias,"people",PNApplication::$instance->user_management->people_id);
	})
	->addWriteAccess("edit_transcripts_design", true)
	->addInsertAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_transcripts_design", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;

$model->addTable("PublishedTranscriptSubject")
	->addForeignKey("transcript","PublishedTranscript", true, false, true, false, false)
	->addForeignKey("subject", "CurriculumSubject", true, false, true, false, false)
	->setKey(array("transcript","subject"))
	->addDecimal("max_grade",3,2,0,100,false,false)
	->addDecimal("passing_grade",3,2,0,100,false,false)
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_transcripts_design", true)
	->addInsertAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;
	
$model->addTable("PublishedTranscriptEvaluationType")
	->addPrimaryKey("id")
	->addForeignKey("transcript","PublishedTranscript", true, false, true, false, false)
	->addForeignKey("subject", "CurriculumSubject", true, false, true, false, false)
	->addString("name", 100, 1, false, false)
	->addInteger("weight",8,0,200,false,false)
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_transcripts_design", true)
	->addInsertAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;
	
$model->addTable("PublishedTranscriptEvaluation")
	->addPrimaryKey("id")
	->addForeignKey("type", "PublishedTranscriptEvaluationType", true, false, true, false, false)
	->addString("name", 100, 1, false, false)
	->addInteger("weight",8,0,200,false,false)
	->addDecimal("max_grade",5,2,0,null,false,false)
	->addReadAccess("consult_students_grades", true)
	->addReadAccessFromForeignTable("PublishedTranscriptEvaluationType")
	->addWriteAccess("edit_transcripts_design", true)
	->addInsertAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;
	
$model->addTable("PublishedTranscriptStudentEvaluationTypeGrade")
	->addForeignKey("people", "People", true, false, true, false, false)
	->addForeignKey("type", "PublishedTranscriptEvaluationType", true, false, true, false, false)
	->addDecimal("grade",3,2,0,100,true,false)
	->setKey(array("people","type"))
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_transcripts_design", true)
	->addInsertAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;
	
$model->addTable("PublishedTranscriptStudentEvaluationGrade")
	->addForeignKey("people", "People", true, false, true, false, false)
	->addForeignKey("evaluation", "PublishedTranscriptEvaluation", true, false, true, false, false)
	->addDecimal("grade",3,2,0,100,true,false)
	->setKey(array("people","evaluation"))
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_transcripts_design", true)
	->addInsertAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_transcripts_design", true)
	->addRemoveAccess("edit_curriculum", true)
	->setRowDescriptionProvider(function($row) { return ""; })
	;
	
$model->internalGetTable("BatchPeriodSpecialization")
	->onRemove(function($table, $rows, $sm) {
		$where = "";
		foreach ($rows as $row) {
			if ($where <> "") $where .= " OR ";
			$where .= "(`period`=".$row["period"]." AND `specialization`=".$row["specialization"].")";
		}
		$published = SQLQuery::create()->bypassSecurity()->select("PublishedTranscript")->where($where)->execute();
		if (count($published) > 0)
			SQLQuery::create()->bypassSecurity()->removeRows("PublishedTranscript", $published);
		$config = SQLQuery::create()->bypassSecurity()->select("TranscriptConfig")->where($where)->execute();
		if (count($config) > 0)
			SQLQuery::create()->bypassSecurity()->removeRows("TranscriptConfig", $config);
	});
?>