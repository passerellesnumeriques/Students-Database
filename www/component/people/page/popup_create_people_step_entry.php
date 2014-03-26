<?php 
class page_popup_create_people_step_entry extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$input = json_decode($_POST["input"], true);
		
		if (isset($input["multiple"]) && $input["multiple"] == "true") {
			require_once("component/data_model/page/create_multiple_data.inc");
			$fixed_columns = $input["fixed_columns"];
			$fixed_data = $input["fixed_data"];
			$prefilled_columns = $input["prefilled_columns"];
			$prefilled_data = $input["prefilled_data"];
				
			createMultipleDataPage($this, "People", null, @$input["sub_models"], $fixed_columns, $fixed_data, $prefilled_columns, $prefilled_data);
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
							ppath = {path:path,columns:{},value:[]};
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
				data.sub_models = <?php echo json_encode(@$input["sub_models"]);?>;
				data.multiple = true;
				<?php 
				if (isset($input["ondone"])) echo "data.ondone = ".json_encode($input["ondone"]).";";
				else if (isset($input["donotcreate"])) echo "data.donotcreate = ".json_encode($input["donotcreate"]).";";
				?>
				postData("popup_create_people_step_check", data, window);
			});
			popup.addCancelButton();
			</script>
			<?php
		} else {
			require_once("component/data_model/page/create_data.inc");
			$values = new DataValues();
			foreach ($input["fixed_columns"] as $v)
				$values->addTableColumnValue($v["table"], $v["column"], $v["value"]);
			foreach ($input["fixed_data"] as $v)
				$values->addTableDataValue($v["table"], $v["data"], $v["value"]);
			$prefilled_values = new DataValues();
			foreach ($input["prefilled_data"] as $v)
				$prefilled_values->addTableDataValue($v["table"], $v["data"], $v["value"]);
			
			$structure_name = createDataPage($this, "People", null, @$input["sub_models"], $values, $prefilled_values);
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
				data.sub_models = <?php echo json_encode(@$input["sub_models"]);?>;
				<?php 
				if (isset($input["ondone"])) echo "data.ondone = ".json_encode($input["ondone"]).";";
				else if (isset($input["donotcreate"])) echo "data.donotcreate = ".json_encode($input["donotcreate"]).";";
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