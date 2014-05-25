<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */
$sm = $model->getSubModel("SelectionCampaign");

$category = new datamodel\DataDisplayCategory("Selection", "/static/selection/selection_16.png");
$model->addDataCategory($category);

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
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("information_session", "name", "Information Session", false, true));
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("exam_center", "name", "Exam Center", false, false));
// $display->addDataDisplay(new CalendarEventDateDataDisplay("Exam Session", "exam_session", true));
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("exam_center_room", "name", "Exam Center Room", false, false));
$display->addDataDisplay(new datamodel\ReadOnlyChoiceDataDisplay("Exam Session", "exam_session", "exam_session.event.start"));
// $display->addDataDisplay(new datamodel\ReadOnlyDataDisplay("Exam Session","exam_session.event.start"));
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
				$html .= "<td>";
				$html .= "<button class='flat small' title='Change Information Session' onclick='changeIS(this);return false;'><img src='".theme::make_icon("/static/selection/IS/IS_16.png", theme::$icons_10["_import"])."'/></button>";
				$html .= "</td>";
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

include "datamodel_is_display.inc";
include "datamodel_exam_display.inc";
?>