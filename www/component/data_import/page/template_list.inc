<?php
function template_list(&$page, $type, $root_table, $sub_model, $known_columns, $import_url, $import_data, $onimportdone) {
	$templates = PNApplication::$instance->data_import->getTemplates($type, $sub_model);
	if (count($templates) == 0) {
		echo "<div style='text-align:center;font-style:italic'>There is no template yet</div>";
	} else {
		echo "<ul style='padding-left:20px'>";
		foreach ($templates as $t) {
			echo "<li style='white-space:nowrap'>";
			echo toHTML($t["name"]);
			echo " (";
			switch ($t["template_type"]) {
				case "multiple": echo "multiple applicants, one by row"; break;
				case "single": echo "single applicant"; break;
			}
			echo ")";
			echo " ";
			echo "<button class='flat icon' title='Import data using this template' onclick='useTemplate(event,".$t["id"].")'><img src='/static/data_import/import_excel_16.png'/></button>";
			echo " ";
			echo "<button class='flat icon' title='Edit this template' onclick='editTemplate(".$t["id"].")'><img src='".theme::$icons_16["edit"]."'/></button>";
			echo " ";
			echo "<button class='flat icon' title='Remove this template' onclick='removeTemplate(this,".$t["id"].");'><img src='".theme::$icons_16["remove"]."'/></button>";
			echo "</li>";
		}
		echo "</ul>";
?>
<script type='text/javascript'>
var templates = [<?php
if (count($templates) > 0) {
	$multiple_ids = array();
	$single_ids = array();
	foreach ($templates as $t)
		switch ($t["template_type"]) {
		case "multiple": array_push($multiple_ids, $t["id"]); break;
		case "single": array_push($single_ids, $t["id"]); break;
		}
	$first_template = true;
	if (count($multiple_ids) > 0) {
		$data = SQLQuery::create()->bypassSecurity()->select("DataImportTemplateMultiple")->whereIn("DataImportTemplateMultiple","template",$multiple_ids)->execute();
		foreach ($templates as $t) {
			if ($t["template_type"] <> "multiple") continue;
			if ($first_template) $first_template = false; else echo ",";
			echo "{";
			echo "id:".$t["id"];
			echo ",name:".json_encode($t["name"]);
			echo ",type:'multiple'";
			echo ",to_import:[";
			$first_data = true;
			foreach ($data as $d) {
				if ($d["template"] <> $t["id"]) continue;
				if ($first_data) $first_data = false; else echo ",";
				echo "{";
				echo "data:".json_encode($d["data_name"]);
				echo ",path:".json_encode($d["data_path"]);
				if ($d["sub_index"] !== null) echo ",sub_index:".$d["sub_index"];
				if ($d["index"] !== null) echo ",index:".$d["index"];
				if ($d["column"] !== null) {
					echo ",sheet_name:".json_encode($d["sheet"]);
					echo ",column:".$d["column"];
					echo ",row_start:".$d["row_start"];
				} else {
					echo ",value:".$d["value"];
				}
				echo "}";
			}
			echo "]";
			echo "}";
		}
	}
	if (count($single_ids) > 0) {
		// TODO
	}
} 
?>];
function editTemplate(id) {
	var temp = null;
	for (var i = 0; i < templates.length; ++i)
		if (templates[i].id == id) { temp = templates[i]; break; }
	popup.freeze();
	require("edit_template.js",function() {
		new edit_template('container', <?php echo json_encode($type);?>, <?php echo json_encode($root_table);?>, <?php echo json_encode($sub_model);?>, <?php echo $known_columns;?>, temp, function() {
			popup.unfreeze();
		});
	});
}
function removeTemplate(button,id) {
	var temp = null;
	for (var i = 0; i < templates.length; ++i)
		if (templates[i].id == id) { temp = templates[i]; break; }
	confirmDialog("Are you sure to remove the template "+temp.name+" ?",function(yes) {
		if (!yes) return;
		popup.freeze("Removing template...");
		service.json("data_import","remove_template",{id:id},function(res) {
			popup.unfreeze();
			if (!res) return;
			var li = button.parentNode;
			var ul = li.parentNode;
			ul.removeChild(li);
			layout.changed(ul);
		});
	});
}
window.top.require("popup_window.js");
window.top.require("excel_import.js");
function useTemplate(ev,id) {
	var temp = null;
	for (var i = 0; i < templates.length; ++i)
		if (templates[i].id == id) { temp = templates[i]; break; }
	window.top.require("popup_window.js", function() {
		var container = document.createElement("DIV");
		container.style.width = "100%";
		container.style.height = "100%";
		var p = new window.top.popup_window("Import Applicants", theme.icons_16._import, container);
		window.top.require("excel_import.js", function() {
			new window.top.excel_import(p, container, function(imp) {
				p.showPercent(95,95);
				imp.init();
				imp.loadImportDataURL(<?php echo json_encode($import_url);?>+"&root_table=<?php echo urlencode($root_table);?><?php if ($sub_model <> null) echo "&sub_model=".$sub_model;?>&ondone=import_done&multiple="+(temp.type == "multiple" ? "true" : "false"), <?php echo $import_data;?>);
				imp.frame_import.import_done = window.frameElement.<?php echo $onimportdone;?>;
				if (temp.type == "multiple")
					imp._prepareExcel = function(ondone) {
						waitFrameContentReady(this.frame_import, function(win) { return typeof win.grid != 'undefined'; }, function() {
							p.setFreezeContent("Importing data using template...");
							var w = getIFrameWindow(imp.frame_excel);
							var xl = w.excel;
							w = getIFrameWindow(imp.frame_import);
							var grid = w.grid;
							var errors = [];
							var fixed_values = [];
							var next = function(i) {
								if (i >= temp.to_import.length) {
									if (errors.length == 1)
										errorDialog(errors[0]);
									else if (errors.length > 1) {
										var div = document.createElement("DIV");
										div.appendChild(document.createTextNode("Several errors occured while importing data from Excel file, using template "+temp.name+":"));
										var ul = document.createElement("UL"); div.appendChild(ul);
										for (var j = 0; j < errors.length; ++j) {
											var li = document.createElement("LI"); ul.appendChild(li);
											li.appendChild(document.createTextNode(errors[j]));
										}
										errorDialogHTML(div);
									}
									for (i = 0; i < fixed_values.length; ++i) {
										var e = fixed_values[i];
										var col_id = e.path+"#"+e.data;
										if (typeof e.sub_index != 'undefined') col_id += "#"+e.sub_index;
										var field_index = grid.getColumnIndexById(col_id);
										var nb = grid.getNbRows()-1;
										for (var row = 0; row < nb; ++row) {
											var f = grid.getCellField(row, field_index);
											if (typeof e.index != 'undefined') {
												if (f.getNbData() > e.index)
													f.setDataIndex(e.index, e.value, true);
											} else
												f.setData(e.value, true);
										}
									}
									ondone();
									return;
								}
								var e = temp.to_import[i];
								var col_id = e.path+"#"+e.data;
								if (typeof e.sub_index != 'undefined') col_id += "#"+e.sub_index;
								var field_index = grid.getColumnIndexById(col_id);
								if (field_index < 1) {
									errors.push("Data "+e.data+" cannot be imported because we cannot find the corresponding column in the data to import");
									setTimeout(function(){next(i+1);},1);
									return;
								}
								if (typeof e.value != 'undefined') {
									fixed_values.push(e);
									setTimeout(function(){next(i+1);},1);
									return;
								}
								var sheet = null;
								if (xl.sheets.length == 1) sheet = xl.sheets[0];
								else for (var j = 0; j < xl.sheets.length; ++j) if (xl.sheets[j].name == e.sheet_name) { sheet = xl.sheets[j]; break; }
								if (!sheet) {
									var err = "Sheet "+e.sheet_name+" not found in Excel file";
									if (!errors.contains(err)) errors.push(err);
									setTimeout(function(){next(i+1);},1);
									return;
								}
								if (e.column >= sheet.columns.length) {
									errors.push("Data "+e.data+" cannot be imported because column "+xl.getExcelColumnName(e.column)+" does not exist in sheet "+sheet.name);
									setTimeout(function(){next(i+1);},1);
									return;
								}
								if (e.row_start >= sheet.rows.length) {
									errors.push("Data "+e.data+" cannot be imported because there are only "+sheet.rows.length+" row(s) in sheet "+sheet.name+", and it is expected to start at row "+(e.row_start+1));
									setTimeout(function(){next(i+1);},1);
									return;
								}
								var range = {start_col:e.column,end_col:e.column,start_row:e.row_start,end_row:sheet.rows.length-1};
								var where;
								if (typeof e.index != 'undefined') {
									if (typeof e.sub_index != 'undefined') {
										where = {type:'set',row:0,index:e.index,sub_index:e.sub_index};
									} else {
										where = {type:'set',row:0,index:e.index};
									}
								} else if (typeof e.sub_index != 'undefined') {
									where = {type:'set',row:0,sub_index:e.sub_index};
								} else {
									where = {type:'reset',row:0};
								}
								p.setFreezeContent("Importing data <b>"+e.data+"</b> using the template...");
								setTimeout(function(){
									if (imp.frame_import) {
										var w = getIFrameWindow(imp.frame_import);
										if (w && w.layout) w.layout.pause();
									}
									imp._importData(sheet, range, field_index, where, function() {
										if (imp.frame_import) {
											var w = getIFrameWindow(imp.frame_import);
											if (w && w.layout) w.layout.resume();
										}
										next(i+1);
									});
								},1);
							};
							setTimeout(function(){next(0);},1);
						});
					};
				imp.uploadFile(ev);
			});
		});
	});
}
</script>
<?php 
	}
} 
?>