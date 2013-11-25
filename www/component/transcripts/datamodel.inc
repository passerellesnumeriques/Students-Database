<?php 
/* @var $model DataModel */
$model->addTable("CurriculumSubjectGrading")
	->addForeignKey("subject", "CurriculumSubject", true, false, false, false, true)
	->addInteger("weight",8,0,200,true,false)
	->addInteger("passing",8,0,100,true,false)
	->setPrimary("subject")
	->addReadAccess("consult_students_grades", true)
	;

$model->addTable("CurriculumSubjectEvaluationType")
	->addPrimaryKey("id")
	->addForeignKey("subject", "CurriculumSubject", true, false, false, false, true)
	->addString("name", 100, 1, false, false)
	->addInteger("weight",8,0,200,true,false)
	->addReadAccess("consult_students_grades", true)
	;

$model->addTable("CurriculumSubjectEvaluation")
	->addPrimaryKey("id")
	->addForeignKey("type", "CurriculumSubjectEvaluationType", true, false, false, false, true)
	->addString("name", 100, 1, false, false)
	->addInteger("weight",8,0,200,true,false)
	->addReadAccess("consult_students_grades", true)
	;

$model->addTable("StudentSubjectEvaluationTypeGrade")
	->addForeignKey("people", "People", true, false, true, false, false)
	->addForeignKey("type", "CurriculumSubjectEvaluationType", true, false, true, false, false)
	->addInteger("grade",8,0,100,true,false)
	->setKey(array("people","subject"))
	->addReadAccess("consult_students_grades", true)
	;

$model->addTable("StudentSubjectEvaluationGrade")
	->addForeignKey("people", "People", true, false, true, false, false)
	->addForeignKey("evaluation", "CurriculumSubjectEvaluation", true, false, true, false, false)
	->addInteger("grade",8,0,100,true,false)
	->setKey(array("people","subject"))
	->addReadAccess("consult_students_grades", true)
	;

$model->addTable("StudentSubjectGrade")
	->addForeignKey("people", "People", true, false, true, false, false)
	->addForeignKey("subject", "CurriculumSubject", true, false, true, false, false)
	->addInteger("grade",8,0,100,true,false)
	->setKey(array("people","subject"))
	->addReadAccess("consult_students_grades", true)
	;

?>