<?php 
class page_build_excel_import extends Page {
	
	public function get_required_rights() { return array(); }
	// TODO have a system to allow creating this
	
	protected function execute() {
		require_once("component/widgets/page/wizard.inc");
		$page_type = $_GET["import"];
		if (!isset($_GET["page"])) {
			$url = "/dynamic/data_import/page/build_excel_import?page=select_file&import=".$page_type;
			if (isset($_GET["root_table"]))
				$url .= "&root_table=".$_GET["root_table"];
			create_wizard_page($this, "/static/data_import/import_excel_32.png", $page_type == "create_template" ? "Create Excel Import Template" : "Import Excel", $url);
			if (isset($_POST["preset"])) {?><script type='text/javascript'>window.import_preset = <?php echo json_encode($_POST["preset"]);?>;</script><?php }
		} else switch ($_GET["page"]) {
			case "select_file":
				?>
				<form action="?page=upload&import=<?php echo $page_type;?>" method="POST" enctype="multipart/form-data" name='excel_example' style='padding:5px'>
				<?php if (!isset($_GET["root_table"])) {?>
				Which kind of data do you want to import ?<br/>
				<select name='root_table'>
				<?php
				require_once("component/data_model/Model.inc");
				foreach (DataModel::get()->getTables() as $table) {
					if ($table->getDisplayName() == null) continue;
					echo "<option value=\"".$table->getName()."\">".$table->getDisplayName()."</option>";
				} 
				?>
				</select>
				<br/><br/>
				<?php } else {?>
				<input type='hidden' name='root_table' value='<?php echo $_GET["root_table"];?>'/>
				<?php } ?>
				<?php
				if ($page_type == "create_template")
					echo "Upload an example of file to define the template:";
				else
					echo "Upload the file to import:";  
				?>
				<br/>
				<input type='file' name='excel' onchange="window.parent.enable_wizard_page_next(this.value.length >0);"/>
				<br/>
				Supported formats are: Excel (xls, xlsx), OpenOffice (odf), Sylk (slk), Gnumeric, CSV
				</form>
				<script type='text/javascript'>
				window.parent.wizard_page_loaded();
				function wizard_page_go_next() {
					window.parent.wizard_freeze_message("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Uploading file...");
					document.forms["excel_example"].submit();
				}
				</script>
				<?php
				break;
			case "upload":
				if (!isset($_FILES["excel"]) || $_FILES["excel"]['error'] <> UPLOAD_ERR_OK) {
					echo "Error uploading file (".(isset($_FILES["excel"]) ? $_FILES["excel"]['error'] : "no file received").").";
					echo "<script type='text/javascript'>window.parent.enable_wizard_page_previous(true);function wizard_page_go_previous() { location.href='?page=select_file'; }</script>";
				} else {
					require_once("component/lib_php_excel/PHPExcel.php");
					try {
						$reader = PHPExcel_IOFactory::createReaderForFile($_FILES["excel"]['tmp_name']);
						if (get_class($reader) == "PHPExcel_Reader_HTML") throw new Exception();
						$excel = $reader->load($_FILES["excel"]['tmp_name']);
					} catch (Exception $e) {
						PNApplication::error("Invalid file format");
						return;
					}
					$this->add_javascript("/static/excel/excel.js");
					$this->add_javascript("/static/widgets/splitter_vertical/splitter_vertical.js");
					$this->onload("init_page();");
					?>
					<div id='excel_template_page' style='width:100%;height:100%'>
						<div id='excel'></div>
						<iframe src='/dynamic/data_import/page/build_excel_import?page=how_are_data&root_table=<?php echo $_POST["root_table"];?>&import=<?php echo $page_type;?>' frameBorder=0 style='border:none'></iframe>
					</div>
					<script type='text/javascript'>
					window.parent.wizard_page_loaded();
					window.parent.enable_wizard_page_previous(true);
					function wizard_page_go_previous() {
						location.href = '?page=select_file&import=<?php echo $page_type;?>';
					}
					function init_page() {
						new splitter_vertical('excel_template_page',0.65);
						window.excel = new Excel('excel', function(xl) {
							<?php
							foreach ($excel->getWorksheetIterator() as $sheet) {
								$cols = 0;
								while ($sheet->cellExistsByColumnAndRow($cols, 1)) $cols++;
								$rows = 0;
								foreach ($sheet->getRowIterator() as $row) {
									$rows++;
									$it = $row->getCellIterator();
									$it->setIterateOnlyExistingCells(false);
									$c = 0;
									foreach ($it as $col) $c++;
									if ($c > $cols) $cols = $c;
								}
								//while ($sheet->cellExistsByColumnAndRow(0, $rows+1)) $rows++;
								echo "xl.addSheet(".json_encode($sheet->getTitle()).",null,".$cols.",".$rows.",function(sheet){\n";
								for ($i = 0; $i < $cols; $i++) {
									$col = $sheet->getColumnDimensionByColumn($i);
									if ($col == null) $w = $sheet->getDefaultColumnDimension()->getWidth();
									else {
										$w = $col->getWidth();
										if (floor($w) == -1) $w = $sheet->getDefaultColumnDimension()->getWidth();
									}
									if (floor($w) == -1) $w = 5;
									$w *= 10;
									if ($w < 10) $w = 10;
									echo "\tsheet.getColumn(".$i.").setWidth(".floor($w+1).");\n";
								}
								for ($i = 0; $i < $rows; $i++) {
									$row = $sheet->getRowDimension($i+1);
									if ($row == null) $h = $sheet->getDefaultRowDimension()->getRowHeight();
									else {
										$h = $row->getRowHeight();
										if ($h == -1) $h = $sheet->getDefaultRowDimension()->getRowHeight();
									}
									if ($h == -1 || $h == 0) $h = 20;
									if ($h < 2) $h = 2;
									echo "\tsheet.getRow(".$i.").setHeight(".floor($h+1).");\n";
								}
								echo "\tvar c;";
								for ($col = 0; $col < $cols; $col++) {
									for ($row = 0; $row < $rows; $row++) {
										try {
											$cell = $sheet->getCellByColumnAndRow($col, $row+1);
											$val = $cell->getFormattedValue();
										} catch (Exception $e) {
											$val = "ERROR: ".$e->getMessage();
										}
										echo "\tc = sheet.getCell(".$col.",".$row.");";
										echo "c.setValue(".json_encode("".$val).");";
										$style = $sheet->getStyleByColumnAndRow($col, $row+1);
										echo "c.setStyle({";
										echo "overflow:'hidden'";
										if ($style <> null) {
											$font = $style->getFont();
											if ($font <> null) {
												if ($font->getName() <> null) echo ",fontFamily:".json_encode($font->getName());
												if ($font->getBold()) echo ",fontWeight:'bold'";
												if ($font->getItalic()) echo ",fontStyle:'italic'";
												if ($font->getColor() <> null) echo ",color:'#".$font->getColor()->getRGB()."'";
												if ($font->getSize() <> null) echo ",fontSize:'".floor($font->getSize())."pt'";
											}
											if ($style->getFill() <> null && $style->getFill()->getFillType() == PHPExcel_Style_Fill::FILL_SOLID && $style->getFill()->getStartColor() <> null)
												echo ",backgroundColor:'#".$style->getFill()->getStartColor()->getRGB()."'";
										}
										echo "});\n";
									}
								}
								echo "});\n";
							} 
							?>
						});
					}							
					</script>
					<?php
				}
				break;
			case "how_are_data":
				?>
				<form action='?page=select_fields&import=<?php echo $page_type;?>' method='post' name='import_type'>
				<input type='hidden' name='root_table' value='<?php echo $_GET["root_table"];?>'/>
				How are data in the Excel ?<br/>
				<input type='radio' name='import_type' value='by_row' onchange='import_type_selected();'/> Multiple entries, one entry per row (specify only column for each information)<br/>
				<input type='radio' name='import_type' value='multiple' onchange='import_type_selected();'/> Multiple entries, not per row (specify ranges where to find information)<br/>
				<input type='radio' name='import_type' value='single' onchange='import_type_selected();'/> Only one entry (specify the cell for each information)<br/>
				</form>
				<script type='text/javascript'>
				window.parent.parent.wizard_page_loaded();
				window.parent.parent.enable_wizard_page_previous(true);
				function import_type_selected() {
					var form = document.forms['import_type'];
					var radio = form.elements['import_type'];
					var selected = false;
					for (var i = 0; i < radio.length; ++i)
						if (radio[i].checked) selected = true;
					window.parent.parent.enable_wizard_page_next(selected);
				}
				var w=window;
				window.parent.wizard_page_go_next = function() {
					var form = w.document.forms['import_type'];
					form.submit();
				};
				</script>
				<?php
				break;
			case "select_fields":
				?>
				<script type='text/javascript'>
				window.parent.parent.wizard_page_loaded();
				window.parent.parent.enable_wizard_page_previous(true);
				window.parent.wizard_page_go_previous = function() {
					var xl = window.parent.excel;
					for (var i = 0; i < xl.sheets.length; ++i)
						while (xl.sheets[i].layers.length > 0)
							xl.sheets[i].removeLayer(xl.sheets[i].layers[0]);
					location.href = '?page=how_are_data&import=<?php echo $page_type;?>&root_table=<?php echo $_POST["root_table"];?>';
				};
				</script>
				<?php
				require_once("component/data_model/DataPath.inc");
				$ctx = new DataPathBuilderContext();
				$paths = DataPathBuilder::search_from($ctx, $_POST["root_table"]);
				switch ($_POST["import_type"]) {
					case "by_row":
						?>
						<div style='padding:2px'>
						<form name='fields_selection'>
						Data start at row (not including headers): <input type='text' name='start_row' value='' size=5 onchange='row_start_changed(this.value);'/><br/>
						Data end at row: <input type='text' name='end_row' value='' size=5 onchange='row_end_changed(this.value);'/><br/>
						Column per information:<br/>
						<?php
						$this->add_javascript("/static/widgets/collapsable_section/collapsable_section.js");
						$cats = array();
						foreach ($paths as $path) {
							if (!$path->is_unique()) continue;
							$cat = $path->table->getDisplayableDataCategory($path->field_name);
							if (isset($cats[$cat]))
								array_push($cats[$cat], $path);
							else
								$cats[$cat] = array($path);
						}
						$i_cat = 0;
						$i_field = 0;
						foreach ($cats as $cat=>$paths) {
							echo "<div id='fields_cat_".$i_cat."' style='border: 1px solid black'>";
							$this->onload("new collapsable_section('fields_cat_".$i_cat."');");
							$i_cat++;
							echo "<div class='collapsable_section_header' style='padding: 1px 2px 1px 2px'>".$cat."</div>";
							echo "<div class='collapsable_section_content' style='padding: 1px 2px 1px 2px'>";
							echo "<table style='border-spacing: 0px; border:none;'>";
							foreach ($paths as $path) {
								echo "<tr>";
								echo "<td style='padding:0px'>".$path->table->getDisplayableDataName($path->field_name)."</td>";
								echo "<td style='padding:0px'><select name='field_".$i_field++."'>";
								echo "<option value='-1'>Not Available</option>";
								echo "</select></td>";
								echo "</tr>";
							}
							echo "</table></div>";
						}
						?>
						</form>
						</div>
						<script type='text/javascript'>
						function row_start_changed(v) {
							var row = parseInt(v);
							if (isNaN(row) || row < 1) row = 1;
							row--;
							var xl = window.parent.excel;
							for (var i = 0; i < xl.sheets.length; ++i)
								for (var j = 0; j < xl.sheets[i].layers.length; ++j) {
									var col = xl.sheets[i].layers[j].col_start;
									var row_end = xl.sheets[i].layers[j].row_end;
									xl.sheets[i].layers[j].setRange(col,row,col,row_end);
								}
						}
						function row_end_changed(v) {
							var row;
							if (v.length == 0)
								row = -1;
							else
								row = parseInt(v);
							if (isNaN(row) || row < 1) row = -1;
							var xl = window.parent.excel;
							for (var i = 0; i < xl.sheets.length; ++i)
								for (var j = 0; j < xl.sheets[i].layers.length; ++j) {
									var col = xl.sheets[i].layers[j].col_start; 
									var row_start = xl.sheets[i].layers[j].row_start; 
									xl.sheets[i].layers[j].setRange(col,row_start,col,row == -1 ? xl.sheets[i].rows.length-1 : row-1);
								}
						}
						var fields_set = [];
						function field_changed(select) {
							if (select.value == -1)
								fields_set.remove(select);
							else if (!fields_set.contains(select))
								fields_set.push(select);
							window.parent.parent.enable_wizard_page_next(fields_set.length > 0);
						}
						function init_fields() {
							var xl = window.parent.excel;
							var form = document.forms['fields_selection'];
							var cols = [];
							for (var i = 0; i < xl.sheets.length; ++i) {
								for (var j = 0; j < xl.sheets[i].columns.length; ++j)
									cols.push({sheet:xl.sheets[i].name,col:xl.sheets[i].columns[j].name});
							}
							for (var i = 0; i < form.elements.length; ++i) {
								var e = form.elements[i];
								if (e.nodeName != 'SELECT') continue;
								e.onchange = function() {
									if (this.value == -1) {
										for (var i = 0; i < xl.sheets.length; ++i)
											for (var j = 0; j < xl.sheets[i].layers.length; ++j)
												if (xl.sheets[i].layers[j].data == this) {
													xl.sheets[i].removeLayer(xl.sheets[i].layers[j]);
													break;
												}
									} else {
										var i = this.value.indexOf('!');
										var sheet = xl.getSheet(this.value.substring(0,i));
										var col = window.parent.getExcelColumnIndex(this.value.substring(i+1));
										var row_start = parseInt(form.elements['start_row'].value);
										if (isNaN(row_start) || row_start < 1) row_start = 1;
										row_start--;
										var row_end = form.elements['end_row'].value.length > 0 ? parseInt(form.elements['end_row'].value) : -1;
										if (isNaN(row_end) || row_end < 1) row_end = sheet.rows.length;
										row_end--;
										var layer = sheet.addLayer(col,row_start,col,row_end,192,255,192);
										layer.data = this;
									}
									field_changed(this);
								};
								for (var j = 0; j < cols.length; ++j) {
									var o = document.createElement("OPTION");
									o.text = "Sheet '"+cols[j].sheet+"', Column '"+cols[j].col+"'";
									o.value = cols[j].sheet+"!"+cols[j].col;
									e.add(o);
								}
							}
						}
						init_fields();
						</script>
						<?php
						break;
					case "multiple":
						?>
						<div style='padding:2px'>
						<form name='fields_selection'>
						<?php
						$this->add_javascript("/static/widgets/collapsable_section/collapsable_section.js");
						$this->add_javascript("/static/widgets/typed_field/typed_field.js");
						$cats = array();
						foreach ($paths as $path) {
							if (!$path->is_unique()) continue;
							$cat = $path->table->getDisplayableDataCategory($path->field_name);
							if (isset($cats[$cat]))
								array_push($cats[$cat], $path);
							else
								$cats[$cat] = array($path);
						}
						$i_cat = 0;
						$i_field = 0;
						$paths_ids = array();
						foreach ($cats as $cat=>$paths) {
							echo "<div id='fields_cat_".$i_cat."' style='border: 1px solid black'>";
							$this->onload("new collapsable_section('fields_cat_".$i_cat."');");
							$i_cat++;
							echo "<div class='collapsable_section_header' style='padding: 1px 2px 1px 2px'>".$cat."</div>";
							echo "<div class='collapsable_section_content' style='padding: 1px 2px 1px 2px'>";
							echo "<table style='border-spacing: 0px; border:none;'>";
							foreach ($paths as $path) {
								$order = array();
								$pa = $path;
								while ($pa <> null) {
									array_splice($order, 0, 0, array($pa));
									$pa = $pa->parent;
								}
								$field_classname = null;
								$field_args = null;
								$done = false;
								for ($j = 0; $j < count($order); $j++) {
									$pa = $order[$j];
									if ($pa instanceof DataPath_Join) {
										if ($pa->foreign_key->multiple) {
											if ($pa->parent->table == $pa->foreign_key->table) {
												// n>1: it does not belong to us, we can propose the choice
												$field_classname = 'field_enum';
												// build request to have the list of values
												$q = SQLQuery::create();
												$q->select(array($pa->table->getSQLNameFor($pa->sub_model)=>$pa->table_alias));
												$source_table_primarykey_alias = $ctx->new_field_alias();
												$q->field($pa->table_alias, $pa->table->getPrimaryKey()->name, $source_table_primarykey_alias);
												for ($k = $j+1; $k < count($order); $k++)
													$order[$k]->append_sql($q, $ctx);
												$values = $q->execute();
												$value_alias = $order[count($order)-1]->field_alias;
												$field_args = "{possible_values:[";
												$first = true;
												foreach ($values as $v) {
													if ($first) $first = false; else $field_args .= ",";
													$field_args .= "[".json_encode($v[$source_table_primarykey_alias]).",".json_encode($v[$value_alias])."]";
												}
												$field_args .= "]";
												$field_args .= ",can_be_null:".($pa->foreign_key->remove_foreign_when_primary_removed ? "false" : "true");
												$field_args .= "}";
												break;
											}
										}
									}
								}
								if ($field_classname == null) {
									$col = $path->table->getColumn($path->field_name);
									$f = PNApplication::$instance->widgets->get_typed_field($col);
									$field_classname = $f[0];
									$field_args = $f[1];
								}

								$id = $this->generate_id();
								array_push($paths_ids, array($id,$path));
								echo "<tr>";
								echo "<td style='padding:0px'>".$path->table->getDisplayableDataName($path->field_name)."</td>";
								echo "<td style='padding:0px'>";
								echo "<select onchange='field_import_changed(this)' id='select_".$id."'>";
								echo "<option value=''>Not Available</option>";
								echo "<option value='range'>From Range</option>";
								echo "<option value='set'>All set to</option>";
								echo "</select>";
								echo "<span style='visibility:hidden;position:absolute'>";
								echo "<input type='text' size=20 id='range_".$id."'/>";
								array_push($range_inputs, $id);
								echo "<img src='/static/excel/select_range.png' style='vertical-align:bottom' class='button' onclick=\"select_range('".$id."')\"/>";
								echo "</span>";
								echo "<span style='visibility:hidden;position:absolute' id='set_".$id."'>";
								$this->add_javascript("/static/widgets/typed_field/".$field_classname.".js");
								$this->onload("document.getElementById('set_".$id."').appendChild(new ".$field_classname."(null,true,null,null,".$field_args.").getHTMLElement());");
								echo "</span>";
								echo "</td>";
								echo "</tr>";
							}
							echo "</table></div></div>";
						}
						?>
						</form>
						</div>
						<script type='text/javascript'>
						var import_paths = [<?php
						$first = true;
						foreach ($paths_ids as $id) {
							if ($first) $first = false; else echo ",";
							echo "{id:".$id[0].",path:".json_encode($id[1]->get_string())."}";
						} 
						?>];
						function field_import_changed(select) {
							var range_span = select.nextSibling;
							var set_span = range_span.nextSibling;
							range_span.style.visibility = 'hidden';
							range_span.style.position = 'absolute';
							set_span.style.visibility = 'hidden';
							set_span.style.position = 'absolute';
							if (select.value == 'range') {
								range_span.style.visibility = 'visible';
								range_span.style.position = 'static';
							} else if (select.value == 'set') {
								set_span.style.visibility = 'visible';
								set_span.style.position = 'static';
							}
						}
						function select_range(id) {
							var xl = window.parent.excel;
							var sheet = xl.getActiveSheet();
							if (sheet == null) return;
							var sel = sheet.getSelection();
							if (sel == null) return;
							var input = document.getElementById('range_'+id);
							input.value = window.parent.getExcelRangeString(sheet, sel);
						}
						function init() {
							service.json("data_model","get_available_fields",{table:<?php echo json_encode($_POST["root_table"]);?>},function(r){
								if (window.parent.parent.import_preset) {
									var presets = window.parent.parent.import_preset.split(",");
								}
							});
						}
						init();
						</script>
						<?php
						break;
					case "single":
						break;
				}
				break; 
			default: echo "Unknown wizard page ".$_GET["page"];
		}
	}
	
}
?>