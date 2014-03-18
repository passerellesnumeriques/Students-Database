<?php
/* @var $model DataModel */
	$model->addTable("SelectionCampaign")
		->addPrimaryKey("id")
		->addString("name")
		->addForeignKey("calendar","Calendar",false,false,false,false)//Not automatically removed, must use the calendar functionalities
		->addReadAccess("can_access_selection_data",true)
		->addWriteAccess("manage_selection_campaign",true)
		->addInsertAccess("manage_selection_campaign",true)
		->addRemoveAccess("manage_selection_campaign",true)
	 	->onInsert(function($fields,$id,$sub_model_instance) {
			try {
				PNApplication::$instance->news->post("selection", null, array("campaign".$id), "Creation of campaign <i>".$fields["name"]."</i>");
			} catch (Exception $e) { PNApplication::error($e); }
	 	})
		;
		
	$sm = $model->addSubModel("SelectionCampaign");
	
	$model->addDataCategoryLink("Information Session", "/dynamic/selection/page/IS/profile?id=%InformationSession.id%", "/static/selection/IS/IS_16.png");
	
	$sm->addTable("Steps")
		->addString("name",255,1,false,true)
		->addBoolean("value",false)
		->setPrimary("name")
		->setRoot()
		->addReadAccess("can_access_selection_data",true)
		->addWriteAccess("can_update_step",true)
		->addRemoveAccess("manage_selection_campaign",true)
		->addInsertAccess("manage_selection_campaign",true);

	$sm->addTable("InformationSession")
		->addPrimaryKey("id")
		->addForeignKey("date","CalendarEvent",false,true,true,true)
		->addForeignKey("geographic_area", "GeographicArea", false, false, true)
		->addInteger("number_boys_expected",10)
		->addInteger("number_girls_expected",10)
		->addInteger("number_boys_real",10)
		->addInteger("number_girls_real",10)
		->addString("name")
		->addWriteAccess("edit_information_session",true)
		->addReadAccess("see_information_session_details",true)
		->addInsertAccess("manage_information_session",true)
		->addRemoveAccess("manage_information_session",true)
		->setDisplayHandler(null,
				new datamodel\MultipleDisplayHandler("Information Session","Information Session", array(
						new datamodel\FieldsDisplayHandler("Information Session","Information Session",
								array(
										"name" => "Name",
								),
								array(
										"name",
								),"selection"
						),
						new CalendarEventDateDataDisplay(null, "Date", "date")
				),array("date"))
		)
		->onInsert(function($fields,$id,$sub_model_instance) {
			try {
				PNApplication::$instance->news->post("selection", null, array("campaign".$sub_model_instance), "New Information Session: <i>".$fields["name"]."</i>");
			} catch (Exception $e) { PNApplication::error($e); }
		})
		;
		
	$sm->addTable("InformationSessionPartner")
		->addForeignKey("information_session","InformationSession",true,false,true,false)
		->addForeignKey("organization","Organization",true,false,true,false)
		->addBoolean("host") //this attributes means that this partner is the one where the IS takes place
		->addForeignKey("host_address","PostalAddress",false,false,true,true) //this attributes is set only when host attribute == true: can select a praticular address in case the organization has several ones
		->setKey(array("information_session","organization"))
		->addWriteAccess("edit_information_session",true)
		->addReadAccess("see_information_session_details",true)
		->addInsertAccess("manage_information_session",true)
		->addRemoveAccess("manage_information_session",true);
	
	$sm->addTable("InformationSessionContactPoint")
		->addForeignKey("information_session","InformationSession",true,false,true,false)
		->addForeignKey("organization","Organization",true,false,true,false)
		//This two attributes are a foreignKey for IS partner table
		->addForeignKey("people","People",true,false,true,false)
		//People + orga = foreignKey for ContactPoint table
		->setKey(array("information_session","organization","people"))
		->addWriteAccess("edit_information_session",true)
		->addReadAccess("see_information_session_details",true)
		->addInsertAccess("manage_information_session",true)
		->addRemoveAccess("manage_information_session",true)
		->setDisplayHandler("people", new datamodel\StopDataDisplayHandler("Selection", "Selection"))
		;
		
	$sm->addTable("SelectionCampaignConfig")
		->addString("name",255,1,false,true)//the name must be unique (key...)
		->addString("value",255,1,false,false)
		->setRoot()
		->addReadAccess("can_access_selection_data",true)
		->addInsertAccess("manage_selection_campaign",true)
		->addRemoveAccess("manage_selection_campaign",true)
		->addWriteAccess("manage_selection_campaign",true)
		->setPrimary("name");
		
	$sm->addTable("ExamSubject")
		->addPrimaryKey("id")
		->addString("name",255,1,false,false) //cannot have two exam with the same name on the same campaign
		->addDecimal("max_score",10,2,0,null,false,false) // must be calculated
		->addWriteAccess("manage_exam_subject",true)
		->addReadAccess("see_exam_subject",true)
		->addInsertAccess("manage_exam_subject",true)
		->addRemoveAccess("manage_exam_subject",true);
		
	$sm->addTable("ExamSubjectPart")
		->addPrimaryKey("id")
		->addForeignKey("exam_subject","ExamSubject",true,false,true,false)
		->addInteger("index",32,null,null,false,false)
		->addDecimal("max_score",10,2,0,null,false,false) // must be calculated
		->addString("name",255,0,true)
		->addWriteAccess("manage_exam_subject",true)
		->addReadAccess("see_exam_subject",true)
		->addInsertAccess("manage_exam_subject",true)
		->addRemoveAccess("manage_exam_subject",true);
		
	$sm->addTable("ExamSubjectQuestion")
		->addPrimaryKey("id")
		->addForeignKey("exam_subject_part","ExamSubjectPart",true,false,true)
		->addInteger("index",32,null,null,false,false) // index of the question in this part(in case the parts index is updated)
		->addDecimal("max_score",10,2,0,null,false,false) // must be editable
		->addString("correct_answer") //optional
		->addString("choices") //optional
		->addReadAccess("see_exam_subject",true)
		->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Exam Subject Question", "Exam Subject Question",
			array(
				"max_score"=>"Max Score",
				"correct_answer"=>"Correct Answer",
				"choices"=>"Choices",
			)
		))
		; 
		//no insert / write / remove right: must pass by a functionality that checks the steps, the exam topic linked...
		
	$sm->addTable("ExamTopicForEligibilityRule")
		/**
		 * As exam topics and exam subject are synchronized by some functionalities,
		 * the rights must be exactly the same!
		 */
		->addPrimaryKey("id")
		->addString("name",255,0,false)
		->addDecimal("max_score",10,2,0,null,false,false) //calculated
		->addInteger("number_questions",32,null,null,false,false)
		->addWriteAccess("manage_exam_subject",true)
		->addReadAccess("see_exam_subject",true)
		->addInsertAccess("manage_exam_subject",true)
		->addRemoveAccess("manage_exam_subject",true);
	
	$sm->addTable("ExamTopicFullSubject")
		/** Table to match an exam topic with exam(s) subject(s)
		 * if a topic is set in this table, it will be considered as
		 * "full subject" for all the matching exams subjects
		 */
		->addForeignKey("exam_subject","ExamSubject",true,false,false,false,true)//An exam subject can be set as full subject for only one topic, because otherwize it means the subject parts would appear in several topics
		->addForeignKey("exam_topic_for_eligibility_rule","ExamTopicForEligibilityRule",true,false,true,false)
		->setKey(array("exam_subject","exam_topic_for_eligibility_rule"))
		->addWriteAccess("manage_exam_subject",true)
		->addReadAccess("see_exam_subject",true)
		->addInsertAccess("manage_exam_subject",true)
		->addRemoveAccess("manage_exam_subject",true);
	
	$sm->addTable("ExamPartTopic")
		->addForeignKey("exam_topic_for_eligibility_rule","ExamTopicForEligibilityRule",true,false,true,false)
		->addForeignKey("exam_subject_part","ExamSubjectPart",true,false,false,false,true)
		/** cannot be multiple because cannot appear in several topics (otherwize the rules are biased...)
		 */
		->setKey(array("exam_topic_for_eligibility_rule","exam_subject_part"))
		->addWriteAccess("manage_exam_subject",true)
		->addReadAccess("see_exam_subject",true)
		->addInsertAccess("manage_exam_subject",true)
		->addRemoveAccess("manage_exam_subject",true);
		
