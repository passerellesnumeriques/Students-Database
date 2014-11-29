<?php 

class ApplicantTableDataDisplay extends datamodel\TableDataDisplay {
	
	public function __construct($category) {
		parent::__construct("Applicant", $category);
	}
	
	public function finalizeCreateData($key, $sub_model, &$table_fields, $come_from) {
		// ensure to create ApplicantMoreInfo entry, even nothing is there yet
		$cols = DataModel::get()->internalGetTable("ApplicantMoreInfo")->internalGetColumnsFor($sub_model);
		if (count($cols) == 1)
			$table_fields->addValue("ApplicantMoreInfo", $sub_model, $key, "people", $key);
		// generate applicant ID if needed
		if (PNApplication::$instance->selection->getOneConfigAttributeValue("generate_applicant_id")) {
			// TODO use database AUTO_INCREMENT
			$applicant_id = SQLQuery::create()->select("Applicant")->expression("MAX(`applicant_id`)+1", "new_id")->executeSingleValue();
			if ($applicant_id == null) $applicant_id = 1; // first one
			$table_fields->addValue("Applicant", PNApplication::$instance->selection->getCampaignId(), $key, "applicant_id", $applicant_id);
		}
		// set as non-excluded
		$table_fields->addValue("Applicant", PNApplication::$instance->selection->getCampaignId(), $key, "excluded", false);
		// if an IS is specified, and this IS is linked to an exam center, automatically assign the applicant to this exam center
		$is = $table_fields->getValue("Applicant", PNApplication::$instance->selection->getCampaignId(), $key, "information_session");
		if ($is <> null) {
			$exam_center = SQLQuery::create()->bypassSecurity()->select("ExamCenterInformationSession")->whereValue("ExamCenterInformationSession", "information_session", $is)->field("ExamCenterInformationSession", "exam_center")->executeSingleValue();
			if ($exam_center <> null)
				$table_fields->addValue("Applicant", PNApplication::$instance->selection->getCampaignId(), $key, "exam_center", $exam_center);
		}
	}
	
	public function getDataDisplay($come_from, $sub_model) {
		$list = parent::getDataDisplay($come_from, $sub_model);
		if ($sub_model <> "@link") {
			// add the exam/extract results
			$subjects = SQLQuery::create()->select("ExamSubject")->selectSubModel("SelectionCampaign", $sub_model)->execute();
			foreach ($subjects as $subject) {
				$data_display = new \datamodel\ReadOnlyDataDisplay("Exam ".$subject["name"], "ApplicantExamSubject>applicant(exam_subject=".$subject["id"].").score");
				$data_display->setTableAndCategory($this->getTable(), $this->getCategory());
				array_push($list, $data_display);
			}
			$extracts = SQLQuery::create()->select("ExamSubjectExtract")->selectSubModel("SelectionCampaign", $sub_model)->execute();
			foreach ($extracts as $extract) {
				$data_display = new \datamodel\ReadOnlyDataDisplay("Exam ".$extract["name"], "ApplicantExamExtract>applicant(exam_extract=".$extract["id"].").score");
				$data_display->setTableAndCategory($this->getTable(), $this->getCategory());
				array_push($list, $data_display);
			}
		}
		return $list;
	}
}

class ApplicantID_DataDisplay extends datamodel\SimpleDataDisplay {
	
	public function __construct() {
		if (PNApplication::$instance->selection->getCampaignId() == null)
			$editable = false;
		else
			$editable = !PNApplication::$instance->selection->getOneConfigAttributeValue("generate_applicant_id");
		parent::__construct("applicant_id", "ID", !$editable, $editable);
	}
	
	public function getHorizontalAlign() { return "right"; }
	
	public function getTypedField($sub_model) {
		$cfg = "{";
		$cfg .= "can_be_null:false";
		$cfg .= ",min:1";
		if (PNApplication::$instance->selection->getOneConfigAttributeValue("generate_applicant_id"))
			$cfg .= ",pad:".PNApplication::$instance->selection->getOneConfigAttributeValue("number_of_applicant_id_digits");
		$cfg .= "}";
		return array("field_integer",$cfg);
	}
	
}

class ApplicantExclusionReason extends datamodel\DataDisplay {

	public function getDisplayName() { return "Exclusion Reason"; }
	public function canAccess($sub_model) { return PNApplication::$instance->user_management->has_right("see_applicant_info"); }
	public function getHandledColumns() { return array("automatic_exclusion_step","automatic_exclusion_reason","custom_exclusion"); }
	public function getTypedField($sub_model) { return array("field_text","{}"); }
	public function getHorizontalAlign() { return "left"; }
	public function isEditable() { return false; }
	public function getEditLocks($sub_model) { return array(); }
	public function isSortable() { return false; }
	public function isMandatory($sub_model) { return false; }
	public function getTypedFilter($sub_model) { return null; } // TODO
	
