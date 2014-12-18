<?php
/* @var $model DataModel */
/* @var $sm SubDataModel */

$sm->addTable("Applicant")
	->addForeignKey("people", "People", true, false, false, false, true)
	->addInteger("applicant_id",32,0,null,false,true)//applicant_id must be a number, minimum 0 so we can also generate using AUTO_INCREMENT
	->addEnum("automatic_exclusion_step", array("Application Form","Written Exam","Interview","Social Investigation"))
	->addString("automatic_exclusion_reason",255,0,true,false)//This field must be filled up by functionalities ("missing",...) to explain the automatic exclusion step
	/* Possible values are:
	 *  - Application Form
	 *    + Too old
	 *  - Written Exam
	 *    + Failed
	 *    + Attendance
	 *  - Interview
	 *    + Absent
	 *    + Failed
	 *  - Social Investigation
	 *    + Failed
	 */
	->addString("custom_exclusion",255,0,true,false)//This field can be filled up by user
	->addBoolean("excluded",false)
	->addForeignKey("high_school", "Organization", false, false, true)
	->addForeignKey("ngo", "Organization", false, false, true)
	->addForeignKey("information_session", "InformationSession", false, false, true, true, false)//An applicant can be assigned to an information session
	->addForeignKey("exam_center", "ExamCenter", false, false, true, true, false)//An applicant can be assigned to an exam center (maybe after importing applicant data
	->addForeignKey("exam_center_room", "ExamCenterRoom", false, false, true, true, false) //An applicant can be assigned to an exam center room after(maybe after importing applicant data
	->addForeignKey("exam_session", "ExamSession", false, false, true, true, false) //An applicant can be assigned to an exam session after being assigned to an exam center, or directly assigned to a session
	->addEnum("exam_attendance", array("Yes","No","Partially","Cheating"), true, false)
	->addBoolean("exam_passer",true)
	->addForeignKey("interview_center", "InterviewCenter", false, false, true, true, false)//An applicant can be assigned to an exam center (maybe after importing applicant data
	->addForeignKey("interview_session", "InterviewSession", false, false, true, true, false) //An applicant can be assigned to an exam session after being assigned to an exam center, or directly assigned to a session
	->addBoolean("interview_attendance", true)
	->addString("interview_comment",1000,0,true,false)
	->addBoolean("interview_passer",true)
	->addEnum("si_grade", array("Priority 1 (A+)", "Priority 2 (A)", "Priority 3 (A-)", "Priority 4 (B+)", "Priority 5 (B)", "Failed"), true, false)
	->addEnum("final_decision", array("Selected","Waiting List","Rejected"), true, false)
	->addEnum("applicant_decision", array("Will come", "Declined"), true, false)
	->addString("applicant_not_coming_reason",250,0,true,false)
	->addColumnWriteAccess("information_session", "manage_information_session", true)
	->addReadAccess("can_access_selection_data",true) //All the managing rights must be handled by functionalities
	->addInsertAccess("edit_applicants",true)
	->addRemoveAccess("edit_applicants",true)
	->setPrimary("people")//The primary key is the people_id
	->addIndex("applicant_id") // it must be set as unique index
	->setRowDescriptionProvider(function($row) {
		$people = SQLQuery::create()->bypassSecurity()->select("People")->whereValue("People","id",$row["people"])->executeSingleRow();
		return DataModel::get()->internalGetTable("People")->getRowDescription($people);
	})
	;
// check applicant age, if someone change the birthdate
$model->internalGetTable("People")
	->onUpdate(function($keys_fields_values, $sub_model) {
		$applicants_by_campaign = array();
		foreach ($keys_fields_values as $updated) {
			$keys = $updated[0];
			$fields_values = $updated[1];
			if (!isset($fields_values["birth"]) || $fields_values["birth"] == null) return;
			// check peoples who are applicants
			$applicants_links = SQLQuery::create()->bypassSecurity()->select("smlink_Applicant_People")->whereIn("smlink_Applicant_People","root",$keys)->execute();
			foreach ($applicants_links as $link) {
				if (!isset($applicants_by_campaign[$link["sm"]])) $applicants_by_campaign[$link["sm"]] = array();
				array_push($applicants_by_campaign[$link["sm"]], array(array($link["root"]), $fields_values));
			}
		}
		if (count($applicants_by_campaign) == 0) return; // no applicant
		foreach ($applicants_by_campaign as $campaign_id=>$applicants) {
			// get selection campaign configuration
			$config = SQLQuery::create()->bypassSecurity()->selectSubModel("SelectionCampaign", $campaign_id)->select("SelectionCampaignConfig")->whereIn("SelectionCampaignConfig","name",array("forbid_too_old_applicants","limit_date_of_birth"))->execute();
			$forbid = false;
			foreach ($config as $c) if ($c["name"] == "forbid_too_old_applicants") { $forbid = json_decode($c["value"]); break; }
			if (!$forbid) continue; // no limit for this campaign
			$min_bd = null;
			foreach ($config as $c) if ($c["name"] == "limit_date_of_birth") { $min_bd = json_decode($c["value"]); break; }
			if ($min_bd == null || $min_bd == "") continue; // not set ? should never happen...
			require_once("component/data_model/TableDefinition.inc");
			$min_bd = datamodel\ColumnDate::toTimestamp($min_bd);
			// check applicants
			foreach ($applicants as $update_list) {
				$applicants_ids = $update_list[0];
				$fields_values = $update_list[1];
				$bd = datamodel\ColumnDate::toTimestamp($fields_values["birth"]);
				if ($bd < $min_bd) {
					// too old, make sure those applicants are excluded
					SQLQuery::create()->bypassSecurity()
						->selectSubModel("SelectionCampaign", $campaign_id)
						->updateByKeys("Applicant", array(
							array(
								$applicants_ids, 
								array("excluded"=>1,"automatic_exclusion_step"=>"Application Form","automatic_exclusion_reason"=>"Too old")
							)
						));
				} else {
					// not too old, put them back if those applicants were excluded because of age
					$back_list = SQLQuery::create()->bypassSecurity()
						->selectSubModel("SelectionCampaign", $campaign_id)
						->select("Applicant")
						->whereIn("Applicant","people",$applicants_ids)
						->whereValue("Applicant", "excluded", 1)
						->whereValue("Applicant", "automatic_exclusion_step", "Application Form")
						->whereValue("Applicant", "automatic_exclusion_reason", "Too old")
						->field("people")
						->executeSingleField();
					if (count($back_list) > 0)
						SQLQuery::create()->bypassSecurity()
							->selectSubModel("SelectionCampaign", $campaign_id)
							->updateByKeys("Applicant", array(
								array(
									$back_list,
									array("excluded"=>0,"automatic_exclusion_step"=>null,"automatic_exclusion_reason"=>null)
								)	
							));
				}
			}
		}
	});

$sm->addTable("ApplicantMoreInfo")//This table contains all the custom data (based on the application form content)
	->addForeignKey("people", "People", true, false, false, false, true)
	->customizable("Application Form","edit_application_form_fields")
	->addReadAccess("can_access_selection_data",true) //All the managing rights must be handled by functionalities
	->addWriteAccess("edit_applicants", true)
	->addInsertAccess("edit_applicants",true)
	->addRemoveAccess("edit_applicants",true)
	->setPrimary("people")//The primary key is the people_id
	;

$sm->addSubModelLink("Applicant","People",true,array("types"),function($columns){
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("applicant",$types);
});

?>