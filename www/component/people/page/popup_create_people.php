<?php 
class page_popup_create_people extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$types = explode(",",$_GET["types"]);
		// check first we can create people with those types
		require_once("component/people/PeopleTypePlugin.inc");
		foreach ($types as $type) {
			$ok = null;
			foreach (PNApplication::$instance->components as $c) {
				foreach ($c->getPluginImplementations() as $pi) {
					if (!($pi instanceof PeopleTypePlugin)) continue;
					if ($pi->getId() <> $type) continue;
					$ok = $pi->canRemove();
					break;
				}
				if ($ok !== null) break;
			}
			if (!$ok) {
				PNApplication::error("You cannot create a people of type ".$type);
				return;
			}
		}
		if (isset($_POST["input"]))
			$input = json_decode($_POST["input"], true);
		else
			$input = $_GET;

		$types_str = "";
		foreach ($types as $t) $types_str .= "/".$t."/";
		
		if (isset($_GET["multiple"]) && $_GET["multiple"] == "true") {
			require_once("component/data_model/page/create_multiple_data.inc");
			if ($input <> null && isset($input["fixed_columns"]))
				$fixed_columns = $input["fixed_columns"];
			else
				$fixed_columns = array();
			array_push($fixed_columns, array("table"=>"People", "column"=>"types", "value"=>$types_str));
			if ($input <> null && isset($input["fixed_data"]))
				$fixed_data = $input["fixed_data"];
			else
				$fixed_data = array();
			if ($input <> null && isset($input["prefilled_columns"]))
				$prefilled_columns = $input["prefilled_columns"];
			else
				$prefilled_columns = array();
			if ($input <> null && isset($input["prefilled_data"]))
				$prefilled_data = $input["prefilled_data"];
			else
				$prefilled_data = array();
				
			createMultipleDataPage($this, "People", null, $fixed_columns, $fixed_data, $prefilled_columns, $prefilled_data);
			?>
			<script type='text/javascript'>
			var popup = window.parent.get_popup_window_from_frame(window);
			var fixed_columns = [<?php
			$first = true;
			foreach ($fixed_columns as $fc) {
				if ($first) $first = false; else echo ",";
				echo "{table:".json_encode($fc["table"]).",column:".json_encode($fc["column"]).",value:".json_encode($fc["value"])."}";
			} 
			?>];
			popup.addNextButton(function() {
				popup.freeze("Checking information...");
				var peoples = [];
				for (var i = 0; i < grid.getNbRows(); ++i) {
					var row = grid.getRow(i);
					if (row._is_new) continue;
					var people = [];
					for (var j = 0; j < row.childNodes.length; ++j) {
						var cell = row.childNodes[j];
						var field = cell.field;
						var data = grid.getColumnById(cell.col_id).attached_data;
						if (field.error) {
							alert("Please correct the problems: "+data.name+": "+field.error);
							popup.unfreeze();
							return;
						}
						var path = cell.col_id;
						if (path == "remove") continue;
						var k = path.lastIndexOf('#');
						path = path.substring(0,k);
						var ppath = null;
						for (var k = 0; k < people.length; ++k)
							if (people[k].path == path) { ppath = people[k]; break; }
						if (ppath == null) {
							ppath = {path:path,multiple:true,columns:{},value:[]};
							var dp = new DataPath(path);
							var table = dp.lastElement().table;
							for (var k = 0; k < fixed_columns.length; ++k)
								if (fixed_columns[k].table == table)
									ppath.columns[fixed_columns[k].column] = fixed_columns[k].value;
							people.push(ppath);
						}
						ppath.value.push({name:data.name,value:field.getCurrentData()});
					}
					peoples.push(people);
				}
				if (peoples.length == 0) {
					alert("You didn't enter anybody to create");
					popup.unfreeze();
					return;
				}
				popup.removeAllButtons();
				var data = {peoples:peoples};
				<?php 
				if (isset($_GET["ondone"])) echo "data.ondone = ".json_encode($_GET["ondone"]).";";
				else if (isset($_GET["donotcreate"])) echo "data.donotcreate = ".json_encode($_GET["donotcreate"]).";";
				?>
				postData("popup_create_people_step_check", data, window);
			});
			popup.addCancelButton();
			</script>
			<?php
		} else {
			require_once("component/data_model/page/create_data.inc");
			$values = new DataValues();
			if ($input <> null && isset($input["fixed_columns"]))
				foreach ($input["fixed_columns"] as $v)
					$values->addTableColumnValue($v["table"], $v["column"], $v["value"]);
			if ($input <> null && isset($input["fixed_data"]))
				foreach ($input["fixed_data"] as $v)
					$values->addTableDataValue($v["table"], $v["data"], $v["value"]);
			$prefilled_values = new DataValues();
			if ($input <> null && isset($input["prefilled_data"]))
				foreach ($input["prefilled_data"] as $v)
					$prefilled_values->addTableDataValue($v["table"], $v["data"], $v["value"]);
			
			$values->addTableColumnValue("People", "types", $types_str);
			
			$structure_name = createDataPage($this, "People", null, $values, $prefilled_values);
			?>
			<script type='text/javascript'>
			var structure = <?php echo $structure_name;?>;
			var popup = window.parent.get_popup_window_from_frame(window);
			popup.addNextButton(function() {
				popup.freeze("Checking information...");
				var people = [];
				var error = null;
				for (var i = 0; i < structure.length; ++i) {
					var p = {path:structure[i].path};
					error = structure[i].validate();
					if (error != null) break;
					p.value = structure[i].getValue();
					p.columns = typeof structure[i].columns != 'undefined' ? structure[i].columns : [];
					people.push(p);
				}
				if (error != null) {
					alert("Please correct the data before to continue: "+error);
					popup.unfreeze();
					return;
				}
				popup.removeAllButtons();
				var data = {peoples:[people]};
				<?php 
				if (isset($_GET["ondone"])) echo "data.ondone = ".json_encode($_GET["ondone"]).";";
				else if (isset($_GET["donotcreate"])) echo "data.donotcreate = ".json_encode($_GET["donotcreate"]).";";
				?>
				postData("popup_create_people_step_check", data, window);
			});
			popup.addCancelButton();
			</script>
			<?php
		} 
	}
	
}
?>