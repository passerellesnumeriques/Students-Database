<?php 
function createMultipleDataPage(&$page, $root_table_name, $sub_model, $sub_models, $fixed_columns, $fixed_data, $prefilled_columns, $prefilled_data, $precreated_data = null) {
$all_sub_models = array();
if ($sub_model <> null) {
	$root_table = DataModel::get()->getTable($root_table_name);
	if ($root_table->getModel() instanceof SubDataModel)
		$all_sub_models[$root_table->getModel()->getParentTable()] = $sub_model;
}
if ($sub_models <> null)
	foreach ($sub_models as $k=>$sm)
		if (!isset($all_sub_models[$k])) $all_sub_models[$k] = $sm;

$known_columns = array();
foreach ($fixed_columns as $fc) {
	if (!isset($known_columns[$fc["table"]])) $known_columns[$fc["table"]] = array();
	$known_columns[$fc["table"]][$fc["column"]] = $fc["value"];
}
foreach ($fixed_data as $fd) {
	$display = DataModel::get()->getTableDataDisplay($fd["table"]);
	if ($display == null) continue;
	$table = $display->getTable();
	$sm = null;
	if ($table->getModel() instanceof SubDataModel)
		$sm = @$all_sub_models[$table->getModel()->getParentTable()];
	$data = $display->getDataDisplayByName($fd["data"], null, $sm);
	if ($data == null) continue;
	$cell = $data->getSingleCell();
	if ($cell == null) continue;
	if (!isset($known_columns[$cell["table"]])) $known_columns[$cell["table"]] = array();
	$known_columns[$cell["table"]][$cell["column"]] = $fd["value"];
}
$fields = PNApplication::$instance->data_model->getAvailableFields($root_table_name, $sub_model, false, $sub_models, $known_columns, true);

// remove non editable, and not fixed
for ($i = 0; $i < count($fields); $i++) {
	$data = $fields[$i][0];
	$is_fixed = false;
	foreach ($fixed_data as $fd) {
		if ($fd["table"] == $data->getTableName() && $fd["data"] == $data->getDisplayName()) {
			$is_fixed = true;
			break;
		}
	}
	if ($is_fixed) continue;
	if ($precreated_data <> null) {
		// check in the precreated data
		foreach ($precreated_data as $pc)
			foreach ($pc as $pcd)
				if ($pcd["category"] == $data->getCategoryName() && $pcd["data"] == $data->getDisplayName() && isset($pcd["forced"]) && $pcd["forced"]) {
					$is_fixed = true;
					break;
				}
		if ($is_fixed) continue;
	}
	if (!$data->isEditableForNewData()) {
		array_splice($fields, $i, 1);
		$i--;
	}
}

$page->requireJavascript("typed_field.js");
foreach ($fields as $field) {
	$tf = $field[0]->getTypedField($field[1]->sub_model);
	$page->requireJavascript($tf[0].".js");
}
$page->requireJavascript("field_html.js");
$page->requireJavascript("datadisplay.js");
	
$page->requireJavascript("grid.js");
$grid_id = $page->generateID();
echo "<div id='$grid_id'></div>";
?>
<script type='text/javascript'>
var grid = new grid('<?php echo $grid_id;?>');

function datachanged(field) {
	var row = grid.getContainingRow(field.getHTMLElement());
	if (row._is_new) {
		row._is_new = false;
		addRow();
	}
}

var col, col_container, sub_cols;
col = new GridColumn("remove", "", null, null, "field_html", false, null, null, {}, null);
grid.addColumn(col);
<?php 
foreach ($fields as $field) {
	/* @var $data \datamodel\DataDisplay */
	$data = $field[0];
	$path = $field[1];
	$tf = $data->getTypedField($path->sub_model);
	$fixed = false;
	foreach ($fixed_data as $fd)
		if ($fd["table"] == $data->getTableName() && $fd["data"] == $data->getDisplayName()) {
			$fixed = true;
			break;
		}
	if (!$fixed && $data->getSingleCell() <> null) {
		$cell = $data->getSingleCell();
		foreach ($fixed_columns as $fc)
			if ($fc["table"] == $cell["table"] && $fc["column"] == $cell["column"]) {
				$fixed = true;
				break;
			}
	}
	$sub_data = $data->getSubDataDisplay();
	if ($sub_data == null) {
		echo "col = new GridColumn(".
			json_encode($path->getString()."#".$data->getDisplayName()).
			",".json_encode($data->getDisplayName()).
			",null".
			",null".
			",".json_encode($tf[0]).
			",".($fixed || !$data->isEditableForNewData() ? "false" : "true").
			",datachanged,null".
			",".$tf[1].
			",".$data->javascriptDataDisplay($path->sub_model).");\n";
		echo "grid.addColumn(col);\n";
	} else {
		echo "sub_cols = [];";
		$names = $sub_data->getDisplayNames();
		for ($i = 0; $i < count($names); $i++) {
			echo "col = new GridColumn(".
				json_encode($path->getString()."#".$data->getDisplayName()."#".$i).
				",".json_encode($names[$i]).
				",null".
				",null".
				",".json_encode($tf[0]).
				",".($fixed ? "false" : "true").
				",datachanged,null".
				",".$tf[1].
				",{datadisplay:".$data->javascriptDataDisplay($path->sub_model).",sub_data:$i});\n";
			echo "col.field_args.sub_data_index = $i;\n";
			echo "sub_cols.push(col);";
		}
		echo "col_container = new GridColumnContainer(".json_encode($data->getDisplayName()).",sub_cols,".$data->javascriptDataDisplay($path->sub_model).");\n";
		echo "grid.addColumnContainer(col_container);\n";
	}
}
?>
var fields = [<?php
$first = true;
foreach ($fields as $field) {
	if ($first) $first = false; else echo ",";
	echo $field[0]->javascriptDataDisplay($field[1]->sub_model);
} 
?>];
var paths = [<?php
$first = true;
foreach ($fields as $field) {
	if ($first) $first = false; else echo ",";
	echo "new DataPath(".json_encode($field[1]->getString()).")";
} 
?>];
var values = [<?php
$first = true; 
foreach ($fields as $field) {
	$data = $field[0];
	$path = $field[1];
	if ($first) $first = false; else echo ",";
	$value = null;
	$ok = false;
	foreach ($fixed_data as $fd)
		if ($fd["table"] == $data->getTableName() && $fd["data"] == $data->getDisplayName()) {
			$value = $fd["value"];
			$ok = true;
			break;
		}
	if (!$ok) {
		foreach ($prefilled_data as $pd) {
			if ($pd["table"] == $data->getTableName() && $pd["data"] == $data->getDisplayName()) {
				$value = $pd["value"];
				$ok = true;
				break;
			}
		}
	}
	if (!$ok) {
		$cell = $data->getSingleCell();
		if ($cell <> null) {
			foreach ($fixed_columns as $fc)
				if ($fc["table"] == $cell["table"] && $fc["column"] == $cell["column"]) {
					$value = $fc["value"];
					$ok = true;
					break;
				}
			if (!$ok) {
				foreach ($prefilled_columns as $fc)
					if ($fc["table"] == $cell["table"] && $fc["column"] == $cell["column"]) {
					$value = $fc["value"];
					$ok = true;
					break;
				}
			}
		}
	}
	if (!$ok) $value = $data->getNewData();
	echo json_encode($value);
}
?>];
var row_id_counter = 0;
function addRow() {
	var row_id = row_id_counter++;
	var row_data = [];
	row_data.push({
		col_id: "remove",
		data_id: null,
		data: "<button class='flat small_icon' onclick='removeRowButton(this);'><img src='"+theme.icons_16.remove+"'/></button> <span style='white-space:nowrap'"+(grid.getNbRows()+1)+"-</span>"
	});
	for (var i = 0; i < fields.length; ++i) {
		if (fields[i].sub_data == null) {
			var data = {};
			data.col_id = paths[i].path+"#"+fields[i].name;
			data.data_id = null;
			data.data = objectCopy(values[i],20);
			row_data.push(data);
		} else {
			var same_data = objectCopy(values[i],20);
			for (var j = 0; j < fields[i].sub_data.names.length; ++j) {
				var data = {};
				data.col_id = paths[i].path+"#"+fields[i].name+"#"+j;
				data.data_id = null;
				data.data = same_data;
				row_data.push(data);
			}
		}
	}
	var row = grid.addRow(row_id, row_data);
	row._is_new = true;
	layout.invalidate(document.body);
}
function removeRowButton(button) {
	var row = grid.getContainingRow(button);
	if (row._is_new) return;
	var index = grid.getRowIndex(row);
	grid.removeRow(row);
	while (index < grid.getNbRows()) {
		grid.getCellField(index,0).setData("<span style='white-space:nowrap'><img src='"+theme.icons_16.remove+"' style='vertical-align:middle' class='button_verysoft' onclick='removeRowButton(this);'/> "+(index+1)+"-</span>");
		index++;
	}
}
addRow();
<?php if ($precreated_data <> null && count($precreated_data) > 0) { ?>
var precreated = <?php echo json_encode($precreated_data);?>;
for (var i = 0; i < precreated.length; ++i) {
	for (var j = 0; j < precreated[i].length; ++j) {
		var d = precreated[i][j];
		var field_index = -1;
		for (var k = 0; k < fields.length; ++k)
			if (fields[k].category == d.category && fields[k].name == d.data) { field_index = k; break; }
		if (field_index < 0) continue;
		var f = grid.getCellFieldById(i, paths[field_index].path+"#"+fields[field_index].name);
		if (!f) continue;
		f.setData(d.value);
		if (d.forced) f.setEditable(false);
	}
}
<?php } ?>
</script>
<?php 
}
?>