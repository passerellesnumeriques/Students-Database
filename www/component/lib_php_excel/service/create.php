<?php 
class service_create extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) {
		switch($input["input"]["format"]) {
			case 'excel2007': return "application/vnd.ms-excel";
			case 'excel5': return "application/vnd.ms-excel";
			case 'csv': return "text/csv;charset=UTF-8";
			case 'pdf':	return "Content-Type: application/pdf";
		}
	}
	
	public function execute(&$component, $input) {
		$input = $input["input"];
		// create excel
		error_reporting(E_ERROR | E_PARSE);
		require_once("component/lib_php_excel/PHPExcel.php");
		$excel = new PHPExcel();
		$first_sheet = true;
		foreach ($input["sheets"] as $sh) {
			$sheet = new PHPExcel_Worksheet($excel, $sh["name"]);
			$excel->addSheet($sheet);
			if ($first_sheet) {
				$excel->removeSheetByIndex(0);
				$first_sheet = false;
			}
			set_time_limit(300);
			$merges = array();
			for ($row_num = 0; $row_num < count($sh["rows"]); $row_num++) {
				for ($cell_num = 0; $cell_num < count($sh["rows"][$row_num]); $cell_num++) {
					$row = $sh["rows"][$row_num];
					$cell = $row[$cell_num];
					$c = $sheet->setCellValueByColumnAndRow($cell_num, $row_num+1, $cell["value"], true);
					$format = @$cell["format"];
					if ($format == null) $format = "text";
					$i = strpos($format, ":");
					if ($i !== false) {
						$format_params = substr($format, $i+1);
						$format = substr($format,0,$i);
					} else
						$format_params = null;
					switch ($format) {
						case "boolean": $c->setDataType(PHPExcel_Cell_DataType::TYPE_BOOL); break;
						case "date":
							//$c->setDataType(PHPExcel_Cell_DataType::TYPE_NUMERIC);
							//$style = $sheet->getStyleByColumnAndRow($cell_num, $row_num+1);
							//$style->getNumberFormat()->setFormatCode($s);
							// TODO
							break;
						case "time":
							// TODO
							break;
						case "timestamp":
							// TODO
							break;
						case "number":
							$c->setDataType(PHPExcel_Cell_DataType::TYPE_NUMERIC);
							$style = $sheet->getStyleByColumnAndRow($cell_num, $row_num+1);
							$s = "0";
							$digits = intval($format_params);
							if ($digits > 0) {
								$s .= ".";
								for ($i = 0; $i < $digits; ++$i) $s .= "0";
							}
							$style->getNumberFormat()->setFormatCode($s);	
							break;
						default:
						case "text": $c->setDataType(PHPExcel_Cell_DataType::TYPE_STRING); break;
					}
					if (isset($cell["style"])) {
						$style = $sheet->getStyleByColumnAndRow($cell_num, $row_num+1);
						$font = $style->getFont();
						$fill = $style->getFill();
						$align = $style->getAlignment();
						if (isset($cell["style"]["backgroundColor"])) {
							$fill->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
							$fill->setStartColor(new PHPExcel_Style_Color(substr($cell["style"]["backgroundColor"],1)));
							$fill->setEndColor(new PHPExcel_Style_Color(substr($cell["style"]["backgroundColor"],1)));
						}
						if (isset($cell["style"]["textAlign"]))
							switch ($cell["style"]["textAlign"]) {
								case "left": $align->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT); break;
								case "right": $align->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT); break;
								case "center": $align->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); break;
							}
						if (isset($cell["style"]["fontWeight"])) $font->setBold($cell["style"]["fontWeight"] == "bold");
						if (isset($cell["style"]["fontStyle"])) $font->setItalic($cell["style"]["fontStyle"] == "italic");
					}
					if (isset($cell["rowSpan"]) || isset($cell["colSpan"])) {
						$r = isset($cell["rowSpan"]) ? intval($cell["rowSpan"]) : 1;
						$c = isset($cell["colSpan"]) ? intval($cell["colSpan"]) : 1;
						array_push($merges, array($cell_num, $row_num+1, $cell_num+$c-1, $row_num+1+$r-1));
						for ($i = 0; $i < $r; $i++)
							for ($j = 0; $j < $c; $j++) {
								if ($i == 0 && $j == 0) continue;
								array_splice($sh["rows"][$row_num+$i],$cell_num+$j,0,array("value"=>""));
							}
					}
				}
			}
			foreach ($merges as $merge)
				$sheet->mergeCellsByColumnAndRow($merge[0],$merge[1],$merge[2],$merge[3]);
		}
		
		set_time_limit(60);
		// initialize writer according to requested format
		$format = $input["format"];
		if ($format == 'excel2007') {
			header("Content-Disposition: attachment; filename=\"".$input["name"].".xlsx\"");
			$writer = new PHPExcel_Writer_Excel2007($excel);
		} else if ($format == 'excel5') {
			header("Content-Disposition: attachment; filename=\"".$input["name"].".xls\"");
			$writer = new PHPExcel_Writer_Excel5($excel);
		} else if ($format == 'csv') {
			header("Content-Disposition: attachment; filename=\"".$input["name"].".csv\"");
			echo "\xEF\xBB\xBF"; // UTF-8 BOM
			$writer = new PHPExcel_Writer_CSV($excel);
		} else if ($format == 'pdf') {
			header("Content-Disposition: attachment; filename=\"".$input["name"].".pdf\"");
			PHPExcel_Settings::setPdfRenderer(PHPExcel_Settings::PDF_RENDERER_MPDF, "component/lib_mpdf/MPDF");
			$writer = new PHPExcel_Writer_PDF($excel);
		}
		// write to output
		$writer->save('php://output');
		
	}
	
}
?>