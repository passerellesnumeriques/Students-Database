<?php 
class service_get_data_list extends Service {
	
	public function get_required_rights() {
		return array();
	}
	
	public function documentation() { echo "Retrieve data from a list of DataPath"; }
	public function input_documentation() {?>
<ul>
	<li><code>table</code>: name of starting table</li><li><code>fields</code>:[paths]</li>
	<li>optional: <code>actions</code>: if true, a list of possible links with icon are returned</li>
</ul>	
<?php }
	public function output_documentation() {
?>
TODO
<?php 
	}
	public function get_output_format($input) {
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
		require_once("component/data_model/DataPath.inc");
		$ctx = new DataPathBuilderContext();
		$possible = DataPathBuilder::search_from($ctx, $table);
		$paths = array();
		foreach ($fields as $f) {
			foreach ($possible as $p)
				if ($p->get_string() == $f) {
					array_push($paths, $p);
					break;
				}
		}
		$q = SQLQuery::create();
		$t = DataModel::get()->getTable($table);
		$alias = null;
		foreach ($paths as $p) {
			$root = $p;
			while ($root <> null) {
				if ($root->table->getName() == $table) {
					$alias = $root->table_alias;
					break;
				}
				$root = $root->parent;
			}
			if ($alias <> null) break; 
		}
		$table_name = $t->getSQLName(null);
		$q->select(array($table_name=>$alias));
		foreach ($paths as $p) {
			if ($p->is_unique())
				$p->build_sql($q, $ctx);
			else {
				// we need the key for the sub-requests
				$multiple_path = $p->parent;
				while ($multiple_path->unique)
					$multiple_path = $multiple_path->parent;
				$multiple_path->parent->build_sql($q, $ctx);
				$pk = $multiple_path->parent->table->getPrimaryKey();
				$alias = $q->get_field_alias($multiple_path->parent->table_alias, $pk->name);
				if ($alias == null) {
					$alias = $ctx->new_field_alias();
					$q->field($multiple_path->parent->table_alias, $pk->name, $alias);
				}
			}
		}
					
		$actions = null;
		if (isset($input["actions"]) && $input["actions"]) {
			$actions = array();
			$categories = array();
			foreach ($paths as $p) {
				$cat_name = $p->parent->table->getDisplayableDataCategoryAndName($p->field_name);
				if ($cat_name == null) continue;
				if (!in_array($cat_name[0], $categories))
					array_push($categories, $cat_name[0]);
			}
			$model = DataModel::get();
			foreach ($categories as $cat) {
				$link = $model->getDataCategoryLink($cat);
				$icon = $model->getDataCategoryLinkIcon($cat);
				if ($link <> null)
					array_push($actions, array($link,$icon));
			}
			foreach ($actions as &$action) {
				$k = 0;
				$link = $action[0];
				while (($k = strpos($link, "%", $k)) !== false) {
					$kk = strpos($link, "%", $k+1);
					if ($kk === false) break;
					$s = substr($link, $k+1, $kk-$k-1);
					$l = strpos($s, ".");
					$table = substr($s, 0, $l);
					$col = substr($s, $l+1);
					$alias = $q->get_field_alias($q->get_table_alias($table), $col);
					if ($alias == null) {
						$alias = $ctx->new_field_alias();
						$q->field($table, $col, $alias);
					}
					$k = $kk+1;
					continue;
				}
			}
		}
		
		if (isset($input["sort_field"]) && isset($input["sort_order"])) {
			foreach ($paths as $p) {
				if ($p->get_string() == $input["sort_field"]) {
					if ($input["sort_order"] == "ASC") $asc = true;
					else if ($input["sort_order"] == "DESC") $asc = false;
					else break;
					$q->order_by($p->field_alias, $asc);
					break;
				}
			}
		}
		$count = null;
		if (!isset($input["export"]) && isset($input["page_size"])) {
			$nb = intval($input["page_size"]);
			if ($nb == 0) $nb = 1000;
			$page = isset($input["page"]) ? intval($input["page"]) : 0;
			if ($page == 0) $page = 1;
			$q_count = new SQLQuery($q);
			$q_count->count("count_entries");
			$count = $q_count->execute_single_row();
			$count = $count["count_entries"];
			$q->limit(($page-1)*$nb, $nb);
		}
		
		//PNApplication::error($q->generate());
		$res = $q->execute();

		// compute sub-requests
		//echo "\r\nQ=".$q->generate()."\r\n\r\n";
		foreach ($paths as $p) {
			if ($p->is_unique()) continue;
			$sq = SQLQuery::create();
			
			$multiple_path = $p->parent;
			$next_paths = array();
			while ($multiple_path->unique) {
				array_push($next_paths, $multiple_path);
				$multiple_path = $multiple_path->parent;
			}
			
			$pk = $multiple_path->parent->table->getPrimaryKey();
			$table_alias = $q->get_table_alias($multiple_path->parent->table->getSQLNameFor($multiple_path->parent->sub_model));
			$key_alias = $q->get_field_alias($table_alias, $pk->name);
				
			if ($multiple_path->table_alias == null) $multiple_path->table_alias = $ctx->new_table_alias();
			$sq->select(array($multiple_path->table->getSQLNameFor($multiple_path->sub_model)=>$multiple_path->table_alias));
			for ($i = count($next_paths)-1; $i >= 0; $i--)
				$next_paths[$i]->append_sql($sq, $ctx);
			$sq->field($p->parent->table_alias, $p->field_name, $p->field_alias);

			$sq_key_alias = $ctx->new_field_alias();
			$sq->field($multiple_path->table_alias, $multiple_path->foreign_key->name, $sq_key_alias);
				
			$where = "";
			foreach ($res as $row) {
				if (strlen($where) > 0) $where .= " OR ";
				$where .= $multiple_path->table_alias.".".$multiple_path->foreign_key->name."='".$row[$key_alias]."'";
			}
			$sq->where($where);
			
			$sres = $sq->execute();
			//echo "\r\nSQ=".$sq->generate()."\r\n\r\n";
			//PNApplication::error($sq->generate()." = ".count($sres));
			foreach ($res as &$row) {
				$l = array();
				foreach ($sres as $srow) {
					$match = true;
					$i = 0;
					//echo "\r\n".$sq_key_alias."\r\n".$srow[$sq_key_alias]."\r\n".$key_alias."\r\n".$row[$key_alias];
					if ($srow[$sq_key_alias] == $row[$key_alias])
						array_push($l, $srow[$p->field_alias]);
				}
				$row[$p->field_alias] = $l;
			}
		}
		
		//echo $q->generate()."\r\n\r\n";

		if (!isset($input["export"])) {
			echo "{";
			if ($count !== null)
				echo "count:".$count.",";
			echo "data:[";
			for ($i = 0; $i < count($res); $i++) {
				if ($i>0) echo ",";
				echo "{values:[";
				for ($j = 0; $j < count($paths); $j++) {
					if ($j>0) echo ",";

					$order = array();
					$pa = $paths[$j];
					while ($pa <> null) {
						array_splice($order, 0, 0, array($pa));
						$pa = $pa->parent;
					}
					
					$done = false;
					for ($k = 0; $k < count($order); $k++) {
						$pa = $order[$k];
						if ($pa instanceof DataPath_Join) {
							if ($pa->foreign_key->multiple && $pa->parent->table <> $pa->foreign_key->table) {
								// 1<n: list of values
								// TODO
								echo "{v:".json_encode($res[$i][$paths[$j]->field_alias])."}";
								$done = true;
								break;
							}
							
							// check existence of an entity
							$key = null;
							if ($pa->parent->table == $pa->foreign_key->table) {
								$alias = $q->get_field_alias($pa->table_alias, $pa->table->getPrimaryKey()->name);
								$key = $res[$i][$alias];
							} else {
								$alias = $q->get_field_alias($pa->table_alias, $pa->foreign_key->name);
								$key = $res[$i][$alias];
							}
							if ($key === null) {
								// cannot continue in the path
								echo "{invalid:true}";
								$done = true;
								break;
							}
							
							// check if multiple link
							if ($pa->foreign_key->multiple) {
								// $pa->parent->table == $pa->foreign_key->table
								// n>1: it does not belong to us, we can propose the choice
								// here the value we will give is the key to go to the choice
								$alias = $q->get_field_alias($pa->table_alias, $pa->table->getPrimaryKey()->name);
								if ($alias == null)
									$alias = $q->get_field_alias($pa->parent->table_alias, $pa->foreign_key->name);
								if ($alias == null) {
									PNApplication::error("Missing primary key and foreign key for n to 1 link");
									echo "{v:'error'}";
								} else
									echo "{v:".json_encode($res[$i][$alias])."}";
								$done = true;
								break;
							}
						}
					}
					if (!$done) {
						echo "{v:";
						echo json_encode($res[$i][$paths[$j]->field_alias]);
						echo ",k:[";
						if ($paths[$j]->parent->table_primarykey_alias <> null)
							echo json_encode($paths[$j]->parent->table->getPrimaryKey()->name).",".json_encode($res[$i][$paths[$j]->parent->table_primarykey_alias]);
						else {
							// no primary key
							$keys = $this->get_keys_for($paths[$j]->parent->table, $paths[$j]->parent->sub_model);
							for ($k = 0; $k < count($keys); $k++) {
								if ($k>0) echo ",";
								$alias = $q->get_field_alias($paths[$j]->parent->table_alias, $keys[$k]->name);
								if ($alias <> null)
									echo json_encode($res[$i][$alias]);
								else // TODO DEBUG
									PNApplication::error("No alias for ".$paths[$j]->parent->table_alias.".".$keys[$k]->name." in ".$q->generate());
							}
						}
						echo "]";
						echo "}";
					}
				}
				echo "]";
				if ($actions !== null) {
					echo ",actions:[";
					$first = true;
					foreach ($actions as &$action) {
						if ($first) $first = false; else echo ",";
						$k = 0;
						$link = $action[0];
						while ($k < strlen($link) && ($k = strpos($link, "%", $k)) !== false) {
							$kk = strpos($link, "%", $k+1);
							if ($kk === false) break;
							$s = substr($link, $k+1, $kk-$k-1);
							$l = strpos($s, ".");
							$table = substr($s, 0, $l);
							$col = substr($s, $l+1);
							$alias = $q->get_field_alias($q->get_table_alias($table), $col);
							if ($alias == null) {
								PNApplication::error("Missing field '".$col."' from table '".$table."' (alias '".$q->get_table_alias($table)."') in SQL request ".$q->generate());
								$k = $kk+1;
								continue;
							}
							$link = substr($link, 0, $k).$res[$i][$alias].substr($link, $kk+1);
							$k = $k + strlen($res[$i][$alias]);
						}
						echo "{link:".json_encode($link).",icon:".json_encode($action[1])."}";
					}
					echo "]";
				}
				echo "}";
			}
			echo "]}";
		} else {
			// create excel
			error_reporting(E_ERROR | E_PARSE);
			require_once("component/php_excel/PHPExcel.php");
			$excel = new PHPExcel();
			$sheet = new PHPExcel_Worksheet($excel, "List");
			$excel->addSheet($sheet);
			$excel->removeSheetByIndex(0);
			$col_index = 0;
			foreach ($paths as $p) {
				$disp = $p->table->getDisplayableDataCategoryAndName($p->field_name);
				$sheet->setCellValueByColumnAndRow($col_index, 1, $disp[1]);
				$col_index++;
			}
			for ($i = 0; $i < count($res); $i++) {
				for ($j = 0; $j < count($paths); $j++) {
					$sheet->setCellValueByColumnAndRow($j, $i+2, $res[$i][$paths[$j]->field_alias]);
				}
			}
			
			$format = $input["export"];
			if ($format == 'excel2007') {
				header("Content-Type: application/vnd.ms-excel");
				header("Content-Disposition: attachment; filename=\"list.xlsx\"");
				$writer = new PHPExcel_Writer_Excel2007($excel);
			} else if ($format == 'excel5') {
				header("Content-Type: application/vnd.ms-excel");
				header("Content-Disposition: attachment; filename=\"list.xls\"");
				$writer = new PHPExcel_Writer_Excel5($excel);
			} else if ($format == 'csv') {
				header("Content-Type: text/csv;charset=UTF-8");
				header("Content-Disposition: attachment; filename=\"list.csv\"");
				echo "\xEF\xBB\xBF"; // UTF-8 BOM
				$writer = new PHPExcel_Writer_CSV($excel);
			} else if ($format == 'pdf') {
				header("Content-Type: application/pdf");
				header("Content-Disposition: attachment; filename=\"list.pdf\"");
				if (!PHPExcel_Settings::setPdfRenderer(PHPExcel_Settings::PDF_RENDERER_MPDF, dirname(__FILE__)."/../../lib_mpdf/MPDF")) {
					header("Content-Type: text/plain");
					echo "Error setting PDF renderer";
					return;
				}
				$writer = new PHPExcel_Writer_PDF($excel);
			} else {
				PNApplication::error("Unknown export format '".$format."'");
				return;
			}
			
			$writer->save('php://output');
		}
	}
	
	private function get_keys_for(&$table, $sub_model) {
		$pk = $table->getPrimaryKey();
		if ($pk <> null) return array($pk);
		foreach ($table->internalGetColumns($sub_model) as $col)
			if ($col->unique)
				return array($col);
		$unique = $table->getUnique();
		if ($unique <> null) {
			$keys = array();
			foreach ($unique as $u)
				array_push($keys, $table->internalGetColumn($u, $sub_model));
			return $keys;
		}
		return array();
	}
		
}
?>