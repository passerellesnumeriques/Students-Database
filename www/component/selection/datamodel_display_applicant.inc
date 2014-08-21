<?php 

class ApplicantTableDataDisplay extends datamodel\TableDataDisplay {
	
	public function __construct($category) {
		parent::__construct("Applicant", $category);
	}
	
	public function finalizeCreateData($key, $sub_model, &$table_fields, $come_from) {
		if (PNApplication::$instance->selection->getOneConfigAttributeValue("generate_applicant_id")) {
			$applicant_id = SQLQuery::create()->select("Applicant")->expression("MAX(`applicant_id`)+1", "new_id")->executeSingleValue();
			if ($applicant_id == null) $applicant_id = 1; // first one
			$table_fields->addValue("Applicant", PNApplication::$instance->selection->getCampaignId(), $key, "applicant_id", $applicant_id);
		}
		$table_fields->addValue("Applicant", PNApplication::$instance->selection->getCampaignId(), $key, "excluded", false);
		// TODO assignment to exam center if assigned to IS...
	}
	
	public function getDataDisplay($come_from, $sub_model) {
		$list = parent::getDataDisplay($come_from, $sub_model);
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
		return $list;
	}
}

class ApplicantID_DataDisplay extends datamodel\SimpleDataDisplay {
	
	public function __construct() {
		if (PNApplication::$instance->selection->getCampaignId() == null)
			$editable = false;
		else
			$editable = !PNApplication::$instance->selection->getOneConfigAttributeValue("generate_applicant_id");
		parent::__construct("applicant_id", "ID", !$editable);
	}
	
	public function getHorizontalAlign() { return "right"; }
	
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
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("interview_center", "name", "Interview Center", false, false));
$display->addDataDisplay(new datamodel\ReadOnlyChoiceDataDisplay("Interview Session", "interview_session", "interview_session.event.start"));
$display->addFollow("people");
$display->addJoinFrom("people", array("types"), function($columns) {
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("applicant", $types);
});
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

class ApplicantDataScreen extends datamodel\GenericDataScreen {
	
	protected function endDataRow($data, $value, $is_new, $can_edit) {
		$html = "";
		switch ($data->getDisplayName()) {
			case "Information Session":
				if ($can_edit && !$is_new) {
					$html .= "<td>";
					$html .= "<button class='flat small' title='Change Information Session' onclick='changeIS(this);return false;'><img src='".theme::make_icon("/static/selection/is/is_16.png", theme::$icons_10["_import"])."'/></button>";
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
			$html .= "</script>";
		}
		return $html;
	}
	
}
$model->addDataScreen(new ApplicantDataScreen($display));

$category_more_info = new datamodel\DataDisplayCategory("Selection (Additional Info.)");
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