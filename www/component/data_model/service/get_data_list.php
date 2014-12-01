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
		return "application/json";
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
		$table_name = $t->getName();
		$q->select(array($table_name=>$alias));
		if (($t->getModel() instanceof SubDataModel) && isset($input["sub_model"]))
			$q->selectSubModelForTable($t, $input["sub_model"]);
		
		// retrieve DataDisplay, filters, and build the request
		$data_aliases = array();
		$display_data = array();
		$filters = $input["filters"];
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
			foreach ($display->getDataDisplay($from, $path->sub_model) as $d)
				if ($d->getDisplayName() == $name) { $data = $d; break; }
			if ($data == null)
				foreach (DataModel::get()->getDataScreens() as $screen) {
					if ($screen instanceof \datamodel\MultipleDataScreen)
						if (in_array($path->table->getName(), $screen->getTables()))
							foreach ($screen->getDataDisplay($from, $path->sub_model, false) as $d)
								if ($d->getDisplayName() == $name) { $data = $d; break; }
					if ($data <> null) break;
			}
			if ($data == null) {
				PNApplication::error("No displayable data ".$name." on table ".$path->table->getName());
				return;
			}
			array_push($display_data, $data);
			if ($path->sub_model == "@link") {
				$sm = $path->table->getModel();
				$p = $path;
				do {
					$linked = $sm->getLinkedRootTable($p->table->getName());
					if ($linked <> null) break;
					$p = $p->parent;
					if ($p <> null && $p->sub_model <> "@link") $p = null;
				} while ($p <> null);
				if ($p <> null) {
					$linked_table_alias = $q->getTableAlias($linked);
					$linked_table = DataModel::get()->internalGetTable($linked);
					$key_alias = $q->getFieldAlias($linked, $linked_table->getPrimaryKey()->name);
					if ($key_alias == null) {
						$key_alias = $q->generateFieldAlias();
						$q->field($linked_table_alias, $linked_table->getPrimaryKey()->name, $key_alias);
					}
					$link_table_alias = $q->getTableAlias("smlink_".$p->table->getName()."_".$linked);
					if ($link_table_alias == null) {
						$link_table_alias = $q->generateTableAlias();
						$q->join($linked_table_alias, "smlink_".$p->table->getName()."_".$linked, array($linked_table->getPrimaryKey()->name=>"root"), $link_table_alias);
					}
					$sm_alias = $q->getFieldAlias("smlink_".$p->table->getName()."_".$linked, "sm");
					if ($sm_alias == null) {
						$sm_alias = $q->generateFieldAlias();
						$q->field($link_table_alias, "sm", $sm_alias);
					}
					array_push($data_aliases, array("sub_model_key"=>$sm_alias,"root_key"=>$key_alias,"sub_model_table"=>$p->table->getName(),"root_table"=>$linked));
				} else
					array_push($data_aliases, null);
				continue;
			}
			$data_alias = $data->buildSQL($q, $path);
			array_push($data_aliases, $data_alias);
			// put datadisplay in filters
			foreach ($filters as &$filter) {
				do {
					if ($filter["category"] == $data->getCategoryName() && $filter["name"] == $data->getDisplayName()) {
						$filter["datadisplay"] = $data;
						$filter["datapath"] = $path;
						$filter["dataaliases"] = $data_alias;
					}
					if (!isset($filter["or"])) break;
					$f = &$filter["or"];
					unset($filter);
					$filter = &$f;
				} while (true);
			}
		}
		
		// add filters not related to a displayed data
		$remaining_filters = array();
		foreach ($filters as &$filter) {
			if (!isset($filter["datadisplay"]))
				array_push($remaining_filters, $filter);
			while (isset($filter["or"])) {
				$f = &$filter["or"];
				unset($filter);
				$filter = &$f;
				if (!isset($filter["datadisplay"]))
					array_push($remaining_filters, $filter);
			}
		}
		foreach ($filters as &$filter) {
			do {
				if (!isset($filter["datadisplay"])) {
					$found = false;
					foreach ($possible as $path) {
						$from = null;
						if ($path instanceof DataPath_Join && $path->isReverse())
							$from = $path->foreign_key->name;
						$display = DataModel::get()->getTableDataDisplay($path->table->getName());
						if ($display == null) continue;
						if ($display->getCategory()->getName() <> $filter["category"]) continue;
						foreach ($display->getDataDisplay($from, $path->sub_model) as $data) {
							if ($data->getDisplayName() <> $filter["name"]) continue;
							$found = true;
							$filter["datadisplay"] = $data;
							$filter["datapath"] = $path;
							$filter["dataaliases"] = $data->buildSQL($q, $path);
							// check if we have other filters on same data
							foreach ($filters as &$fil) {
								do {
									if (!isset($fil["datadisplay"])) {
										if ($fil["category"] == $display->getCategory()->getName() && $fil["name"] == $data->getDisplayName()) {
											$fil["datadisplay"] = $data;
											$fil["datapath"] = $path;
											$fil["dataaliases"] = $filter["dataaliases"];
										}
									}
									if (!isset($fil["or"])) break;
									$f = &$fil["or"];
									unset($fil);
									$fil = &$f;
									unset($f);
								} while (true);				
							}
							break;
						}
						if ($found) break;
					}
					if (!$found) PNApplication::error("Invalid filter: unknown data '".$filter["name"]."' in category '".$filter["category"]."'");
				}
				if (!isset($filter["or"])) break;
				$f = &$filter["or"];
				unset($filter);
				$filter = &$f;
				unset($f);
			} while (true);
		}
		
		// apply filters
		foreach ($filters as &$filter) {
			$where = "(";
			$having = "(";
			$cd = $filter["datadisplay"]->getFilterCondition($q, $filter["datapath"], $filter["dataaliases"], $filter["data"]);
			if ($cd <> null) {
				if ($cd["type"] == "where")
					$where .= "(".$cd["condition"].")";
				else if ($cd["type"] == "having")
					$having .= "(".$cd["condition"].")";
			}
			while (isset($filter["or"])) {
				$cd = $filter["or"]["datadisplay"]->getFilterCondition($q, $filter["or"]["datapath"], $filter["or"]["dataaliases"], $filter["or"]["data"]);
				if ($cd <> null) {
					if ($cd["type"] == "where") {
						if ($where <> "(") $where .= " OR ";
						$where .= "(".$cd["condition"].")";
					} else if ($cd["type"] == "having") {
						if ($having <> "(") $having .= " OR ";
						$having .= "(".$cd["condition"].")";
					}
				}
				$f = $filter["or"];
				unset($filter);
				$filter = $f;
			}
			if ($where <> "(")
				$q->where($where.")");
			if ($having <> "(")
				$q->having($having.")");
		}
		
		// handle sort
		$sort_done = true;
		if (isset($input["sort_field"]) && isset($input["sort_order"])) {
			for ($i = 0; $i < count($display_data); $i++) {
				$data = $display_data[$i];
				if ($data->getCategoryName().".".$data->getDisplayName() == $input["sort_field"]) {
					if ($input["sort_order"] == "ASC") $asc = true;
					else if ($input["sort_order"] == "DESC") $asc = false;
					else break;
					$alias = @$data_aliases[$i]["data"];
					if ($alias <> null)
						$q->orderBy($alias, $asc);
					else
						$sort_done = false;
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
		$query_start_time = microtime(true);
		// execute the query
		$res = $q->execute();
		
		// handle necessary sub requests
		$sub_models_linked = array();
		for ($i = 0; $i < count($display_data); $i++) {
			$data = $display_data[$i];
			$path = $paths[$i];
			if ($path->sub_model == "@link") {
				$sm_table = $path->table->getModel()->getParentTable();
				if (!isset($sub_models_linked[$sm_table]))
					$sub_models_linked[$sm_table] = array();
				if (!isset($sub_models_linked[$sm_table][$data_aliases[$i]["sub_model_key"]]))
					$sub_models_linked[$sm_table][$data_aliases[$i]["sub_model_key"]] = array();
				if (!isset($sub_models_linked[$sm_table][$data_aliases[$i]["sub_model_key"]][$data_aliases[$i]["sub_model_table"]]))
					$sub_models_linked[$sm_table][$data_aliases[$i]["sub_model_key"]][$data_aliases[$i]["sub_model_table"]] = array("root_key"=>$data_aliases[$i]["root_key"],"indexes"=>array());
				array_push($sub_models_linked[$sm_table][$data_aliases[$i]["sub_model_key"]][$data_aliases[$i]["sub_model_table"]]["indexes"], $i);
				continue;
			}
			$data->performSubRequests($q, $res, $data_aliases[$i], $path);
		}
		foreach ($sub_models_linked as $sm_table=>$sm_keys) {
			foreach ($sm_keys as $sm_key_alias=>$entry_tables) {
				$instances = array();
				foreach ($res as $row)
					if (isset($row[$sm_key_alias]) && !in_array($row[$sm_key_alias], $instances))
						array_push($instances, $row[$sm_key_alias]);
				foreach ($entry_tables as $entry_table=>$info) {
					$root_key_alias = $info["root_key"];
					$indexes = $info["indexes"];
					foreach ($instances as $sm_instance) {
						$link_keys = array();
						foreach ($res as $row) if ($row[$sm_key_alias] == $sm_instance && !in_array($row[$root_key_alias], $link_keys)) array_push($link_keys, $row[$root_key_alias]);
						$sq = SQLQuery::create();
						$sq->avoidAliasCollision($q);
						$entry_table_alias = $sq->generateTableAlias();
						$sq->select(array($entry_table=>$entry_table_alias));
						$sq->selectSubModel($sm_table, $sm_instance);
						$sm_table = DataModel::get()->getTable($entry_table);
						$root_table = $sm_table->getModel()->getLinkedRootTable($entry_table);
						$fk = null;
						foreach ($sm_table->internalGetColumnsFor($sm_instance) as $col)
							if (($col instanceof datamodel\ForeignKey) && $col->foreign_table == $root_table) { $fk = $col; break; }
						$sq->whereIn($entry_table_alias, $fk->name, $link_keys);
						$sq->field($entry_table_alias, $fk->name, "SM_ENTRY_KEY");
						$sm_data_aliases = array();
						foreach ($indexes as $i) {
							$data = $display_data[$i];
							$path = $paths[$i];
							$path->sub_model_from_link = true;
							$path->sub_model = $sm_instance;
							$data_alias = $data->buildSQL($sq, $path);
							$sm_data_aliases[$i] = $data_alias;
							$data_aliases[$i] = array("key"=>"SM_KEY_".$i, "data"=>"SM_DATA_".$i);
						}
						$sm_res = $sq->execute();
						$q->avoidAliasCollision($sq);
						foreach ($sm_res as $row) {
							$key = $row["SM_ENTRY_KEY"];
							for ($i = 0; $i < count($res); $i++) {
								if ($res[$i][$root_key_alias] == $key) {
									foreach ($indexes as $ind) {
										$a = $sm_data_aliases[$ind];
										$res[$i]["SM_KEY_".$ind] = $row[$a["key"]];
										$res[$i]["SM_DATA_".$ind] = $row[$a["data"]]; 
									}
								}
							}
						}
						// TODO sub requests of sub requests...
					}
				}
			}
		}
		$query_end_time = microtime(true);
		
		if (!$sort_done) {
			// manual sort
			for ($i = 0; $i < count($display_data); $i++) {
				$data = $display_data[$i];
				if ($data->getCategoryName().".".$data->getDisplayName() == $input["sort_field"]) {
					$alias = @$data_aliases[$i]["data"];
					if ($alias <> null) {
						if ($input["sort_order"] == "ASC") $asc = true;
						else if ($input["sort_order"] == "DESC") $asc = false;
						$sorter = new DataListRowSorter($alias, $asc);
						usort($res, array($sorter,'compare'));
					}
					break;
				}
			}
		}
		
		if (!isset($input["export"])) {
			echo "{";
			if (isset($input["page_size"]))
				echo "count:".$count.",";
			echo "time:".($query_end_time-$query_start_time).",";
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
					echo json_encode($data->getData($row, $a));
					if (@$a["key"] !== null && isset($row[$a["key"]]))
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
			// get multiple export fields
			$multiple = array();
			for ($i = 0; $i < count($fields); $i++) {
				if (isset($multiple[$fields[$i]["path"]])) continue;
				$max = 0;
				foreach ($res as $row) {
					$a = $data_aliases[$i];
					$data = $display_data[$i];
					$value = $data->getData($row, $a);
					$path = $paths[$i];
					$times = $data->getExportTimes($value, $path->sub_model);
					if ($times > $max) $max = $times;
				}
				$multiple[$fields[$i]["path"]] = $max;
			}
			// column headers
			$has_sub_data = false;
			foreach ($fields as $f) if ($f["sub_index"] <> -1) { $has_sub_data = true; break; }
			$col_index = 0;
			for ($i = 0; $i < count($display_data); $i++) {
				$data = $display_data[$i];
				$times = $multiple[$fields[$i]["path"]];
				$count = 1;
				if ($fields[$i]["sub_index"] <> -1)
					while ($i+$count < count($display_data) && $fields[$i+$count]["path"] == $fields[$i]["path"]) $count++;
				for ($num = 1; $num <= $times; $num++) {
					// set the main title: display name
					$sheet->setCellValueByColumnAndRow($col_index, 1, $data->getDisplayName().($times > 1 ? " ".$num : ""));
					if ($has_sub_data) {
						// we have sub data
						if ($fields[$i]["sub_index"] == -1) {
							// not on this one => merge 2 rows
							$sheet->mergeCellsByColumnAndRow($col_index, 1, $col_index, 2);
							$col_index++;
						} else {
							// merge the main title
							$sheet->mergeCellsByColumnAndRow($col_index, 1, $col_index+$count-1, 1);
							// set the sub-titles
							$sub_data = $data->getSubDataDisplay();
							$names = $sub_data->getDisplayNames();
							for ($j = 0; $j < $count; $j++) {
								$sub_index = $fields[$i+$j]["sub_index"];
								$sheet->setCellValueByColumnAndRow($col_index++, 2, $names[$sub_index]);
							}
							
						}
					} else $col_index++;
				}
				$i += $count-1;
			}
			$row_index = $has_sub_data ? 3 : 2;
			// put data in excel
			foreach ($res as $row) {
				$col_index = 0;
				for ($i = 0; $i < count($display_data); $i++) {
					$a = $data_aliases[$i];
					$data = $display_data[$i];
					$path = $paths[$i];
					$value = $data->getData($row, $a);
					$times = $multiple[$fields[$i]["path"]];
					$count = 1;
					if ($fields[$i]["sub_index"] <> -1)
						while ($i+$count < count($display_data) && $fields[$i+$count]["path"] == $fields[$i]["path"]) $count++;
					for ($num = 0; $num < $times; $num++) {
						if ($fields[$i]["sub_index"] == -1) {
							$val = $data->exportValueNumber($value, $path->sub_model, $num);
							$sheet->setCellValueByColumnAndRow($col_index++, $row_index, $val);
						} else {
							$sub_data = $data->getSubDataDisplay();
							for ($j = 0; $j < $count; $j++) {
								$sub_index = $fields[$i+$j]["sub_index"];
								$val = $sub_data->exportValueNumber($value, $path->sub_model, $sub_index, $num);
								$sheet->setCellValueByColumnAndRow($col_index++, $row_index, $val);
							}
						}
					}
					$i += $count-1;
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
				PHPExcel_Settings::setPdfRenderer(PHPExcel_Settings::PDF_RENDERER_MPDF, "component/lib_mpdf/MPDF");
				$writer = new PHPExcel_Writer_PDF($excel);
			}
			// write to output
			$writer->save('php://output');
		}
	}
}

class DataListRowSorter {
	
	private $alias;
	private $asc;
	
	public function __construct($alias, $asc) {
		$this->alias = $alias;
		$this->asc = $asc;
	}
	
	public function compare($row1,$row2) {
		$r = $this->cmp($row1,$row2);
		if ($this->asc) return $r;
		return -$r;
	}
	
	public function cmp($row1,$row2) {
		$v1 = @$row1[$this->alias];
		$v2 = @$row2[$this->alias];
		if ($v1 === null) {
			if ($v2 === null) return 0;
			return -1;
		}
		if ($v2 === null) return 1;
		if (is_numeric($v1)) {
			if (!is_numeric($v2)) return -1;
			$i1 = intval($v1);
			$i2 = intval($v2);
			if ($i1 < $i2) return -1;
			if ($i1 > $i2) return 1;
			return 0;
		}
		if (is_numeric($v2)) return 1;
		if (!is_string($v1)) {
			if (is_string($v2)) return 1;
			return 0;
		}
		if (!is_string($v2)) return -1;
		if (strlen($v1) == 10 && substr($v1,4,1) == "-" && substr($v1,7,1) == "-") {
			if (strlen($v2) == 10 && substr($v2,4,1) == "-" && substr($v2,7,1) == "-") {
				$t1 = strtotime($v1);
				$t2 = strtotime($v2);
				if ($t1 < $t2) return -1;
				if ($t1 > $t2) return 1;
				return 0;
			} else
				return -1;
		} else if (strlen($v2) == 10 && substr($v2,4,1) == "-" && substr($v2,7,1) == "-")
			return 1;
		return strcasecmp($v1, $v2);
	}
	
}
?>