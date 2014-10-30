<?php 
class page_people_new_type extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$new_type = $_GET["type"];
		$people_id = $_GET["people"];
		$sub_models = @$_GET["sub_models"];
		if ($sub_models == null) $sub_models = array();
		else $sub_models = json_decode($sub_models,true);
		$ondone = @$_GET["ondone"];
		$oncancel = @$_GET["oncancel"];
		
		theme::css($this, "section.css");
		
		$type = PNApplication::$instance->people->getPeopleTypePlugin($new_type);
		$tables = $type->getTables();
		$remaining_tables = array();
		foreach ($tables as $t) array_push($remaining_tables, $t);
		$screens = array();
		require_once("component/data_model/Model.inc");
		foreach (DataModel::get()->getDataScreens() as $screen) {
			if (!($screen instanceof \datamodel\SimpleDataScreen)) continue;
			$ok = true;
			foreach ($screen->getTables() as $t) if (!in_array($t, $tables)) { $ok = false; break; }
			if (!$ok) continue;
			array_push($screens, $screen);
			foreach ($screen->getTables() as $t) {
				for ($i = 0; $i < count($remaining_tables); $i++)
					if ($remaining_tables[$i] == $t) {
						array_splice($remaining_tables, $i, 1);
						$i--;
					}
			}
		}
		foreach ($remaining_tables as $t) {
			$display = DataModel::get()->getTableDataDisplay($t);
			if ($display == null) continue;
			$screen = new \datamodel\GenericDataScreen($display);
			array_push($screens, $screen);
		}
		
		require_once("component/data_model/DataPath.inc");
		$all_paths = DataPathBuilder::searchFrom("People", null, false, $sub_models);
		
		require_once("component/data_model/page/data_screen.inc");
		$values = new DataValues();
		$input = isset($_POST["input"]) ? json_decode($_POST["input"], true) : array();
		if (isset($input["fixed_columns"]))
			foreach ($input["fixed_columns"] as $v)
				$values->addTableColumnValue($v["table"], $v["column"], $v["value"]);
		if (isset($input["fixed_data"]))
			foreach ($input["fixed_data"] as $v)
				$values->addTableDataValue($v["table"], $v["data"], $v["value"]);
		$prefilled_values = new DataValues();
		if (isset($input["prefilled_data"]))
			foreach ($input["prefilled_data"] as $v)
				$prefilled_values->addTableDataValue($v["table"], $v["data"], $v["value"]);
		if (isset($input["precreated"]))
			foreach ($input["precreated"] as $pc) {
				$cat = DataModel::get()->getDataCategory($pc["category"]);
				if ($cat == null) continue;
				foreach ($cat->getTables() as $table_name) {
					$display = DataModel::get()->getTableDataDisplay($table_name);
					$data = $display->getDataDisplayByName($pc["data"], null, @$pc["sub_model"]);
					if ($data <> null) {
						// found it
						if (isset($pc["forced"]) && $pc["forced"])
							$values->addTableDataValue($table_name, $pc["data"], $pc["value"]);
						else
							$prefilled_values->addTableDataValue($table_name, $pc["data"], $pc["value"]);
						break;
					}
				}
			}
		
		$people = SQLQuery::create()->select("People")->whereValue("People","id",$people_id)->executeSingleRow();
		foreach ($people as $col=>$val) {
			if ($col == "types") $val .= "/".$new_type."/";
			$values->addTableColumnValue("People", $col, $val);
		}
		
		echo "<div style='padding:10px'>";
		echo "<script type='text/javascript'>people_new_type = [];</script>";
		theme::css($this, "section.css");
		foreach ($screens as $screen) {
			$paths = array();
			$stables = $screen->getTables();
			foreach ($stables as $table_name)
				foreach ($all_paths as $p) if ($p->table->getName() == $table_name) { array_push($paths, $p); break; }
			echo "<div class='section'>";
			echo "<div class='header'><div>";
			if ($screen->getIcon() <> null) echo "<img src='".$screen->getIcon()."'/> ".toHTML($screen->getName());
			echo "</div></div>";
			echo "<div style='background-color:white;'>";
			$screen->generate($this, $paths, $values, $prefilled_values, "people_new_type");
			echo "</div>";
			echo "</div>";
		}
		echo "</div>";
?>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);
function save() {
	popup.freeze("Setting "+<?php echo json_encode($people["first_name"]);?>+" "+<?php echo json_encode($people["last_name"]);?>+" as <?php echo $type->getName()?>");
	var data = [];
	for (var i = 0; i < people_new_type.length; ++i) {
		var d = {};
		d.path = people_new_type[i].path;
		d.value = people_new_type[i].getValue();
		data.push(d);
	}
	service.json("people","new_type",{data:data,type:<?php echo json_encode($new_type)?>,people:<?php echo $people_id?>,sub_models:<?php echo json_encode($sub_models);?>},function(res){
		if (!res) { popup.unfreeze(); return; }
		<?php if ($ondone <> null) echo "window.frameElement.$ondone($people_id);";?>
		popup.close();
	});
}
if (people_new_type.length == 0)
	save();
else
	popup.addOkCancelButtons(function() {
		for (var i = 0; i < people_new_type.length; ++i) {
			var error = people_new_type[i].validate();
			if (error != null) { alert(error); return; }
		}
		save();
	},function() {
		<?php if ($oncancel <> null) echo "window.frameElement.$oncancel();";?>
		return true;
	});
</script>
<?php 
	}
	
}
?>