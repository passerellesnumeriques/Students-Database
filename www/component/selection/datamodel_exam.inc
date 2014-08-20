<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$sm->addTable("ExamSubject")
	->addPrimaryKey("id")
	->addString("name",100,1,false,true) //cannot have two exam with the same name on the same campaign
	->addDecimal("max_score",10,2,0,null,false,false) // must be calculated
	->addColumnWriteAccess("name","manage_exam_subject",true)
	->addReadAccess("see_exam_subject",true)
	->addInsertAccess("manage_exam_subject",true)
	->addRemoveAccess("manage_exam_subject",true)
	;

$sm->addTable("ExamSubjectVersion")
	->addPrimaryKey("id")
    ->addForeignKey("exam_subject","ExamSubject",true,false,true,false)
    ->addReadAccess("see_exam_subject",true)
    ;
	//no insert / write / remove right: must pass by a functionality that checks the steps, the exam topic linked...

$sm->addTable("ExamSubjectPart")
	->addPrimaryKey("id")
	->addForeignKey("exam_subject","ExamSubject",true,false,true,false)
	->addInteger("index",32,null,null,false,false)
	->addDecimal("max_score",10,2,0,null,false,false) // must be calculated
	->addString("name",100,1,false,false)
	->addReadAccess("see_exam_subject",true)
	;
	//no insert / write / remove right: must pass by a functionality that checks the steps, the exam topic linked...

$sm->addTable("ExamSubjectQuestion")
	->addPrimaryKey("id")
	->addForeignKey("exam_subject_part","ExamSubjectPart",true,false,true)
	->addInteger("index",8,null,null,false,false) // index of the question in this part(in case the parts index is updated)
	->addDecimal("max_score",10,2,0,null,false,false) // must be editable
	->addString("type_config") // eg. "5" meaning 5 possibles choices in case type is mcq 
	->addEnum("type",array("mcq_single","mcq_multi","number","text"),true,false) 
	->addReadAccess("see_exam_subject",true)
	;
	//no insert / write / remove right: must pass by a functionality that checks the steps, the exam topic linked...
        ;
$sm->addTable("ExamSubjectAnswer")
	->addForeignKey("exam_subject_question","ExamSubjectQuestion",true,false,true)
	->addForeignKey("exam_subject_version","ExamSubjectVersion",true,false,true)
	->addString("answer",255,0,true) // eg. "{A,C}" or "127.8" or "toto titi"  depending on question type
	->setKey(array("exam_subject_question","exam_subject_version"))
	->addReadAccess("see_exam_subject",true) // TODO special right ?
	;
	//no insert / write / remove right: must pass by a functionality that checks the steps, the exam topic linked...

$sm->addTable("ExamSubjectExtract")
	->addPrimaryKey("id")
	->addString("name",100,1,false,true)
	->addReadAccess("see_exam_subject", true)
	->addColumnWriteAccess("name", "manage_exam_subject", true)
	;
$sm->addTable("ExamSubjectExtractParts")
	->addForeignKey("extract", "ExamSubjectExtract", true, false, true)
	->addForeignKey("part", "ExamSubjectPart", true, false, true)
	->setKey(array("extract","part"))
	->addReadAccess("see_exam_subject", true)
	;

$sm->addTable("ExamEligibilityRule")
	->addPrimaryKey("id")
	->addForeignKey("parent", "ExamEligibilityRule", true, false, false, true)//Can only have one parent. If null, root level
	->addDecimal("expected",3,2,null,null,false,false) // expected grade
	->addWriteAccess("manage_exam_subject",true)
	->addReadAccess("see_exam_subject",true)
	->addInsertAccess("manage_exam_subject",true)
	->addRemoveAccess("manage_exam_subject",true)
	;

$sm->addTable("ExamEligibilityRuleTopic")
	->addForeignKey("rule", "ExamEligibilityRule", true, false, true)
	->addForeignKey("subject", "ExamSubject", true, false, true, true)
	->addForeignKey("extract", "ExamSubjectExtract", true, false, true, true)
	->addDecimal("coefficient",3,2,null,null,true,false) //optional. If empty, considered as 1
	->setKey(array("rule","subject","extract"))
	->addWriteAccess("manage_exam_subject",true)
	->addReadAccess("see_exam_subject",true)
	->addInsertAccess("manage_exam_subject",true)
	->addRemoveAccess("manage_exam_subject",true)
	;


