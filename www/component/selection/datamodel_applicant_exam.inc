<?php
// (June 04 2014) Adding new table ApplicantExam
$sm->addTable("ApplicantExamSubject")  
      ->addForeignKey("applicant","Applicant",true,false,true)
      ->addForeignKey("exam_subject","ExamSubject",true,false,true,false) // for faster computing
      ->addForeignKey("exam_subject_version","ExamSubjectVersion",true,false,true,false)
      ->setKey(array("applicant","exam_subject")) // also create index automatically
      ->addDecimal("score",10,2,0,null,false,false)
       //TODO : Access Rights ?
      ;
// (June 04 2014) Adding new table ApplicantExamPart
$sm->addTable("ApplicantExamSubjectPart")
      ->addForeignKey("applicant","Applicant",true,false,true)
      ->addForeignKey("exam_subject_part","ExamSubjectPart",true,false,true)
      ->setKey(array("applicant","exam_subject_part"))
      ->addDecimal("score",10,2,0,null,false,false)
      //TODO : Access Rights ?
      ;

// (June 04 2014) Adding new table ApplicantExamAnswer
$sm->addTable("ApplicantExamAnswer")
      ->addForeignKey("applicant","Applicant",true,false,true)
      ->addForeignKey("exam_subject_question","ExamSubjectQuestion",true,false,true)
       ->setKey(array("applicant","exam_subject_question"))
      ->addString("answer") // the answer given by the applicant
      ->addDecimal("score",10,2,0,null,false,false)
       //TODO : Access Rights ?
        ;

// (June 04 2014) Adding new table ApplicantExamTopic
$sm->addTable("ApplicantExamTopic")
      ->addForeignKey("applicant","Applicant",true,false,true)
      ->addForeignKey("exam_topic_for_eligibility_rule","ExamTopicForEligibilityRule",true,false,true,false)
      ->setKey(array("applicant","exam_topic_for_eligibility_rule"))
      ->addDecimal("score",10,2,0,null,false,false)
      //TODO : Access Rights ?
      ;

?>