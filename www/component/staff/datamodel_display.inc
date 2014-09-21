<?php 
/* @var $model DataModel */

$category = new datamodel\DataDisplayCategory("PN Staff");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("Staff", $category);
$display->addFollow("people");
$model->addTableDataDisplay($display);

class StaffPositionDataDisplay extends datamodel\DataDisplay {
	
	public function getDisplayName() { return "Last Position"; }
	public function canAccess($sub_model) { return true; }
	public function getHandledColumns() { return array(); }
	public function getTypedField($sub_model) { return array("field_text","{}"); }
	public function isEditable() { return false; }
	public function getEditLocks($sub_model) { return array(); }
	public function isSortable() { return false; }
	public function isMandatory($sub_model) { return false; }
	
	public function buildSQL(&$q, $path) {
		return array("key"=>$q->getFieldAlias("People", "id"),"data"=>null);
	}
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		return null;
	}
	
	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path) {
		$ids = array();
		foreach ($rows as $row) array_push($ids, $row[$sql_aliases["key"]]);
		if (count($ids) == 0) return array();
		$q = SQLQuery::create()->select("StaffPosition")->whereIn("StaffPosition","people",$ids)->orderBy("StaffPosition", "start", false);
		$positions = $q->execute();
		$alias = $original_query->generateFieldAlias();
		$sql_aliases["data"] = $alias;
		for ($i = 0; $i < count($rows); $i++) {
			foreach ($positions as $p) {
				if ($p["people"] == $rows[$i][$sql_aliases["key"]]) {
					if ($p["end"] <> null)
						$rows[$i][$alias] = "was ".$p["position"]." from ".$p["start"]." to ".$p["end"];
					else
						$rows[$i][$alias] = $p["position"]." since ".$p["start"];
					break;
				}
			}
		}
		return array(array($q,$positions));
	}
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {}
	
}

class StaffDepartmentDataDisplay extends datamodel\DataDisplay {

	public function getDisplayName() { return "Last Department"; }
	public function canAccess($sub_model) { return true; }
	public function getHandledColumns() { return array(); }
	public function getTypedField($sub_model) { return array("field_text","{}"); }
	public function isEditable() { return false; }
	public function getEditLocks($sub_model) { return array(); }
	public function isSortable() { return false; }
	public function isMandatory($sub_model) { return false; }

	public function buildSQL(&$q, $path) {
		return array("key"=>$q->getFieldAlias("People", "id"),"data"=>null);
	}
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		return null;
	}
	
	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path) {
		$ids = array();
		foreach ($rows as $row) array_push($ids, $row[$sql_aliases["key"]]);
		if (count($ids) == 0) return array();
		$q = SQLQuery::create()->select("StaffPosition")->whereIn("StaffPosition","people",$ids)->orderBy("StaffPosition", "start", false)->join("StaffPosition","PNDepartment",array("department"=>"id"));
		$positions = $q->execute();
		$alias = $original_query->generateFieldAlias();
		$sql_aliases["data"] = $alias;
		for ($i = 0; $i < count($rows); $i++) {
			foreach ($positions as $p) {
				if ($p["people"] == $rows[$i][$sql_aliases["key"]]) {
					$rows[$i][$alias] = $p["name"];
					break;
				}
			}
		}
		return array(array($q,$positions));
	}

	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {}

}

$display = new datamodel\TableDataDisplay("StaffPosition", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("position", "Position", true, false), null);
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("department", "name", "Department"), null);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("start", "Start"), null);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("end", "End"), null);

$display->addJoinFrom("people", array("types"), function($columns) {
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("staff", $types);
});
$model->addTableDataDisplay($display);

class StaffDataScreen implements datamodel\SimpleDataScreen {
	
	public function getTables() { return array("Staff","StaffPosition"); }
	
	public function getIcon() { return "/static/staff/staff_16.png"; }
	public function getName() { return "PN Staff Positions"; }
	
	public function generate(&$page, $paths, $values, $prefilled_values, $structure_name) {
		$people_types = $values->getColumnValue("People", "types");
		$people_types = PNApplication::$instance->people->parseTypes($people_types);
		if (!in_array("staff", $people_types)) return;
		
		require_once("page/staff_info.inc");
		$id = $page->generateID();
		echo "<div id='$id'></div>";
		$fct = staff_info($page, $values->hasColumnValue("Staff", "people") ? $values->getColumnValue("Staff", "people") : -1, $id);
		echo "<script type='text/javascript'>";
		echo "var positions = new $fct();";
		if ($structure_name <> null) {
			echo "var path = {path:".json_encode($paths[0]->getString()).",columns:{},getValue:function(){return null;},validate:function(){return null}};";
			echo "$structure_name.push(path);";
			echo "path = {path:".json_encode($paths[1]->getString()).",positions:positions,columns:{}";
			echo ",getValue:function(){ return this.positions.positions; }";
			echo ",validate:function(){ if (this.positions.positions.length == 0) return 'Please enter at least one position as a PN staff'; return null; }";
			echo "};";
			echo "$structure_name.push(path);";
		}
		echo "</script>";
	}
	
	public function generateEndOfSection(&$page, $paths, $values, $prefilled_values, $structure_name) {}
	
	public function createData($paths, $multiple) {
		$path = $paths[0];
		$people_id = $path->columns["people"];
		SQLQuery::create()->insert("Staff", array("people"=>$people_id));
		$path = $paths[1];
		$rows = array();
		foreach ($path->value as $pos) {
			$pos["people"] = $people_id;
			unset($pos["id"]);
			array_push($rows, $pos);
		}
		SQLQuery::create()->insertMultiple("StaffPosition", $rows);
	}
	
}
$model->addDataScreen(new StaffDataScreen());

class MultipleStaffDataScreen implements \datamodel\MultipleDataScreen {
	
	public function getTables() { return array("StaffPosition"); }
	
	public function getDataDisplay($come_from, $sub_model, $for_new_data) {
		$list = array();
		if ($for_new_data) {
			array_push($list, new \datamodel\ChoiceDataDisplay("department", "name", "Department", true, true));
			array_push($list, new \datamodel\SimpleDataDisplay("position", "Position"));
			array_push($list, new \datamodel\SimpleDataDisplay("start", "Started on"));
		} else {
			array_push($list, new StaffPositionDataDisplay());
			array_push($list, new StaffDepartmentDataDisplay());
		}
		foreach ($list as $data) $data->setTableAndCategory(\DataModel::get()->internalGetTable("StaffPosition"), DataModel::get()->getDataCategory("PN Staff"));
		return $list;
	}
	
	public function createData($paths, $multiple) {
		$path = $paths[0];
		$people_id = $path->columns["people"];
		SQLQuery::create()->insert("Staff", array("people"=>$people_id));
		foreach ($path->value as $cd)
			if ($cd["name"] == "Position") $position = $cd["value"];
			else if ($cd["name"] == "Department") $department = $cd["value"];
			else if ($cd["name"] == "Started on") $start = $cd["value"];
		SQLQuery::create()->insert("StaffPosition", array("people"=>$people_id,"position"=>$position,"department"=>$department,"start"=>$start));
	}
}
$model->addDataScreen(new MultipleStaffDataScreen());
?>