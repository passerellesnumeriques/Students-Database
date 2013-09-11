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
	public function get_output_format() {
		if (isset($_POST["export"])) {
			$format = $_POST["export"];
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
	
	public function execute(&$component) {
		$table = $_POST["table"];
		$fields = json_decode($_POST["fields"]);
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
		foreach ($paths as $p)
			if ($p->is_unique())
				$p->append_sql($q, $ctx);

		$actions = null;
		if (isset($_POST["actions"]) && $_POST["actions"]) {
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
					$alias = $q->get_field_alias($q->get_table_alias($table), $col, $ctx->new_field_alias());
					if ($alias == null)
						$q->field($table, $col);
					$k = $kk+1;
					continue;
				}
			}
		}
		
		//PNApplication::error($q->generate());
		$res = $q->execute();

		// compute sub-requests
		foreach ($paths as $p) {
			if ($p->is_unique()) continue;
			$sq = SQLQuery::create();
			$multiple_path = $p->parent;
			$next_paths = array();
			while ($multiple_path->unique) {
				array_push($next_paths, $multiple_path);
				$multiple_path = $multiple_path->parent;
			}
			if ($multiple_path->table_alias == null) $multiple_path->table_alias = $ctx->new_table_alias();
			$sq->select(array($multiple_path->table->getSQLNameFor($multiple_path->sub_model)=>$multiple_path->table_alias));
			for ($i = count($next_paths)-1; $i >= 0; $i--)
				$next_paths[$i]->append_sql($sq, $ctx);
			$sq->field($p->parent->table_alias, $p->field_name, $p->field_alias);
			$keys_aliases = array();
			foreach ($multiple_path->matching_fields as $src=>$dst) {
				$alias = $ctx->new_field_alias();
				array_push($keys_aliases, $alias);
				$sq->field($multiple_path->table_alias, $dst, $alias);
			}
			$where = "";
			foreach ($res as $row) {
				if (strlen($where) > 0) $where .= " OR ";
				$where .= "(";
				$first = true;
				foreach ($multiple_path->matching_fields as $src=>$dst) {
					if ($first) $first = false; else $where .= " AND ";
					$where .= "`".$multiple_path->table_alias."`.`".$dst."`='";
					$where .= $sq->escape($row[$q->get_field_alias($multiple_path->parent->table_alias, $src)]);
					$where .= "'";
				}
				$where .= ")";
			}
			$sq->where($where);
			//PNApplication::error($sq->generate());
			$sres = $sq->execute();
			foreach ($res as &$row) {
				$l = array();
				foreach ($sres as $srow) {
					$match = true;
					$i = 0;
					foreach ($multiple_path->matching_fields as $src=>$dst) {
						//echo "Try: ".$srow[$keys_aliases[$i]]." => ".$row[$q->get_field_alias($multiple_path->parent->table_alias, $src)]."\r\n";
						if ($srow[$keys_aliases[$i]] <> $row[$q->get_field_alias($multiple_path->parent->table_alias, $src)]) {
							$match = false;
							break;
						}
						$i++;
					}
					if ($match)
						array_push($l, $srow[$p->field_alias]);
				}
				$row[$p->field_alias] = $l;
			}
		}

		if (!isset($_POST["export"])) {
			echo "{";
			
			echo "tables:[";
			for ($j = 0; $j < count($paths); $j++) {
				if ($j>0) echo ",";
				echo "{name:".json_encode($paths[$j]->parent->table->getName());
				echo ",sub_model:".json_encode($paths[$j]->parent->sub_model);
				echo ",keys:[";
				$keys = $this->get_keys_for($paths[$j]->parent->table, $paths[$j]->parent->sub_model);
				for ($i = 0; $i < count($keys); $i++) {
					if ($i>0) echo ",";
					echo json_encode($keys[$i]->name);
				};
				echo "]";
				echo "}";
			}
			echo "]";
			echo ",data:[";
			for ($i = 0; $i < count($res); $i++) {
				if ($i>0) echo ",";
				echo "{values:[";
				for ($j = 0; $j < count($paths); $j++) {
					if ($j>0) echo ",";
					if ($paths[$j]->is_unique()) {
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
					} else {
						//echo "{v:\"TODO: multiple values\"}";
						// TODO multiple
						echo "{v:".json_encode($res[$i][$paths[$j]->field_alias])."}";
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
			
			$format = $_POST["export"];
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
		foreach ($table->getLinks() as $link)
			if ($link->unique) {
				$keys = array();
				foreach ($link->fields_matching as $field=>$field2)
					array_push($keys, $link->table_from->internalGetColumn($field));
				return $keys;
			}
		return array();
	}
		
}
?>