$sm->addTable("ExamCenter")
	->addPrimaryKey("id")
	->addString("name",255,0,false)//optional
	->addForeignKey("geographic_area", "GeographicArea", true, false, true, false, true)//An exam center must be linked to a geographic area, and this area is unique in this table
	->addReadAccess("see_exam_center", true)
	->addWriteAccess("manage_exam_center", true)
	->addRemoveAccess("manage_exam_center", true)
	->addInsertAccess("manage_exam_center", true)
	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			PNApplication::$instance->news->post("selection", null, array("campaign".$sub_model_instance), "activity", "New Exam Center: <i>".$fields["name"]."</i>");
		} catch (Exception $e) { PNApplication::error($e); }
	});

$sm->addTable("ExamCenterPartner")
	->addForeignKey("exam_center","ExamCenter",true,false,true,false)
	->addForeignKey("organization","Organization",true,false,true,false)
	->addBoolean("host")
	->addForeignKey("host_address","PostalAddress",false,false,true,true) //this attributes is set only when host attribute == true: can select a praticular address in case the organization has several ones
	->setKey(array("exam_center","organization"))
	->addWriteAccess("manage_exam_center",true)
	->addReadAccess("see_exam_center",true)
	->addInsertAccess("manage_exam_center",true)
	->addRemoveAccess("manage_exam_center",true);
$model->internalGetTable("Organization")
	->addReadAccessFromForeignTable("ExamCenterPartner");
$model->internalGetTable("PostalAddress")
	->addReadAccessFromForeignTable("ExamCenterPartner");

$sm->addTable("ExamCenterContactPoint")
	->addForeignKey("exam_center","ExamCenter",true,false,true,false)
	->addForeignKey("organization","Organization",true,false,true,false)
	//This two attributes are a foreignKey for ExamCenter partner table
	->addForeignKey("people","People",true,false,true,false)
	//People + orga = foreignKey for ContactPoint table
	->setKey(array("exam_center","organization","people"))
	->addWriteAccess("manage_exam_center",true)
	->addReadAccess("see_exam_center",true)
	->addInsertAccess("manage_exam_center",true)
	->addRemoveAccess("manage_exam_center",true)
	;

$sm->addTable("ExamCenterInformationSession")//An exam center can created from one or several information sessions
	->addForeignKey("exam_center", "ExamCenter", true, false, true)
	->addForeignKey("information_session", "InformationSession", true, false, false,false,true)//An information session can only be linked to one exam center
	->setKey(array("exam_center","information_session"))
	->addWriteAccess("manage_exam_center",true)
	->addReadAccess("see_exam_subject",true)
	->addInsertAccess("manage_exam_center",true)
	->addRemoveAccess("manage_exam_center",true)
	;
		
$sm->addTable("ExamSession")
	->addForeignKey("event", "CalendarEvent", true, true, false, false, true)
	->addForeignKey("exam_center", "ExamCenter", true, false, true)//An exam center can have several session
	->setPrimary("event")//The event is the primary key of a session
	->addWriteAccess("manage_exam_center",true)
	->addReadAccess("see_exam_subject",true)
	->addInsertAccess("manage_exam_center",true)
	->addRemoveAccess("manage_exam_center",true);
$model->internalGetTable("CalendarEvent")
	->addReadAccessFromForeignTable("ExamSession")
	->addRemoveAccessFromForeignTable("ExamSession")
	;

$sm->addTable("ExamCenterRoom")
	->addPrimaryKey("id")
	->addForeignKey("exam_center", "ExamCenter", true, false, true)
	->addInteger("capacity",32,0,null,false,false)//The capacity of the room cannot be null
	->addString("name",20,0,false,false)//Can be custom or not, but must be set
	->addWriteAccess("manage_exam_center",true)
	->addReadAccess("see_exam_subject",true)
	->addInsertAccess("manage_exam_center",true)
	->addRemoveAccess("manage_exam_center",true);

$sm->addTable("ExamSessionSupervisor")
	->addForeignKey("session", "ExamSession", true, false, true, false)
	->addForeignKey("staff", "Staff", true, false, true, false)
	->setKey(array("session","staff"))
	->addWriteAccess("manage_exam_center",true)
	->addReadAccess("see_exam_center",true)
	->addInsertAccess("manage_exam_center",true)
	->addRemoveAccess("manage_exam_center",true);

$sm->addTable("ExamSessionSupervisorCustom")
	->addPrimaryKey("id")
	->addForeignKey("session", "ExamSession", true, false, true, false)
	->addString("name",100,1,false,true)
	->addWriteAccess("manage_exam_center",true)
	->addReadAccess("see_exam_center",true)
	->addInsertAccess("manage_exam_center",true)
	->addRemoveAccess("manage_exam_center",true);
?>