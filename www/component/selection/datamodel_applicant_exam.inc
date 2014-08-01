<?php
/* @var $model DataModel */
/* @var $sm SubDataModel */

$sm->addTable("ApplicantExamSubject")  
      ->addForeignKey("applicant","Applicant",true,false,true)
      ->addForeignKey("exam_subject","ExamSubject",true,false,true,false) // for faster computing
      ->addForeignKey("exam_subject_version","ExamSubjectVersion",true,false,true,false)
      ->setKey(array("applicant","exam_subject")) // also create index automatically
      ->addDecimal("score",10,2,null,null,false,false)
      ->addReadAccess("see_applicant_info", true)
      ;

$sm->addTable("ApplicantExamSubjectPart")
      ->addForeignKey("applicant","Applicant",true,false,true)
      ->addForeignKey("exam_subject_part","ExamSubjectPart",true,false,true)
      ->setKey(array("applicant","exam_subject_part"))
      ->addDecimal("score",10,2,null,null,false,false)
      ->addReadAccess("see_applicant_info", true)
      ;


$sm->addTable("ApplicantExamAnswer")
      ->addForeignKey("applicant","Applicant",true,false,true)
      ->addForeignKey("exam_subject_question","ExamSubjectQuestion",true,false,true)
       ->setKey(array("applicant","exam_subject_question"))
      ->addString("answer") // the answer given by the applicant
      ->addDecimal("score",10,2,null,null,false,false)
      ->addReadAccess("see_applicant_info", true)
        ;

$sm->addTable("ApplicantExamExtract")
      ->addForeignKey("applicant","Applicant",true,false,true)
      ->addForeignKey("exam_extract","ExamSubjectExtract",true,false,true,false)
      ->setKey(array("applicant","exam_extract"))
      ->addDecimal("score",10,2,null,null,false,false)
      ->addReadAccess("see_applicant_info", true)
      ;

?>