	public function buildSQL(&$q, $path) {
		$table_alias = \DataPathSQLBuilder::build($path, $q);
		$key_alias = $q->getFieldAlias($table_alias, "people");
		if ($key_alias == null)
			$q->field($table_alias, "people", $key_alias = $q->generateFieldAlias());
		$excluded_alias = $q->getFieldAlias($table_alias, "excluded");
		if ($excluded_alias == null)
			$q->field($table_alias, "excluded", $excluded_alias = $q->generateFieldAlias());
		$custom_exclusion_alias = $q->getFieldAlias($table_alias, "custom_exclusion");
		if ($custom_exclusion_alias == null)
			$q->field($table_alias, "custom_exclusion", $custom_exclusion_alias = $q->generateFieldAlias());
		$automatic_exclusion_step_alias = $q->getFieldAlias($table_alias, "automatic_exclusion_step");
		if ($automatic_exclusion_step_alias == null)
			$q->field($table_alias, "automatic_exclusion_step", $automatic_exclusion_step_alias = $q->generateFieldAlias());
		$automatic_exclusion_reason_alias = $q->getFieldAlias($table_alias, "automatic_exclusion_reason");
		if ($automatic_exclusion_reason_alias == null)
			$q->field($table_alias, "automatic_exclusion_reason", $automatic_exclusion_reason_alias = $q->generateFieldAlias());
		return array("key"=>$key_alias,"data"=>$excluded_alias,"custom"=>$custom_exclusion_alias,"automatic_step"=>$automatic_exclusion_step_alias,"automatic_reason"=>$automatic_exclusion_reason_alias);
	}

	public function getData($row, $resultFromBuildSQL) {
		if (!isset($resultFromBuildSQL["data"])) return null;
		if ($row[$resultFromBuildSQL["data"]] == false) return null;
		if ($row[$resultFromBuildSQL["automatic_step"]] <> null) {
			return "Automatic from ".$row[$resultFromBuildSQL["automatic_step"]].": ".$row[$resultFromBuildSQL["automatic_reason"]];
		}
		if ($row[$resultFromBuildSQL["custom"]] <> null) {
			return "Manual: ".$row[$resultFromBuildSQL["custom"]];
		}
		return "Unknown";
	}
	
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		return null; // TODO
	}
	
	public function getNewData() { return null; }
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {}
	
}

$display = new ApplicantTableDataDisplay($category);
$display->addDataDisplay(new ApplicantID_DataDisplay());
require_once("component/contact/datamodel_display.inc");
$display->addDataDisplay(new OrganizationDataDisplay("high_school", "High School", array("Selection"), array("High School"), true, null));
$display->addDataDisplay(new OrganizationDataDisplay("ngo", "Following NGO", array("Selection"), array("NGO"), true, null));
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("information_session", "name", "Information Session", false, true));
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("exam_center", "name", "Exam Center", false, false));
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("exam_center_room", "name", "Exam Center Room", false, false));
$display->addDataDisplay(new datamodel\ReadOnlyChoiceDataDisplay("Exam Session", "exam_session", "exam_session.event.start"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("exam_attendance", "Exam Attendance",true,false));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("exam_passer", "Eligible for Interview", true, false));
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("interview_center", "name", "Interview Center", false, false));
$display->addDataDisplay(new datamodel\ReadOnlyChoiceDataDisplay("Interview Session", "interview_session", "interview_session.event.start"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("excluded", "Excluded", true, false));
$display->addDataDisplay(new ApplicantExclusionReason());
$display->addFollow("people");
$display->addJoinFrom("people", array("types"), function($columns) {
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("applicant", $types);
});
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

class ApplicantDataScreen extends datamodel\GenericDataScreen {
	
	protected function endDataRow($data, $value, $sub_index, $is_new, $can_edit) {
		$html = "";
		switch ($data->getDisplayName()) {
			case "Information Session":
				if (PNApplication::$instance->user_management->has_right("edit_applicants") && !$is_new) {
					$html .= "<td>";
					$html .= "<button class='flat small' title='Change Information Session' onclick='changeIS(this);return false;'><img src='".theme::make_icon("/static/selection/is/is_16.png", theme::$icons_10["_import"])."'/></button>";
					$html .= "</td>";
				}
				break;
			case "Exam Center":
				if (PNApplication::$instance->user_management->has_right("edit_applicants") && !$is_new) {
					$html .= "<td>";
					$html .= "<button class='flat small' title='Change Exam Center' onclick='changeExamCenter(this);return false;'><img src='".theme::make_icon("/static/selection/exam/exam_center_16.png", theme::$icons_10["_import"])."'/></button>";
					$html .= "</td>";
				}
				break;
		}
		return $html;
	}
	
	protected function generateDataTable(&$page, $path, $list, $fixed_columns, $values, $prefilled_values, $structure_name, $is_new) {
		$html = parent::generateDataTable($page, $path, $list, $fixed_columns, $values, $prefilled_values, $structure_name, $is_new);
		if (!$is_new) {
			$applicant_id = $values->getColumnValue("Applicant", "people");
			$html .= "<script type='text/javascript'>";
			$html .= "function changeIS(button) {";
				$html .= "require('assign_is.js', function() {";
					$html .= "assign_is(button, [".$applicant_id."], function() {location.reload();});";
				$html .= "});";
			$html .= "}";
			$html .= "function changeExamCenter() {";
				$html .= "popup_frame('/static/selection/exam/exam_center_16.png', 'Assign applicant to an exam center', '/dynamic/selection/page/exam/assign_applicants_to_center?ondone=refresh', {applicants:[".$applicant_id."]}, null, null, function(frame,popup) { frame.refresh=function() {location.reload();}; });";
			$html .= "}";
			$html .= "</script>";
		}
		return $html;
	}
	
}
$model->addDataScreen(new ApplicantDataScreen($display));

$category_more_info = new datamodel\DataDisplayCategory("Selection (Additional Info)");
$model->addDataCategory($category_more_info);
$display = new datamodel\TableDataDisplay("ApplicantMoreInfo", $category_more_info);
$display->addJoinFrom("people", array("types"), function($columns) {
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("applicant", $types);
});
$display->addJoinToPrimary();
$display->addFollowAll();
$model->addTableDataDisplay($display);

?>