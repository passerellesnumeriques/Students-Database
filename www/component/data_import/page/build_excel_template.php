<?php 
class page_build_excel_template extends Page {
	
	public function get_required_rights() { return array(); }
	// TODO have a system to allow creating this
	
	protected function execute() {
		require_once("component/widgets/page/wizard.inc");
		if (!isset($_GET["page"])) {
			create_wizard_page($this, "/static/data_import/import_excel_32.png", "Create Excel Import Template", "/dynamic/data_import/page/build_excel_template?page=select_file");
		} else switch ($_GET["page"]) {
			case "select_file":
				?>
				<form action="?page=upload" method="POST" enctype="multipart/form-data" name='excel_example' style='padding:5px'>
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
				Upload an example of Excel file to define the template:<br/>
				<input type='file' name='excel' onchange="window.parent.enable_wizard_page_next(this.value.length >0);"/>
				</form>
				<script type='text/javascript'>
				function wizard_page_go_next() {
					document.forms["excel_example"].submit();
				}
				</script>
				<?php
				break;
			case "upload":
				if (!isset($_FILES["excel"]) || $_FILES["excel"]['error'] <> UPLOAD_ERR_OK) {
					echo "Error uploading file.";
					echo "<script type='text/javascript'>window.parent.enable_wizard_page_previous(true);function wizard_page_go_previous() { location.href='?page=select_file'; }</script>";
				} else {
					require_once("component/lib_php_excel/PHPExcel.php");
					$excel = PHPExcel_IOFactory::load($_FILES["excel"]['tmp_name']);
					$this->add_javascript("/static/excel/excel.js");
					$this->add_javascript("/static/widgets/splitter_vertical/splitter_vertical.js");
					$this->onload("init_page();");
					?>
					<div id='excel_template_page' style='width:100%;height:100%'>
						<div id='excel'></div>
						<div id='fields' style='overflow:auto;padding:2px;'>
						<?php
						require_once("component/data_model/DataPath.inc");
						$ctx = new DataPathBuilderContext();
						$paths = DataPathBuilder::search_from($ctx, $_POST["root_table"]);
						$this->add_javascript("/static/data_import/excel_template_field.js");
						foreach ($paths as $path) {
							if (!$path->is_unique()) continue;
							echo "<div id='excel_template_field__".$path->table->getName()."__".$path->field_name."'></div>";
							$this->onload("new excel_template_field('".$path->table->getName()."','".$path->field_name."','".str_replace("\'","\\\'",$path->table->getDisplayableDataName($path->field_name))."');");
						} 
						?>
						</div>
					</div>
					<script type='text/javascript'>
					function init_page() {
						new splitter_vertical('excel_template_page',0.7);
						window.excel = new Excel('excel', function(xl) {
							<?php
							foreach ($excel->getWorksheetIterator() as $sheet) {
								$cols = 0;
								while ($sheet->cellExistsByColumnAndRow($cols, 1)) $cols++;
								$rows = 0;
								while ($sheet->cellExistsByColumnAndRow(0, $rows+1)) $rows++;
								echo "xl.addSheet(".json_encode($sheet->getTitle()).",null,".$cols.",".$rows.",function(sheet){";
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
									echo "sheet.getColumn(".$i.").setWidth(".floor($w+1).");";
								}
								for ($i = 0; $i < $rows; $i++) {
									$row = $sheet->getRowDimension($i+1);
									if ($row == null) $h = $sheet->getDefaultRowDimension()->getRowHeight();
									else {
										$h = $row->getRowHeight();
										if ($h == -1) $h = $sheet->getDefaultRowDimension()->getRowHeight();
									}
									if ($h < 2) $h = 2;
									echo "sheet.getRow(".$i.").setHeight(".floor($h+1).");";
								}
								echo "var c;";
								for ($col = 0; $col < $cols; $col++) {
									for ($row = 0; $row < $rows; $row++) {
										$cell = $sheet->getCellByColumnAndRow($col, $row+1);
										$val = $cell->getFormattedValue();
										echo "c = sheet.getCell(".$col.",".$row.");";
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
										echo "});";
									}
								}
								echo "});";
							} 
							?>
						});
					}									
					</script>
					<?php
				}
				break;
			default: echo "Unknown wizard page ".$_GET["page"];
		}
	}
	
}
?>