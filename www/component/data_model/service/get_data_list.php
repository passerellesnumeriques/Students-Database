<?php 
class service_get_data_list extends Service {
	
	public function getRequiredRights() {
		return array();
	}
	
	public function documentation() { echo "Retrieve data from a list of DataPath"; }
	public function inputDocumentation() { echo "
<ul>
	<li><code>table</code>: name of starting table</li>
	<li><code>sub_model</code>: sub model of starting table</li>
	<li><code>fields</code>:[paths]</li>
	<li>optional: <code>actions</code>: if true, a list of possible links with icon are returned</li>
</ul>";
	}
	public function outputDocumentation() { echo "TODO"; }

	public function getOutputFormat($input) {
		if (isset($input["export"])) {
			$format = $input["export"];
			if ($format == 'excel2007')
				return "application/vnd.ms-excel";
			if ($format == 'excel5')
				return "application/vnd.ms-excel";
			if ($format == 'csv')
				return "text/csv;charset=UTF-8";
			if ($format == 'pdf')
				return "Content-Type: application/pdf";
		}
		return "text/json";
	}
	
	public function execute(&$component, $input) {
		$table = $input["table"];
		$fields = $input["fields"];
		// retrieve data paths
		require_once("component/data_model/DataPath.inc");
		$possible = DataPathBuilder::searchFrom($table, @$input["sub_model"]);
		$paths = array();
		foreach ($fields as $f) {
			$found = false;
			foreach ($possible as $p)
				if ($p->getString() == $f["path"]) {
				array_push($paths, $p);
				$found = true;
				break;
			}
			if (!$found) {
				PNApplication::error("Unknown data path: ".$f["path"]);
				return;
			}
		}

		$model = DataModel::get();
		
		// init query with root table
		$q = SQLQuery::create();
		$t = $model->getTable($table);
		$alias = $q->generateTableAlias();
		$table_name = $t->getSQLName(null);
		$q->select(array($table_name=>$alias));
		
		// retrieve DataDisplay, filters, and build the request
		$data_aliases = array();
		$display_data = array();
		$filters = array();
		$remaining_filters = $input["filters"];
		for ($i = 0; $i < count($fields); $i++) {
			$name = $fields[$i]["name"];
			$path = $paths[$i];
			$from = null;
			$display = $model->getTableDataDisplay($path->table->getName());
			if ($path instanceof DataPath_Join && $path->isReverse()) {
				$from = $path->foreign_key->name;
				// TODO $needed_columns = $display->getNeededColumnsToJoinFrom($from);
								
			}
			if ($display == null) {
				PNApplication::error("No display specified on table ".$path->table->getName()." for path ".$fields[$i]["path"]);
				return;
			}
			$data = null;
			foreach ($display->getDataDisplay($from) as $d)
				if ($d->getDisplayName() == $name) { $data = $d; break; }
			if ($data == null) {
				PNApplication::error("No displayable data ".$name." on table ".$path->table->getName());
				return;
			}
			array_push($display_data, $data);
			$f = array();
			for ($j = 0; $j < count($remaining_filters); $j++) {
				$filter = $remaining_filters[$j];
				if ($filter["category"] <> $data->getCategoryName()) continue;
				if ($filter["name"] <> $data->getDisplayName()) continue;
				array_splice($remaining_filters, $j, 1);
				$j--;
				$fil = array();
				array_push($fil, $filter["data"]);
				while (isset($filter["or"])) {
					$filter = $filter["or"];
					array_push($fil, $filter["data"]);
				}
				array_push($f, $fil);
			}
			array_push($filters, $f);
			array_push($data_aliases, $data->buildSQL($q, $path, $f));
		}
		
		// add filters not related to a displayed data
		foreach ($remaining_filters as $filter) {
			$found = false;
			foreach ($possible as $path) {
				$from = null;
				if ($path instanceof DataPath_Join && $path->isReverse())
					$from = $path->foreign_key->name;
				$display = DataModel::get()->getTableDataDisplay($path->table->getName());
				if ($display == null) continue;
				if ($display->getCategory()->getName() <> $filter["category"]) continue;
				foreach ($display->getDataDisplay($from) as $data) {
					if ($data->getDisplayName() <> $filter["name"]) continue;
					$found = true;
					$fil = array();
					array_push($fil, $filter["data"]);
					while (isset($filter["or"])) {
						$filter = $filter["or"];
						array_push($fil, $filter["data"]);
					}
					$filter_list = array($fil);
					$data->buildSQL($q, $path, $filter_list);
					break;
				}
				if ($found) break;
			}
			if (!$found) PNApplication::error("Invalid filter: unknown data '".$filter["name"]."' in category '".$filter["category"]."'");
		}
		
		// handle sort
		if (isset($input["sort_field"]) && isset($input["sort_order"])) {
			for ($i = 0; $i < count($display_data); $i++) {
				$data = $display_data[$i];
				if ($data->getCategoryName().".".$data->getDisplayName() == $input["sort_field"]) {
					if ($input["sort_order"] == "ASC") $asc = true;
					else if ($input["sort_order"] == "DESC") $asc = false;
					else break;
					$alias = $data_aliases[$i]["data"];
					$q->orderBy($alias, $asc);
					break;
				}
			}
		}
		
		//echo $q->generate();

		if (!isset($input["export"])) {
			// handle pages
			if (isset($input["page_size"])) {
				// calculate the total number of entries
				$count = new SQLQuery($q);
				$count->resetFields();
				$count->removeUnusefulJoinsForCounting();
				$count = $count->count("NB_DATA")->executeSingleRow();
				$count = $count["NB_DATA"];
				
				$nb = intval($input["page_size"]);
				if ($nb == 0) $nb = 1000;
				$page = isset($input["page"]) ? intval($input["page"]) : 0;
				if ($page == 0) $page = 1;
				$q->limit(($page-1)*$nb, $nb);
			}
		}
		// execute the query
		$res = $q->execute();
		
		// handle necessary sub requests
		for ($i = 0; $i < count($display_data); $i++) {
			$data = $display_data[$i];
			$path = $paths[$i];
			$data->performSubRequests($q, $res, $data_aliases[$i], $path, $filters[$i]);
		}
		
		if (!isset($input["export"])) {
			echo "{";
			if (isset($input["page_size"]))
				echo "count:".$count.",";
			echo "data:[";
			$first = true;
			foreach ($res as $row) {
				if ($first) $first = false; else echo ",";
				echo "{values:[";
				$f = true;
				for ($i = 0; $i < count($display_data); $i++) {
					$a = $data_aliases[$i];
					$data = $display_data[$i];
					$path = $paths[$i];
					if ($f) $f = false; else echo ",";
					echo "{v:";
					if (isset($row[$a["data"]]))
						echo json_encode($row[$a["data"]]);
					else
						echo "null";
					if ($a["key"] !== null && isset($row[$a["key"]]))
						echo ",k:".json_encode($row[$a["key"]]);
					else {
						echo ",k:null";
						//PNApplication::error("Missing key '".$a["key"]."' for data '".$data->getDisplayName()."' in table '".$data->handler->table->getName()."'");
					} 
					echo "}";
				}
				echo "]";
				echo "}";
			}
			echo "]";
			echo "}";
		} else {
			/* -- export -- */
			// create excel
			error_reporting(E_ERROR | E_PARSE);
			require_once("component/lib_php_excel/PHPExcel.php");
			$excel = new PHPExcel();
			$sheet = new PHPExcel_Worksheet($excel, "List");
			$excel->addSheet($sheet);
			$excel->removeSheetByIndex(0);
			// column headers
			$col_index = 0;
			for ($i = 0; $i < count($display_data); $i++) {
				$data = $display_data[$i];
				$sheet->setCellValueByColumnAndRow($i, 1, $data->getDisplayName());
			}
			// put data in excel
			$row_index = 2;
			foreach ($res as $row) {
				$col_index = 0;
				for ($i = 0; $i < count($display_data); $i++) {
					$a = $data_aliases[$i];
					$data = $display_data[$i];
					$path = $paths[$i];
					$value = $row[$a["data"]];
					// TODO make value as exportable...
					$sheet->setCellValueByColumnAndRow($col_index, $row_index, $value);
					$col_index++;
				}
				$row_index++;
			}
			// initialize writer according to requested format
			$format = $input["export"];
			if ($format == 'excel2007') {
				header("Content-Disposition: attachment; filename=\"list.xlsx\"");
				$writer = new PHPExcel_Writer_Excel2007($excel);
			} else if ($format == 'excel5') {
				header("Content-Disposition: attachment; filename=\"list.xls\"");
				$writer = new PHPExcel_Writer_Excel5($excel);
			} else if ($format == 'csv') {
				header("Content-Disposition: attachment; filename=\"list.csv\"");
				echo "\xEF\xBB\xBF"; // UTF-8 BOM
				$writer = new PHPExcel_Writer_CSV($excel);
			} else if ($format == 'pdf') {
				header("Content-Disposition: attachment; filename=\"list.pdf\"");
				PHPExcel_Settings::setPdfRenderer(PHPExcel_Settings::PDF_RENDERER_MPDF, "common/MPDF");
				$writer = new PHPExcel_Writer_PDF($excel);
			}
			// write to output
			$writer->save('php://output');
		}
	}
}
?>