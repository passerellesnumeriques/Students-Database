<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$category = new datamodel\DataDisplayCategory("Selection", "/static/selection/selection_16.png");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("Applicant", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("applicant_id", "Applicant ID", true));
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("information_session", "name", "Information Session", false));
$display->addFollow("people");
$display->addJoinFrom("people", array("types"), function($columns) {
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("applicant", $types);
});
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

class ApplicantDataScreen extends datamodel\GenericDataScreen {
	
	public function generate(&$page, $paths, $values, $prefilled_values, $structure_name) {
		$people_types = $values->getColumnValue("People", "types");
		$people_types = PNApplication::$instance->people->parseTypes($people_types);
		if (!in_array("applicant", $people_types)) return;
		
		$path = $paths[0];
		if ($structure_name <> null) {
			// new applicant
			$list = array();
			if (!PNApplication::$instance->selection->getOneConfigAttributeValue("generate_applicant_id")) {
				$data = new datamodel\SimpleDataDisplay($column_name, "Applicant ID", false);
				$data->setTableDataDisplay($this->display);
				array_push($list, $data);
			}
			$data = new datamodel\ChoiceDataDisplay("information_session", "name", "From Information Session");
			$data->setTableDataDisplay($this->display);
			array_push($list, $data);
			$fixed_columns = array();
			echo parent::generateDataTable($page, $path, $list, $fixed_columns, $values, $prefilled_values, $structure_name, true);
		} else {
			parent::generate($page, $paths, $values, $prefilled_values, $structure_name);
		}
	}
	
	public function createData($paths) {
		$path = $paths[0];
		$people_id = $path->columns["people"];
		if (PNApplication::$instance->selection->getOneConfigAttributeValue("generate_applicant_id")) {
			$applicant_id = SQLQuery::create()->select("Applicant")->expression("MAX(`applicant_id`)+1", "new_id")->executeSingleValue();
			if ($applicant_id == null) $applicant_id = 1; // first one
		} else {
			foreach ($path->value as $cd)
				if ($cd["name"] == "Application ID") {
					$applicant_id = $cd["value"];
					break;
				}
		}
		$fields = array("people"=>$people_id, "applicant_id"=>$applicant_id);
		foreach ($path->value as $cd)
			switch ($cd["name"]) {
				case "From Information Session": $fields["information_session"] = $cd["value"]; break;
			}
		SQLQuery::create()->insert("Applicant", $fields);
		// TODO if assigned to information session, assign to corresponding exam center
	}
	
}

$model->addDataScreen(new ApplicantDataScreen($display));
?>