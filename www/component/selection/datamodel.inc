<?php
/* @var $model DataModel */
	$model->addTable("SelectionCampaign")
		->addPrimaryKey("id")
		->addString("name")
		->addForeignKey("calendar","Calendar",false,true,false,false)
		->addReadAccess("can_access_selection_data",true)
		->addWriteAccess("manage_selection_campaign",true)
		->addInsertAccess("manage_selection_campaign",true)
		->addRemoveAccess("manage_selection_campaign",true);
		
	$sm = $model->addSubModel("SelectionCampaign");
	
	$sm->addDataCategoryLink("Information Session profile", "/dynamic/selection/page/IS_profile?id=%Information_session.id%", "/static/selection/IS_16.png");
	
	$sm->addTable("Information_session")
		->addPrimaryKey("id")
		->addForeignKey("date","CalendarEvent",false,true,true,true)
		->addForeignKey("postal_address","Postal_address",false,true,true,true)
		//This field exists in case the user wants to set an address but no host partner
		// In case a host partner is choosen, this field must be set to null
		->addForeignKey("fake_organization","Organization",false,false,false,false)
		//This field is set to link the custom postal address to an organization
		//Otherwize, the filter of the "contact" component datamodel wont let the user see/manage the address
		//Because of the reading filter of organization (if fake, cannot be read!),
		//the fake organization must be removed manually (must use bypass_security method)
		//so the the remove_primary_when_foreign_removed is set to false
		
		->addInteger("number_boys_expected",10)
		->addInteger("number_girls_expected",10)
		->addInteger("number_boys_real",10)
		->addInteger("number_girls_real",10)
		->addString("name")
		->addWriteAccess("edit_information_session",true)
		->addReadAccess("see_information_session_details",true)
		->addInsertAccess("manage_information_session",true)
		->addRemoveAccess("manage_information_session",true)
		->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Information Session","Information Session",
			array(
				"name" => "Name",
				"date" => "Date"
			),
			array(
				"name" => array("not_editable"),
				"date" => array("not_editable")
			),"selection"
		));
		
	$sm->addTable("Information_session_partner")
		->addForeignKey("information_session","Information_session",true,false,true,false)
		->addForeignKey("organization","Organization",true,false,true,false)
		->addBoolean("host") //this attributes means that this partner is the one where the IS takes place
		->addForeignKey("host_address","Postal_address",false,false,true,true) //this attributes is set only when host attribute == true: can select a praticular address in case the organization has several ones
		->setKey(array("information_session","organization"))
		->addWriteAccess("edit_information_session",true)
		->addReadAccess("see_information_session_details",true)
		->addInsertAccess("manage_information_session",true)
		->addRemoveAccess("manage_information_session",true);
		
	$sm->addTable("Information_session_contact_point")
		->addForeignKey("information_session","Information_session",true,false,true,false)
		->addForeignKey("organization","Organization",true,false,true,false)
		//This two attributes are a foreignKey for IS partner table
		->addForeignKey("people","People",true,false,true,false)
		//People + orga = foreignKey for contact_point table
		->setKey(array("information_session","organization","people"))
		->addWriteAccess("edit_information_session",true)
		->addReadAccess("see_information_session_details",true)
		->addInsertAccess("manage_information_session",true)
		->addRemoveAccess("manage_information_session",true)
