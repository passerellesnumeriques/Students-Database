<?php 
class page_excel_upload extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		if (!isset($_FILES["excel"]) || $_FILES["excel"]['error'] <> UPLOAD_ERR_OK) {
			PNApplication::error("Error uploading file (".(isset($_FILES["excel"]) ? PNApplication::$instance->storage->get_upload_error($_FILES["excel"]) : "no file received").").");
			return;
		}
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
		$this->onload("setTimeout(init_page,1);");
		?>
		<div id='excel_container' style='width:100%;height:100%'>
		</div>
		<script type='text/javascript'>
		window.excel_uploaded = true;
		</script>
		<script type='text/javascript'>
		function init_page() {
			window.excel = new Excel('excel_container', function(xl) {
				<?php
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
	
}
?>