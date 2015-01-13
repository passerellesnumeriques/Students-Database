<?php 

class ApplicantTableDataDisplay extends datamodel\TableDataDisplay {
	
	public function __construct($category) {
		parent::__construct("Applicant", $category);
	}
	
	public function finalizeCreateData($key, $sub_model, &$table_fields, $come_from) {
		// ensure to create ApplicantMoreInfo entry, even nothing is there yet
		$cols = DataModel::get()->internalGetTable("ApplicantMoreInfo")->internalGetColumnsFor($sub_model);
		if (count($cols) == 1) {
			if ($key <> null) $k = $key;
			else $k = $table_fields->getValue("Applicant", $sub_model, null, "people");
			$table_fields->addValue("ApplicantMoreInfo", $sub_model, null, "people", $k);
		}
		// generate applicant ID if needed
		if (PNApplication::$instance->selection->getOneConfigAttributeValue("generate_applicant_id")) {
			// put a zero so the AUTO_INCREMENT feature of the database will do it
			$table_fields->addValue("Applicant", PNApplication::$instance->selection->getCampaignId(), $key, "applicant_id", 0);
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
	
	private $cache = array();
	public function getDataDisplay($come_from, $sub_model) {
		$list = parent::getDataDisplay($come_from, $sub_model);
		if ($sub_model <> "@link") {
			if (!isset($this->cache[$come_from]))
				$this->cache[$come_from] = array();
			if (!isset($this->cache[$come_from][$sub_model])) {
				// add the exam/extract results
				$this->cache[$come_from][$sub_model] = array();
				$subjects = SQLQuery::create()->bypassSecurity()->select("ExamSubject")->selectSubModel("SelectionCampaign", $sub_model)->execute();
				foreach ($subjects as $subject) {
					$data_display = new \datamodel\ReadOnlyDataDisplay("Exam ".$subject["name"], "ApplicantExamSubject>applicant(exam_subject=".$subject["id"].").score");
					$data_display->setTableAndCategory($this->getTable(), $this->getCategory());
					array_push($this->cache[$come_from][$sub_model], $data_display);
				}
				$extracts = SQLQuery::create()->bypassSecurity()->select("ExamSubjectExtract")->selectSubModel("SelectionCampaign", $sub_model)->execute();
				foreach ($extracts as $extract) {
					$data_display = new \datamodel\ReadOnlyDataDisplay("Exam ".$extract["name"], "ApplicantExamExtract>applicant(exam_extract=".$extract["id"].").score");
					$data_display->setTableAndCategory($this->getTable(), $this->getCategory());
					array_push($this->cache[$come_from][$sub_model], $data_display);
				}
				// add programs eligibility
				if ($sub_model == PNApplication::$instance->selection->getCampaignId()) {
					foreach (PNApplication::$instance->selection->getPrograms() as $program) {
						$data_display = new \datamodel\ReadOnlyDataDisplay("Passed exam for ".$program["name"], "ApplicantExamProgramPasser>applicant(program=".$program["id"].").program","center",null,array("field_boolean","{}"));
						$data_display->setTableAndCategory($this->getTable(), $this->getCategory());
						array_push($this->cache[$come_from][$sub_model], $data_display);
					}
				}
				// add interview criteria
				$criteria = SQLQuery::create()->bypassSecurity()->select("InterviewCriterion")->selectSubModel("SelectionCampaign", $sub_model)->execute();
				foreach ($criteria as $c) {
					$data_display = new \datamodel\ReadOnlyDataDisplay("Interview: ".$c["name"], "ApplicantInterviewCriterionGrade>people(criterion=".$c["id"].").grade");
					$data_display->setTableAndCategory($this->getTable(), $this->getCategory());
					array_push($this->cache[$come_from][$sub_model], $data_display);
				}
			}
			$list = array_merge($list, $this->cache[$come_from][$sub_model]);
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
	
	public function getHorizontalAlign($sub_model) { return "right"; }
	
	public function getTypedField($sub_model) {
		$cfg = "{";
		$cfg .= "can_be_null:false";
		$cfg .= ",min:1";
		if (PNApplication::$instance->selection->getOneConfigAttributeValue("generate_applicant_id",$sub_model))
			$cfg .= ",pad:".PNApplication::$instance->selection->getOneConfigAttributeValue("number_of_applicant_id_digits",$sub_model);
		$cfg .= "}";
		return array("field_integer",$cfg);
	}

	public function exportValue($value, $sub_model) {
		if (PNApplication::$instance->selection->getOneConfigAttributeValue("generate_applicant_id")) {
			$digits = PNApplication::$instance->selection->getOneConfigAttributeValue("number_of_applicant_id_digits");
			return str_pad("".$value, $digits, "0", STR_PAD_LEFT);
		}
		return $value;
	}
}

class ApplicantExclusionReason extends datamodel\DataDisplay {

	public function getDisplayName() { return "Exclusion Reason"; }
	public function canAccess($sub_model) { return PNApplication::$instance->user_management->hasRight("see_applicant_info"); }
	public function getHandledColumns() { return array("automatic_exclusion_step","automatic_exclusion_reason","custom_exclusion"); }
	public function getTypedField($sub_model) { return array("field_text","{}"); }
	public function getHorizontalAlign($sub_model) { return "left"; }
	public function isEditable() { return false; }
	public function getEditLocks($sub_model) { return array(); }
	public function isSortable() { return false; }
	public function isMandatory($sub_model) { return false; }
	public function getTypedFilter($sub_model) {
		$cfg = "{";
		$cfg .= "possible_values:[";
			$cfg .= "'Too old'";
			$cfg .= ",'Absent during exam'";
			$cfg .= ",'Only partially attend the exam'";
			$cfg .= ",'Cheat during exam'";
			$cfg .= ",'Failed at exam'";
			$cfg .= ",'Absent for interview'";
			$cfg .= ",'Failed at interview'";
			$cfg .= ",'Other: Manual Exclusion'";
		$cfg .= "]";
		$cfg .= ",can_be_null:false";
		$cfg .= "}";
		return array("filter_enum",$cfg);
	}
	
	public function buildSQL(&$q, $path, $filtered) {
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
		if (!isset($filter["values"]) || !is_array($filter["values"])) return null;
		$table_alias = $q->getTableAlias($path->table->getName());
		$w = "";
		foreach ($filter["values"] as $enum_value) {
			if ($enum_value == "") continue;
			if (strlen($w) > 0) $w .= " OR ";
			switch ($enum_value) {
				case "Too old": $w .= "`$table_alias`.`automatic_exclusion_reason`='Too old'"; break;
				case "Absent during exam": $w .= "(`$table_alias`.`automatic_exclusion_step`='Written Exam' AND `$table_alias`.`exam_attendance`='No')"; break;
				case "Only partially attend the exam": $w .= "(`$table_alias`.`automatic_exclusion_step`='Written Exam' AND `$table_alias`.`exam_attendance`='Partially')"; break;
				case "Cheat during exam": $w .= "(`$table_alias`.`automatic_exclusion_step`='Written Exam' AND `$table_alias`.`exam_attendance`='Cheating')"; break;
				case "Failed at exam": $w .= "(`$table_alias`.`automatic_exclusion_step`='Written Exam' AND `$table_alias`.`automatic_exclusion_reason`='Failed')"; break;
				case "Absent for interview": $w .= "(`$table_alias`.`automatic_exclusion_step`='Interview' AND `$table_alias`.`automatic_exclusion_reason`='Absent')"; break;
				case "Failed at interview": $w .= "(`$table_alias`.`automatic_exclusion_step`='Interview' AND `$table_alias`.`automatic_exclusion_reason`='Failed')"; break;
				case "Other: Manual Exclusion": $w .= "`$table_alias`.`custom_exclusion` IS NOT NULL"; break;
			}
		}
		if ($w == "") return null;
		return array("type"=>"where","condition"=>$w);
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
$display->addDataDisplay(new datamodel\SimpleDataDisplay("interview_attendance", "Interview Attendance",true,false));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("interview_comment", "Comment from Interview", true, false));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("interview_passer", "Eligible for Social Investigation", true, false));
$display->addDataDisplay(new datamodel\ReadOnlyDataDisplay("Social Investigation Visits", "SocialInvestigation>applicant.event.start","right",null,array("field_date","{show_utc:true}")));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("si_grade", "Social Investigation Grade", true, false));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("final_decision","Final Decision of PN", true, false, "center"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("applicant_decision","Final Decision of Applicant", true, false, "center"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("applicant_not_coming_reason","Reason of Applicant to decline", true, false));
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
				if (PNApplication::$instance->user_management->hasRight("edit_applicants") && !$is_new) {
					$html .= "<td>";
					$html .= "<button class='flat small' title='Change Information Session' onclick='changeIS(this);return false;'><img src='".theme::make_icon("/static/selection/is/is_16.png", theme::$icons_10["_import"])."'/></button>";
					$html .= "</td>";
				}
				break;
			case "Exam Center":
				if (PNApplication::$instance->user_management->hasRight("edit_applicants") && !$is_new) {
					$html .= "<td>";
					$html .= "<button class='flat small' title='Change Exam Center' onclick='changeExamCenter(this);return false;'><img src='".theme::make_icon("/static/selection/exam/exam_center_16.png", theme::$icons_10["_import"])."'/></button>";
					$html .= "</td>";
				}
				break;
			case "Interview Center":
				if (PNApplication::$instance->user_management->hasRight("edit_applicants") && !$is_new) {
					$html .= "<td>";
					$html .= "<button class='flat small' title='Change Interview Center' onclick='changeInterviewCenter(this);return false;'><img src='".theme::make_icon("/static/selection/exam/exam_center_16.png", theme::$icons_10["_import"])."'/></button>";
					$html .= "</td>";
				}
				break;
		}
		return $html;
	}
	
	protected function generateDataTable(&$page, $path, $list, $fixed_columns, $values, $prefilled_values, $structure_name, $is_new) {
		$html = parent::generateDataTable($page, $path, $list, $fixed_columns, $values, $prefilled_values, $structure_name, $is_new);
		if (!$is_new) {
			$applicant_id = $values->hasColumnValue("Applicant","people") ? $values->getColumnValue("Applicant", "people") : $values->getColumnValue("People", "id");
			$html .= "<script type='text/javascript'>";
			$html .= "function changeIS(button) {";
				$html .= "require('assign_is.js', function() {";
					$html .= "assign_is(button, [".$applicant_id."], function() {location.reload();});";
				$html .= "});";
			$html .= "}";
			$html .= "function changeExamCenter() {";
				$html .= "popupFrame('/static/selection/exam/exam_center_16.png', 'Assign applicant to an exam center', '/dynamic/selection/page/exam/assign_applicants_to_center?ondone=refresh', {applicants:[".$applicant_id."]}, null, null, function(frame,popup) { frame.refresh=function() {location.reload();}; });";
			$html .= "}";
			$html .= "function changeInterviewCenter() {";
				$html .= "popupFrame('/static/selection/exam/exam_center_16.png', 'Assign applicant to an interview center', '/dynamic/selection/page/interview/assign_applicants_to_center?ondone=refresh', {applicants:[".$applicant_id."]}, null, null, function(frame,popup) { frame.refresh=function() {location.reload();}; });";
			$html .= "}";
			$html .= "</script>";
		}
		return $html;
	}
	
	public function generateEndOfSection(&$page, $paths, $values, $prefilled_values, $structure_name) {
		if (!$values->hasColumnValue("Applicant", "people")) return;
		if ($values->getColumnValue("Applicant", "excluded") == 1) {
?>
<button class='action green' onclick='unexclude();'>Back after exclusion</button>
<script type='text/javascript'>
var from_auto = <?php echo $values->hasColumnValue("Applicant", "automatic_exclusion_step") ? json_encode($values->getColumnValue("Applicant", "automatic_exclusion_step")) : "null"; ?>;
function unexclude() {
	confirmDialog(
		from_auto
			? "This applicant has been automatically excluded at "+from_auto+" step because of: "+<?php echo json_encode($values->getColumnValue("Applicant","automatic_exclusion_reason"));?>+".<br/>Are you sure you want to put this applicant back in the process, even he/she do not comply to rules ?"
			: "Are you sure you want to cancel the exclusion, and put back this applicant in the process ?"
		,function(yes) {
			if (!yes) return;
			service.json("data_model","save_cells",{cells:[
			  {
				table:'Applicant',
				sub_model:<?php echo PNApplication::$instance->selection->getCampaignId();?>,
				keys:[<?php echo $values->getColumnValue("Applicant", "people");?>],
				values:[
					{column:'excluded',value:'0'},
					{column:'automatic_exclusion_step',value:null},
					{column:'automatic_exclusion_reason',value:null},
					{column:'custom_exclusion',value:null}
				]
			  }
			]},function(res) {
				if (res) location.reload();
			});
		}
	);
}
</script>
<?php 
		} else {
?>
<button class='action red' onclick='exclude();'>Exclude</button>
<script type='text/javascript'>
function exclude() {
	popupFrame(null,"Exclude Applicant","/dynamic/selection/page/applicant/exclude?ondone=_excluded",{applicants:[<?php echo $values->getColumnValue("Applicant", "people");?>]},null,null,function(f,p) {
		f._excluded = function() { location.reload(); };
	});
}
</script>
<?php 
		}
	}
	
}
$model->addDataScreen(new ApplicantDataScreen($display));

$display = new datamodel\TableDataDisplay("ApplicantInterviewer", $category);
$display->addJoinFrom("applicant");
$display->addDataDisplay(new datamodel\JoiningTableDataDisplay("Interviewers", "applicant", "interviewer", "name"));
$model->addTableDataDisplay($display);
$model->internalGetTable("CalendarEventAttendee")
	->addReadAccessFromForeignTable("ApplicantInterviewer");

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