//TODO add a field "remark"		
		// ->setDisplayHandler("people", new datamodel\StopDataDisplayHandler("Selection", "Selection"))
		;		
		
	$sm->addTable("Selection_campaign_config")
		->addString("name",255,1,false,true)//the name must be unique (key...)
		->addString("value",255,1,false,false)
		->addReadAccess("can_access_selection_data",true)
		->addInsertAccess("manage_selection_campaign",true)
		->addRemoveAccess("manage_selection_campaign",true)
		->addWriteAccess("manage_selection_campaign",true)
		->setPrimary("name");
		
	// $sm->addTable("Selection_campaign_steps")
		// ->addString("name",255,1,false,true)
		// ->addBoolean("value")
		// ->addReadAccess("can_access_selection_data",true)
		// ->addInsertAccess("manage_selection_campaign",true)
		// ->addRemoveAccess("manage_selection_campaign",true)
		// No write access. This is the selection component that checks the matching rights of the user, and use bypass_security if the user is allowed
		// ->setPrimary("name");
		
	$sm->addTable("Exam_subject")
		->addPrimaryKey("id")
		->addString("name",255,1,false,false) //cannot have two exam with the same name on the same campaign
		->addDecimal("max_score",10,2,0,null,false,false) // must be calculated
		->addWriteAccess("manage_exam_subject",true)
		->addReadAccess("see_exam_subject",true)
		->addInsertAccess("manage_exam_subject",true)
		->addRemoveAccess("manage_exam_subject",true);
		
	$sm->addTable("Exam_subject_part")
		->addPrimaryKey("id")
		->addForeignKey("exam_subject","Exam_subject",true,false,true,false)
		->addInteger("index",32,null,null,false,false)
		->addDecimal("max_score",10,2,0,null,false,false) // must be calculated
		->addString("name",255,0,false)
		->addWriteAccess("manage_exam_subject",true)
		->addReadAccess("see_exam_subject",true)
		->addInsertAccess("manage_exam_subject",true)
		->addRemoveAccess("manage_exam_subject",true);
		
	$sm->addTable("Exam_subject_question")
		->addPrimaryKey("id")
		->addForeignKey("exam_subject_part","Exam_subject_part",true,false,true)
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
		
	$sm->addTable("Exam_topic_for_eligibility_rule")
		/**
		 * As exam topics and exam subject are synchronized by some functionalities,
		 * the rights must be exactly the same!
		 */
		->addPrimaryKey("id")
		->addString("name",255,0,false)
		->addWriteAccess("manage_exam_subject",true)
		->addReadAccess("see_exam_subject",true)
		->addInsertAccess("manage_exam_subject",true)
		->addRemoveAccess("manage_exam_subject",true);
	
	$sm->addTable("Exam_topic_full_exam")
		/** Table to match an exam topic with exam(s) subject(s)
		 * if a topic is set in this table, it will be considered as
		 * "full subject" for all the matching exams subjects
		 */
		->addForeignKey("exam_subject","Exam_subject",true,false,true,false)
		->addForeignKey("exam_topic_for_eligibility_rule","Exam_topic_for_eligibility_rule",true,false,true,false)
		->setKey(array("exam_subject","exam_topic_for_eligibility_rule"))
		->addWriteAccess("manage_exam_subject",true)
		->addReadAccess("see_exam_subject",true)
		->addInsertAccess("manage_exam_subject",true)
		->addRemoveAccess("manage_exam_subject",true);
	
	$sm->addTable("Exam_part_topic")
		->addForeignKey("exam_topic_for_eligibility_rule","Exam_topic_for_eligibility_rule",true,false,true,false)
		->addForeignKey("exam_subject_part","Exam_subject_part",true,false,false,false,true)
		/** cannot be multiple because cannot appear in several topics (otherwize the rules are biased...)
		 * Before removing a part, the method
		 * #selection#updateExamTopicsForEligibilityRules is called
		 */
		->setKey(array("exam_topic_for_eligibility_rule","exam_subject_part"))
		->addWriteAccess("manage_exam_subject",true)
		->addReadAccess("see_exam_subject",true)
		->addInsertAccess("manage_exam_subject",true)
		->addRemoveAccess("manage_exam_subject",true);
		
	$sm->addTable("Applicant")
		->addPrimaryKey("id")
		->addInteger("applicant_id",32,1,null,false,true)//applicant_id must be a number
		->addString("first_name", 100, 1, false)
		->addString("middle_name", 100, 1, true)
		->addString("last_name", 100, 1, false)
		->addString("khmer_first_name", 100, 1, true)
		->addString("khmer_last_name", 100, 1, true)
		->addEnum("sex", array("M","F"),false)
		->addDate("birth", false)
		->addBoolean("excluded",false)
		->addString("exclusion_reason", 100, 1, true)
		->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Applicant Information", "Applicant Information",
			array(
				"first_name"=>"First Name",
				"middle_name"=>"Middle Name",
				"last_name"=>"Last Name",
				"khmer_first_name"=>"Khmer First Name",
				"khmer_last_name"=>"Khmer Last Name",
				"sex"=>"Gender",
				"birth"=>"Birth Date",
				"applicant_id"=>"Applicant ID",
				// "excluded"=>"Excluded",
				// "exclusion_reason"=>"Exclusion Reason",
			)
		))
		->addWriteAccess("manage_applicant_info",true)
		->addReadAccess("see_applicant_info",true)
		->addInsertAccess("create_applicant",true)
		->addRemoveAccess("manage_applicant_info",true);
		//TODO finish + update steps.inc
		
	$sm->addTable("Exam_question_result")
		->addForeignKey("applicant","Applicant",true,false,true,false)
		->addForeignKey("exam_subject_question","Exam_subject_question",true,false,true,false)
		->addString("answer")//optional
		->addDecimal("score",10,2,0,null,false,false)
		->setKey(array("applicant","exam_subject_question"));
		//TODO finish + update steps.inc
		
		//TODO Exam_part_result, exam_subject_result (reduce the calculation time for statistics...)
		
		
		
		
		
		
		
		
		