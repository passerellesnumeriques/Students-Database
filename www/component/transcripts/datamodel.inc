<?php 
/* @var $model DataModel */
$model->addTable("CurriculumSubjectGrading")
	->addForeignKey("subject", "CurriculumSubject", true, false, false, false, true)
	->addInteger("weight",8,0,200,false,false)
	->addInteger("max_grade",8,0,100,false,false)
	->addInteger("passing_grade",8,0,100,false,false)
	->addBoolean("only_final_grade", false)
	->setPrimary("subject")
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_students_grades", true)
	->addInsertAccess("edit_students_grades", true)
	->addRemoveAccess("edit_students_grades", true)
	->addRemoveAccess("edit_curriculum", true)
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
	;

$model->addTable("CurriculumSubjectEvaluation")
	->addPrimaryKey("id")
	->addForeignKey("type", "CurriculumSubjectEvaluationType", true, false, true, false, false)
	->addString("name", 100, 1, false, false)
	->addInteger("weight",8,0,200,false,false)
	->addInteger("max_grade",8,0,100,false,false)
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_students_grades", true)
	->addInsertAccess("edit_students_grades", true)
	->addRemoveAccess("edit_students_grades", true)
	->addRemoveAccess("edit_curriculum", true)
	;

$model->addTable("StudentSubjectEvaluationTypeGrade")
	->addForeignKey("people", "People", true, false, true, false, false)
	->addForeignKey("type", "CurriculumSubjectEvaluationType", true, false, true, false, false)
	->addInteger("grade",8,0,100,true,false)
	->setKey(array("people","type"))
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_students_grades", true)
	->addInsertAccess("edit_students_grades", true)
	->addRemoveAccess("edit_students_grades", true)
	->addRemoveAccess("edit_curriculum", true)
	;

$model->addTable("StudentSubjectEvaluationGrade")
	->addForeignKey("people", "People", true, false, true, false, false)
	->addForeignKey("evaluation", "CurriculumSubjectEvaluation", true, false, true, false, false)
	->addInteger("grade",8,0,100,true,false)
	->setKey(array("people","evaluation"))
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_students_grades", true)
	->addInsertAccess("edit_students_grades", true)
	->addRemoveAccess("edit_students_grades", true)
	->addRemoveAccess("edit_curriculum", true)
	;

$model->addTable("StudentSubjectGrade")
	->addForeignKey("people", "People", true, false, true, false, false)
	->addForeignKey("subject", "CurriculumSubject", true, false, true, false, false)
	->addInteger("grade",8,0,100,true,false)
	->setKey(array("people","subject"))
	->addReadAccess("consult_students_grades", true)
	->addWriteAccess("edit_students_grades", true)
	->addInsertAccess("edit_students_grades", true)
	->addRemoveAccess("edit_students_grades", true)
	->addRemoveAccess("edit_curriculum", true)
	;

?>