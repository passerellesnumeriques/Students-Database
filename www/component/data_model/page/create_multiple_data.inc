<?php 
function createMultipleDataPage(&$page, $root_table_name, $sub_model, $sub_models, $fixed_columns, $fixed_data, $prefilled_columns, $prefilled_data, $precreated_data = null) {
require_once("component/data_model/Model.inc");
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
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div style='flex:none;background-color:white;box-shadow: 1px 2px 5px 0px #808080;margin-bottom:3px;padding:2px' class='grid_font'>
		Customize this screen: 
		<button class='flat grid_font' onclick='showHideColumns(this);'>
			<img src='/static/data_model/table_column.png'/>
			Hide/Show columns
		</button>
	</div>
	<div id='<?php echo $grid_id;?>' style='flex:1 1 auto'></div>
</div>
<script type='text/javascript'>
var grid = new grid('<?php echo $grid_id;?>');
grid.makeScrollable();

function showHideColumns(button) {
	grid.showHideColumnsMenu(button, function() {
		grid.saveState();
	});
}

function datachanged(field) {
	var row = grid.getContainingRow(field.getHTMLElement());
	if (row._is_new) {
		row._is_new = false;
		addRow();
	}
}

function addColumnActions(col) {
	var needFieldInfo = function() {
		if (typeof window[col.field_type].prototype.helpFillMultipleItems != 'undefined') {
			var f = new window[col.field_type](col.attached_data.new_data,true,col.field_args);
			var helper = f.helpFillMultipleItems();
			col.addAction(new GridColumnAction('set_all',theme.icons_10.pen,function(ev,action,col) {
				require("mini_popup.js",function() {
					var p = new mini_popup(helper.title);
					p.content.appendChild(helper.content);
					var apply = function() {
						if (grid.getNbRows() == 1)
							helper.apply(grid.getCellField(0,grid.getColumnIndex(col)))
						else {
							var col_index = grid.getColumnIndex(col);
							for (var i = 0; i < grid.getNbRows()-1; ++i)
								helper.apply(grid.getCellField(i,col_index));
						}
					};
					if (helper.validated)
						helper.validated.addListener(function() { apply(); p.close(); });
					else
						p.addOkButton(function() {
							apply();
							return true;
						});
					p.showBelowElement(action.element);
				});
			},helper.title));
		}
		if (typeof window[col.field_type].prototype.createValue != 'undefined') {
			var f = new window[col.field_type](col.attached_data.new_data,true,col.field_args);
			col.addAction(new GridColumnAction('create_value',theme.icons_10.add,function(ev,action,col) {
				f.createValue(null,col.title);
			},"Create new "+col.title));
		}
	};
	if (col._loaded)
		needFieldInfo();
	else
		col.onloaded.addListener(needFieldInfo);
}
function addColumnContainerActions(col,field_type,field_args) {
	require([["typed_field.js",field_type+".js"]], function() {
		if (typeof window[field_type].prototype.helpFillMultipleItemsForAllSubData != 'undefined') {
			var f = new window[field_type](col.attached_data.new_data,true,field_args);
			var helper = f.helpFillMultipleItemsForAllSubData();
			col.addAction(new GridColumnAction('set_all',theme.icons_10.pen,function(ev,action,col) {
				require("mini_popup.js",function() {
					var p = new mini_popup(helper.title);
					p.content.appendChild(helper.content);
					p.addOkButton(function() {
						var col_index = grid.getColumnIndex(col.sub_columns[0]);
						var nb = grid.getNbRows();
						if (nb > 1) nb--;
						for (var row = 0; row < nb; ++row) {
							var fields = [];
							for (var sub_col = 0; sub_col < col.sub_columns.length; ++sub_col)
								fields.push(grid.getCellField(row,col_index+sub_col));
							helper.apply(fields);
						}
						return true;
					});
					p.showBelowElement(action.element);
				});
			},helper.title));
		}
	});
}

var col, col_container, sub_cols;
col = new GridColumn("remove", "", null, null, "field_html", false, null, null, {}, null);
grid.addColumn(col);
var cat_col, cols, title;
<?php 
$by_cat = array();
foreach ($fields as $field) {
	$cat = $field[0]->getCategory();
	if (!isset($by_cat[$cat->getName()])) $by_cat[$cat->getName()] = array($cat,array());
	array_push($by_cat[$cat->getName()][1], $field); 
}
foreach ($by_cat as $cat_name=>$cat) {
echo "cols = [];\n";
foreach ($cat[1] as $field) {
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
	$sub_indexes = array();
	if ($sub_data <> null) {
		$names = $sub_data->getDisplayNames();
		for ($i = 0; $i < count($names); $i++)
			if ($sub_data->isEditableForNew($i))
				array_push($sub_indexes, $i);
	}
	if (count($sub_indexes) == 0) {
		echo "cols.push(col = new GridColumn(".
			json_encode($path->getString()."#".$data->getDisplayName()).
			",".json_encode($data->getDisplayName()).
			",null".
			",null".
			",".json_encode($tf[0]).
			",".($fixed || !$data->isEditableForNewData() ? "false" : "true").
			",datachanged,null".
			",".$tf[1].
			",".$data->javascriptDataDisplay($path->sub_model).")";
		echo ");\n";
		echo "addColumnActions(col);\n";
	} else {
		echo "sub_cols = [];\n";
		$names = $sub_data->getDisplayNames();
		foreach ($sub_indexes as $i) {
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
			echo "sub_cols.push(col);\n";
			echo "addColumnActions(col);\n";
		}
		echo "col_container = new GridColumnContainer(".json_encode($data->getDisplayName()).",sub_cols,".$data->javascriptDataDisplay($path->sub_model).");\n";
		echo "cols.push(col_container);\n";
		echo "addColumnContainerActions(col_container,".json_encode($tf[0]).",".$tf[1].");\n";
	}
}
echo "title = document.createElement('DIV');\n";
echo "title.style.display = 'inline-block';\n";
echo "title.innerHTML = \"".($cat[0]->getIcon() <> null ? "<img src='".$cat[0]->getIcon()."' style='vertical-align:bottom'/> " : "").toHTML($cat[0]->getName())."\";\n";
echo "col_container = new GridColumnContainer(title,cols,null);\n";
echo "col_container.text_title = ".json_encode($cat[0]->getName()).";\n";
echo "grid.addColumnContainer(col_container);\n";
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
		data: "<span style='white-space:nowrap'><button class='flat small_icon' onclick='removeRowButton(this);' tabindex='-1' style='vertical-align:middle'><img src='"+theme.icons_16.remove+"'/></button>"+(grid.getNbRows()+1)+"-</span>"
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
	layout.changed(document.body);
}
function removeRowButton(button) {
	var row = grid.getContainingRow(button);
	if (row._is_new) return;
	var index = grid.getRowIndex(row);
	grid.removeRow(row);
	while (index < grid.getNbRows()) {
		grid.getCellField(index,0).setData("<span style='white-space:nowrap'><button class='flat small_icon' onclick='removeRowButton(this);' style='vertical-align:middle'><img src='"+theme.icons_16.remove+"'/></button>"+(index+1)+"-</span>");
		index++;
	}
}
addRow();
<?php if ($precreated_data <> null && count($precreated_data) > 0) { ?>
var precreated = <?php echo json_encode($precreated_data);?>;
var initPreCreated = function(i) {
	if (i >= precreated.length) return;
	grid.onallrowsready(function() {
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
		setTimeout(function(){initPreCreated(i+1)},1);
	});
};
initPreCreated(0);
<?php } ?>
grid.loadState();
</script>
<?php 
}
?>