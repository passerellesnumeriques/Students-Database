<?php 
class page_excel_upload extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		if (isset($_GET["button"])) {
			?>
			<table style='width:100%;height:100%'>
			<tr><td valign=middle align=center>
			<button class='action' onclick="window.frameElement.<?php echo $_GET["button"];?>();">
				Open File...
			</button>
			<div><img src='<?php echo theme::$icons_16["info"];?>' style='vertical-align:bottom'/> Supported Formats: Microsoft Excel, Open Office Calc, Gnome Gnumeric, and CSV.</div>
			</td></tr>
			</table>
			<script type='text/javascript'>window.is_excel_upload_button = true;</script>
			<?php 
			return;
		}
		if (isset($_GET["new"])) {
			echo "Please upload an Excel file";
			return;
		}
		if (isset($_GET["id"])) {
			$path = PNApplication::$instance->storage->get_data_path($_GET["id"]);
		} else if (!isset($_FILES["excel"]) || $_FILES["excel"]['error'] <> UPLOAD_ERR_OK) {
			echo "<script type='text/javascript'>window.page_errors=true;</script>";
			PNApplication::error("Error uploading file (".(isset($_FILES["excel"]) ? PNApplication::$instance->storage->get_upload_error($_FILES["excel"]) : "no file received").").");
			return;
		} else
			$path = $_FILES["excel"]['tmp_name'];
		require_once("component/lib_php_excel/PHPExcel.php");
		set_time_limit(120);
		try {
			$reader = PHPExcel_IOFactory::createReaderForFile($path);
			if (get_class($reader) == "PHPExcel_Reader_HTML") throw new Exception();
			$excel = $reader->load($path);
			if (isset($_GET["id"])) {
				PNApplication::$instance->storage->remove_data($_GET["id"]);
			}
		} catch (Exception $e) {
			echo "<script type='text/javascript'>window.page_errors=true;</script>";
			PNApplication::error("Invalid file format: ".$e->getMessage());
			return;
		}
		$this->addJavascript("/static/excel/excel.js");
		$this->addJavascript("/static/widgets/splitter_vertical/splitter_vertical.js");
		$this->onload("setTimeout(init_page,1);");
		?>
		<div id='excel_container' style='width:100%;height:100%'>
		</div>
		<script type='text/javascript'>
		window.excel_uploaded = true;
		</script>
		<?php 
		$code = "";
		foreach ($excel->getWorksheetIterator() as $sheet) {
			$cols = 0;
			while ($sheet->cellExistsByColumnAndRow($cols, 1)) $cols++;
			$rows = 0;
			$nb_rows_without_cells = 0;
			foreach ($sheet->getRowIterator() as $row) {
				$rows++;
				$it = $row->getCellIterator();
				$it->setIterateOnlyExistingCells(false);
				$c = 0;
				$has_cells = false;
				foreach ($it as $col) {
					$c++;
					if ($sheet->cellExistsByColumnAndRow($col->getColumn(), $col->getRow()) && $col->getValue() <> null)
						$has_cells = true;
				}
				if ($c > $cols) $cols = $c;
				if (!$has_cells)
					$nb_rows_without_cells++;
				else
					$nb_rows_without_cells = 0;
			}
			$rows -= $nb_rows_without_cells;
			$code .= "xl.addSheet(".json_encode($sheet->getTitle()).",null,".$cols.",".$rows.",function(sheet){\n";
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
				$code .= "\tsheet.getColumn(".$i.").setWidth(".floor($w+1).");\n";
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
				$code .= "\tsheet.getRow(".$i.").setHeight(".floor($h+1).");\n";
			}
			$code .= "\tvar c;";
			for ($col = 0; $col < $cols; $col++) {
				for ($row = 0; $row < $rows; $row++) {
					try {
						$cell = $sheet->getCellByColumnAndRow($col, $row+1);
						$val = $cell->getCalculatedValue();
						$val = "/".$cell->getCalculatedValue()."/".$cell->getFormattedValue()."/".$cell->getCoordinate();
						if (PHPExcel_Shared_Date::isDateTime($cell) && is_numeric($val)) {
							$val = PHPExcel_Shared_Date::ExcelToPHPObject($cell->getCalculatedValue());
							$date = getdate($val->getTimestamp());
							if ($date["seconds"] == 0) {
								if ($date["minutes"] == 0 && $date["hours"] == 0)
									$val = $val->format("Y-m-d"); // only a date
								else
									$val = $val->format("Y-m-d H:i"); // date time
							} else
								$val = $val->format("Y-m-d H:i:s"); // date time including seconds
						} else {
							try {
								//$val = $cell->getFormattedValue();
							} catch (Exception $e) {}
						}
						if ($val == "#REF!") $val = $cell->getOldCalculatedValue();
					} catch (Exception $e) {
						$val = "ERROR: ".$e->getMessage();
					}
					$code .= "\tc = sheet.getCell(".$col.",".$row.");";
					$code .= "c.setValue(".json_encode("".$val).");";
					$style = $sheet->getStyleByColumnAndRow($col, $row+1);
					$code .= "c.setStyle({";
					$code .= "overflow:'hidden'";
					if ($style <> null) {
						$font = $style->getFont();
						$font_name = $font <> null && $font->getName() <> null ? $font->getName() : "Calibri";
						$font_size = $font <> null && $font->getSize() <> null ? $font->getSize() : "11";
						$font_weight = $font <> null && $font->getBold() ? "bold" : "normal";
						$font_style = $font <> null && $font->getItalic() ? "italic" : "normal";
						$font_color = $font <> null && $font->getColor() <> null ? "#".$font->getColor()->getRGB() : "black";
						$code .= ",fontFamily:".json_encode($font_name);
						$code .= ",fontSize:".json_encode($font_size."pt");
						$code .= ",fontWeight:".json_encode($font_weight);
						$code .= ",fontStyle:".json_encode($font_style);
						$code .= ",color:".json_encode($font_color);
						if ($style->getFill() <> null && $style->getFill()->getFillType() == PHPExcel_Style_Fill::FILL_SOLID && $style->getFill()->getStartColor() <> null)
							$code .= ",backgroundColor:'#".$style->getFill()->getStartColor()->getRGB()."'";
						$align = $style->getAlignment();
						if ($align <> null) {
							$horiz = $align->getHorizontal();
							switch ($horiz) {
							case PHPExcel_Style_Alignment::HORIZONTAL_CENTER: $code .= ",textAlign:'center'"; break;
							case PHPExcel_Style_Alignment::HORIZONTAL_LEFT: $code .= ",textAlign:'left'"; break;
							case PHPExcel_Style_Alignment::HORIZONTAL_RIGHT: $code .= ",textAlign:'right'"; break;
							}
						}
						$borders = $style->getBorders();
						if ($borders <> null) {
							$bottom = $borders->getBottom();
							if ($bottom <> null) {
								$s = $bottom->getBorderStyle();
								if ($s <> null && $s <> PHPExcel_Style_Border::BORDER_NONE) {
									switch ($s) {
										default:
										case PHPExcel_Style_Border::BORDER_THIN: $border = "1px solid"; break;
										case PHPExcel_Style_Border::BORDER_MEDIUM: $border = "2px solid"; break;
										case PHPExcel_Style_Border::BORDER_DOTTED: $border = "1px dotted"; break;
										// TODO
									}
									$border .= " #".$bottom->getColor()->getRGB();
									$code .= ",borderBottom:'$border'";
								}
							}
							$top = $borders->getTop();
							if ($top <> null) {
								$s = $top->getBorderStyle();
								if ($s <> null && $s <> PHPExcel_Style_Border::BORDER_NONE) {
									switch ($s) {
										default:
										case PHPExcel_Style_Border::BORDER_THIN: $border = "1px solid"; break;
										case PHPExcel_Style_Border::BORDER_MEDIUM: $border = "2px solid"; break;
										case PHPExcel_Style_Border::BORDER_DOTTED: $border = "1px dotted"; break;
										// TODO
									}
									$border .= " #".$top->getColor()->getRGB();
									$code .= ",borderTop:'$border'";
								}
							}
							$left = $borders->getLeft();
							if ($left <> null) {
								$s = $left->getBorderStyle();
								if ($s <> null && $s <> PHPExcel_Style_Border::BORDER_NONE) {
									switch ($s) {
										default:
										case PHPExcel_Style_Border::BORDER_THIN: $border = "1px solid"; break;
										case PHPExcel_Style_Border::BORDER_MEDIUM: $border = "2px solid"; break;
										case PHPExcel_Style_Border::BORDER_DOTTED: $border = "1px dotted"; break;
										// TODO
									}
									$border .= " #".$left->getColor()->getRGB();
									$code .= ",borderLeft:'$border'";
								}
							}
							$right = $borders->getRight();
							if ($right <> null) {
								$s = $right->getBorderStyle();
								if ($s <> null && $s <> PHPExcel_Style_Border::BORDER_NONE) {
									switch ($s) {
										default:
										case PHPExcel_Style_Border::BORDER_THIN: $border = "1px solid"; break;
										case PHPExcel_Style_Border::BORDER_MEDIUM: $border = "2px solid"; break;
										case PHPExcel_Style_Border::BORDER_DOTTED: $border = "1px dotted"; break;
										// TODO
									}
									$border .= " #".$right->getColor()->getRGB();
									$code .= ",borderRight:'$border'";
								}
							}
						}
					}
					$code .= "});\n";
				}
			}
			$merges = $sheet->getMergeCells();
			foreach ($merges as $merge) {
				list($rangeStart, $rangeEnd) = PHPExcel_Cell::rangeBoundaries($merge);
				$col_start = $rangeStart[0]-1;
				$col_end = $rangeEnd[0]-1;
				$row_start = $rangeStart[1]-1;
				$row_end = $rangeEnd[1]-1;
				$code .= "\tsheet.mergeCells($col_start,$row_start,$col_end,$row_end);\n";
			}
			$code .= "});\n";
		} 
		?>
		<script type='text/javascript'>
		function init_page() {
			window.excel = new Excel('excel_container', function(xl) {<?php echo $code; ?>});
		}							
		</script>
		<?php
	}
	
}
?>