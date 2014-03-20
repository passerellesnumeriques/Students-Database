<?php 

$category = new datamodel\DataDisplayCategory("PN Staff");
$display = new datamodel\TableDataDisplay("StaffPosition", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("position", "Position"));
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("department", "name", "Department"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("start", "Start"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("end", "End"));
$model->addTableDataDisplay($display);

class StaffDataScreen extends datamodel\DataScreen {
	
	public function getTables() { return array("Staff"); }
	
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
			echo "var path = {path:".json_encode($paths[0]->getString()).",positions:positions,columns:{}";
			echo ",getValue:function(){ return this.positions.positions; }";
			echo ",validate:function(){ if (this.positions.positions.length == 0) return 'Please enter at least one position as a PN staff'; return null; }";
			echo "};";
			echo "$structure_name.push(path);";
		}
		echo "</script>";
	}
	
	public function createData($paths) {
		$path = $paths[0];
		$people_id = $path->columns["people"];
		SQLQuery::create()->insert("Staff", array("people"=>$people_id));
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
?>