// 	$sm->addTable("ExamQuestionResult")
// 		->addForeignKey("applicant","Applicant",true,false,true,false)
// 		->addForeignKey("exam_subject_question","ExamSubjectQuestion",true,false,true,false)
// 		->addString("answer")//optional
// 		->addDecimal("score",10,2,0,null,false,false)
// 		->setKey(array("applicant","exam_subject_question"));
// 		//TODO finish + update steps.inc
		
		//TODO Exam_part_result, exam_subject_result (reduce the calculation time for statistics...)
		
	$sm->addTable("EligibilityRule")
		->addPrimaryKey("id")
		->addForeignKey("parent", "EligibilityRule", true, false, false, true)//Can only have one parent. If null, root level 
		->addWriteAccess("manage_exam_subject",true)
		->addReadAccess("see_exam_subject",true)
		->addInsertAccess("manage_exam_subject",true)
		->addRemoveAccess("manage_exam_subject",true);
	//optional: addString name?
	
	$sm->addTable("EligibilityRuleExamTopic")
		->addForeignKey("eligibility_rule", "EligibilityRule", true, false, true)
		->addForeignKey("exam_topic_for_eligibility_rule", "ExamTopicForEligibilityRule", true, false, true)
		->addDecimal("expected",10,2,null,null,false,false) //the expected grade for this topic
		->addDecimal("coefficient") //optional. If empty, considered as 1
		->setKey(array("eligibility_rule","exam_topic_for_eligibility_rule"))
		->addWriteAccess("manage_exam_subject",true)
		->addReadAccess("see_exam_subject",true)
		->addInsertAccess("manage_exam_subject",true)
		->addRemoveAccess("manage_exam_subject",true);
		
		
	$sm->addTable("ExamCenter")
		->addPrimaryKey("id")
		->addString("name",255,0,false)//optional
		->addForeignKey("geographic_area", "GeographicArea", true, false, true, false, true)//An exam center must be linked to a geographic area, and this area is unique in this table
		->addReadAccess("see_exam_center_detail", true)
		->addWriteAccess("manage_exam_center", true)
		->addRemoveAccess("manage_exam_center", true)
		->addInsertAccess("manage_exam_center", true)
		->setDisplayHandler(null,new datamodel\FieldsDisplayHandler("Exam Center","Exam Center",
								array(
										"name" => "Name",
								),
								array(
										"name",
								),"selection"
						))
		->onInsert(function($fields,$id,$sub_model_instance) {
			try {
				PNApplication::$instance->news->post("selection", null, array("campaign".$sub_model_instance), "New Exam Center: <i>".$fields["name"]."</i>");
			} catch (Exception $e) { PNApplication::error($e); }
		})
		;
	
	$sm->addTable("ExamCenterPartner")
		->addForeignKey("exam_center","ExamCenter",true,false,true,false)
		->addForeignKey("organization","Organization",true,false,true,false)
		->addBoolean("host")
		->addForeignKey("host_address","PostalAddress",false,false,true,true) //this attributes is set only when host attribute == true: can select a praticular address in case the organization has several ones
		->setKey(array("exam_center","organization"))
		->addWriteAccess("manage_exam_center",true)
		->addReadAccess("see_exam_center_detail",true)
		->addInsertAccess("manage_exam_center",true)
		->addRemoveAccess("manage_exam_center",true);
	
	$sm->addTable("ExamCenterContactPoint")
		->addForeignKey("exam_center","ExamCenter",true,false,true,false)
		->addForeignKey("organization","Organization",true,false,true,false)
		//This two attributes are a foreignKey for ExamCenter partner table
		->addForeignKey("people","People",true,false,true,false)
		//People + orga = foreignKey for ContactPoint table
		->setKey(array("exam_center","organization","people"))
		->addWriteAccess("manage_exam_center",true)
		->addReadAccess("see_exam_center_detail",true)
		->addInsertAccess("manage_exam_center",true)
		->addRemoveAccess("manage_exam_center",true)
		->setDisplayHandler("people", new datamodel\StopDataDisplayHandler("Selection", "Selection"))
		;
	
	/**
	 * An exam center can be created from one or several information sessions
	 * Since applicants can be linked to any information session, use the data from this table to assign those students to the exam center
	 */
	$sm->addTable("ExamCenterInformationSession")
		->addForeignKey("exam_center", "ExamCenter", true, false, true)
		->addForeignKey("information_session", "InformationSession", true, false, false,false,true)//An information session can only be linked to one exam center
		->setKey(array("exam_center","information_session"))
		->addWriteAccess("manage_exam_center",true)
		->addReadAccess("see_exam_center_detail",true)
		->addInsertAccess("manage_exam_center",true)
		->addRemoveAccess("manage_exam_center",true);
	
	$sm->addTable("ExamSession")
		->addForeignKey("event", "CalendarEvent", true, true, false, false, true)
		->addForeignKey("exam_center", "ExamCenter", true, false, true)//An exam center can have several session
		->setPrimary("event")//The event is the primary key of a session
		->addWriteAccess("manage_exam_center",true)
		->addReadAccess("see_exam_center_detail",true)
		->addInsertAccess("manage_exam_center",true)
		->addRemoveAccess("manage_exam_center",true);
		
	$sm->addTable("ExamCenterRoom")
		->addPrimaryKey("id")
		->addForeignKey("exam_center", "ExamCenter", true, false, true)
		->addInteger("capacity",32,0,null,false,false)//The capacity of the room cannot be null
		->addString("name",255,0,false,false)//Can be custom or not, but must be set
		->addWriteAccess("manage_exam_center",true)
		->addReadAccess("see_exam_center_detail",true)
		->addInsertAccess("manage_exam_center",true)
		->addRemoveAccess("manage_exam_center",true);
		
	$sm->addTable("Applicant")
		->addForeignKey("people", "People", true, false, false, false, true)
		->addInteger("applicant_id",32,1,null,false,true)//applicant_id must be a number
		->addString("exclusion_reason", 100, 1, true)
		->addEnum("automatic_exclusion_step", array("exam","eligibility_rule","interview","social_investigation"))
		->addString("automatic_exclusion_reason",255,0,true,false)//This field must be filled up by functionalities ("missing",...) to explain the automatic exclusion step
		->addString("custom_exclusion",255,0,true,false)//This field can be filled up by user
		->addBoolean("excluded",false)
		->addForeignKey("information_session", "InformationSession", false, false, true, true, false)//An applicant can be assigned to an information session
		->addForeignKey("exam_center", "ExamCenter", false, false, true, true, false)//An applicant can be assigned to an exam center (maybe after importing applicant data
		->addForeignKey("exam_center_room", "ExamCenterRoom", false, false, true, true, false) //An applicant can be assigned to an exam center room after(maybe after importing applicant data
		->addForeignKey("exam_session", "ExamSession", false, false, true, true, false) //An applicant can be assigned to an exam session after being assigned to an exam center, or directly assigned to a session
		->addColumnWriteAccess("information_session", "manage_information_session", true)
		->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Applicant Information", "Applicant Information",
				array(
						"applicant_id"=>"Applicant ID",
						// "excluded"=>"Excluded",
						// "exclusion_reason"=>"Exclusion Reason",
				)
		))
		->addReadAccess("can_access_selection_data",true) //All the managing rights must be handled by functionalities
		->setPrimary("people");//The primary key is the people_id
		//TODO finish + update steps.inc
	
	$sm->addTable("ApplicantMoreInfo")//This table contains all the custom data (based on the application form content)
		->addForeignKey("people", "People", true, false, false, false, true)
		->addReadAccess("can_access_selection_data",true) //All the managing rights must be handled by functionalities
		->setPrimary("people");//The primary key is the people_id
		