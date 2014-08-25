<?php 
namespace datamodel {

	interface DataScreen {
		public function getTables();
		public function createData($paths, $multiple);
	}
	
	interface SimpleDataScreen extends DataScreen {
		
		public function getIcon();
		public function getName();
		
		public function generate(&$page, $paths, $values, $prefilled_values, $structure_name);
		public function generateEndOfSection(&$page, $paths, $values, $prefilled_values, $structure_name);
		
	}
	
	interface MultipleDataScreen extends DataScreen {
		public function getDataDisplay($come_from, $sub_model, $for_new_data);
	}
	
	class GenericDataScreen implements DataScreen, SimpleDataScreen, MultipleDataScreen {
	
		/** @var \datamodel\TableDataDisplay $display */
		protected $display;
		
		/**
		 * @param \datamodel\TableDataDisplay $display
		 */
		public function __construct($display) {
			$this->display = $display;
		}
		
		public function getTables() { return array($this->display->getTableName()); }
	
		public function getIcon() { return $this->display->getCategory()->getIcon(); }
		public function getName() { return $this->display->getCategory()->getName(); }
		
		public function getDataDisplay($come_from, $sub_model, $for_new_data) {
			return $this->display->getDataDisplay($come_from, $sub_model);
		}
		
		public function generate(&$page, $paths, $values, $prefilled_values, $structure_name) {
			$path = $paths[0];
			$from = $path instanceof DataPath_Join && $path->isReverse() ? $path->foreign_key_name : null;
			//var_dump($values);
			//$key_value = getKeyValue($path->table, $values);
			$is_new = $structure_name <> null;
			$fixed_columns = array_merge($values->getColumns($path->table->getName()));
			echo $this->generateDataTable($page, $path, $this->display->getDataDisplay($from, $path->sub_model), $fixed_columns, $values, $prefilled_values, $structure_name, $is_new);
		}
		
		public function generateEndOfSection(&$page, $paths, $values, $prefilled_values, $structure_name) {}
		
		/**
		 * 
		 * @param Page $page
		 * @param DataPath $path
		 * @param \datamodel\DataDisplay[] $list
		 * @param array $fixed_columns
		 * @param DataValues $values
		 * @param array $prefilled_values
		 * @param string $structure_name
		 * @param boolean $is_new
		 * @return string
		 */
		protected function generateDataTable(&$page, $path, $list, $fixed_columns, $values, $prefilled_values, $structure_name, $is_new) {
			$script = "";
			$html = "<table>";
			$has_something = false;
			$readonly = false;
			if (!$is_new) {
				$key_value = getKeyValue($path->table, $values);
				if (!$path->table->mayModify() && !$path->table->mayInsert() && !$path->table->mayRemove()) {
					$readonly = true;
				} else {
					$table_alias = "THE_TABLE";
					$q = \SQLQuery::create()->select(array($path->table->getName()=>$table_alias))->selectSubModelForTable($path->table, $path->sub_model);
					$q->whereKey($path->table->getName(), $key_value);
					$path->table->prepareSelectToDetermineWriteAccess($q, $table_alias);
					if ($q->limit(0,1)->executeSingleRow() == null)
						$readonly = true;
				}
			}
			foreach ($list as $data) {
				$can_edit = $is_new ? $data->isEditableForNewData() : ($data->isEditable() && !$readonly);
				if ($is_new && !$can_edit && !$values->hasDataValue($path->table->getName(), $data)) continue;
				foreach ($data->getHandledColumns() as $c) {
					if (isset($fixed_columns[$c]))
						unset($fixed_columns[$c]);
				}
				$has_something = true;
				$html .= "<tr>";
				$html .= "<td>".toHTML($data->getDisplayName())."</td>";
				$id = $page->generateID();
				$html .= "<td id='$id'>";
				$tf = $data->getTypedField($path->sub_model);
				if (!$is_new)
					$kv = $key_value;
				if ($values->hasDataValue($path->table->getName(), $data))
					$value = $values->getDataValue($path->table->getName(), $data);
				else if ($is_new && $prefilled_values->hasDataValue($path->table->getName(), $data))
					$value = $prefilled_values->getDataValue($path->table->getName(), $data);
				else if ($is_new)
					$value = $data->getNewData();
				else {
					$table_alias = "THE_TABLE";
					$q = \SQLQuery::create()->select(array($path->table->getName()=>$table_alias))->selectSubModelForTable($path->table, $path->sub_model);
					$q->whereKey($path->table->getName(), $key_value);
					$keys = $data->buildSQL($q, $path);
					$row = $q->executeSingleRow();
					if ($keys["key"] <> null)
						$kv = $row[$keys["key"]];
					if ($keys["data"] <> null)
						$value = @$row[$keys["data"]];
					else {
						$rows = array($row);
						$data->performSubRequests($q, $rows, $keys, $path);
						$value = @$rows[0][$keys["data"]];
					}
				}
				if (!$is_new)
					$kv = $data->getKeyFromTableKey($kv);
				$page->addJavascript("/static/widgets/typed_field/typed_field.js");
				$page->requireJavascript($tf[0].".js");
				// check can really edit the columns
				if (!$is_new && $can_edit) {
					foreach ($data->getHandledColumns() as $c)
						if (!$path->table->canModifyColumn($c)) { $can_edit = false; break; }
				}
				if ($is_new) {
					$script .= "field = new ".$tf[0]."(".json_encode($value).",".json_encode($can_edit).",".$tf[1].");\n";
					$script .= "field.fillWidth();\n";
					$script .= "document.getElementById('$id').appendChild(field.getHTMLElement());\n";
					if ($structure_name <> null)
						$script .= "path.data.push({name:".json_encode($data->getDisplayName()).",field:field,getValue:function(){return this.field.getCurrentData();},validate:function(){ return this.field.error; }});\n";
				} else if ($can_edit) {
					$from = $path instanceof DataPath_Join && $path->isReverse() ? $path->foreign_key_name : null;
					$page->addJavascript("/static/data_model/datadisplay.js");
					$page->addJavascript("/static/data_model/editable_field.js");
					$page->addJavascript("/static/data_model/editable_datadisplay.js");
					$script .= "field = new editable_datadisplay(document.getElementById('$id'), ".$data->javascriptDataDisplay($path->sub_model).", ".json_encode($from).", ".json_encode($kv).", ".json_encode($value).");\n";
					$script .= "field.editable_field.fillContainer();\n";
				} else {
					$script .= "field = new ".$tf[0]."(".json_encode($value).",false,".$tf[1].");\n";
					$script .= "document.getElementById('$id').appendChild(field.getHTMLElement());\n";
				}
				$html .= "</td>";
				$html .= $this->endDataRow($data, $value, $is_new, $can_edit);
				$html .= "</tr>";
			}
			if (!$has_something) return "";
			$html .= "</table>";
			$html .= "<script type='text/javascript'>\n";
			$html .= "var field;\n";
			if ($structure_name <> null) {
				$html .= "var path = {path:".json_encode($path->getString()).",data:[],columns:{";
				$first = true;
				foreach ($fixed_columns as $cname=>$val) {
					if ($first) $first = false; else $html .= ",";
					$html .= $cname.":".json_encode($val);
				}
				$html .= "}";
				$html .= ",getValue:function(){ var v=[]; for (var i = 0; i < this.data.length; ++i) v.push({name:this.data[i].name,value:this.data[i].getValue()}); return v;}";
				$html .= ",validate:function(){ for (var i = 0; i < this.data.length; ++i) { var e = this.data[i].validate(); if (e != null) return this.data[i].name+': '+e; } return null; }";
				$html .= "};\n";
				$html .= "$structure_name.push(path);\n";
			}
			$html .= $script;
			$html .= "</script>\n";
			return $html;
		}
		
		/** Optionally add some columns/info at the end of the row for a DataDisplay
		 * @param \datamodel\DataDisplay $data
		 * @param unknown $value
		 * @param boolean $is_new
		 * @param boolean $can_edit
		 * @return string
		 */
		protected function endDataRow($data, $value, $is_new, $can_edit) {
			return "";
		}
	
		public function createData($paths, $multiple) {
			$path = $paths[0];
			$come_from = ($path instanceof \DataPath_Join) && $path->isReverse() ? $path->foreign_key->name : null;
			// list of DataDisplay
			$to_update = new \TablesToUpdate();
			if ($path->table->getPrimaryKey() <> null)
				$key = @$path->columns[$path->table->getPrimaryKey()->name];
			else {
				$key_cols = $path->table->getKey();
				$k = array();
				foreach ($key_cols as $col)
					if (isset($path->columns[$col]))
						$k[$col] = $path->columns[$col];
					else { $k = null; break; }
				if ($k == null) $key = null;
				else $key = json_encode($k);
			}
			if ($path->unique)
				foreach ($path->columns as $cname=>$val)
					$to_update->addValue($path->table->getName(), $path->sub_model, $key, $cname, $val);
			foreach ($this->getDataDisplay($come_from, $path->sub_model, true) as $data) {
				$found = false;
				$val = null;
				foreach ($path->value as $cd)
					if ($cd["name"] == $data->getDisplayName()) {
					$found = true;
					$val = $cd["value"];
					break;
				}
				if (!$found) continue;
				$join = null;
				if ($come_from <> null) {
					if (isset($path->columns[$come_from]))
						$join = $path->columns[$come_from];
					else if (isset($path->parent->columns[$path->parent->table->getPrimaryKey()->name]))
						$join = $path->parent->columns[$path->parent->table->getPrimaryKey()->name];
				}
				$data->saveData($key, $val, $path->sub_model, $to_update, $come_from, $join);
			}
			$this->display->finalizeCreateData($key, $path->sub_model, $to_update, $come_from);
			
			// check if we are on a table joining 2 tables => if we come from one side, we need first to create the other side so we can join
			$children_before = array();
			$children_after = array();
			if ($come_from <> null) { // we are on a reverse join
				foreach ($path->children as $child) {
					if (!$child->isReverse()) // the child is a direct join => we are probably on a joining table
						array_push($children_before, $child);
					else
						array_push($children_after, $child);
				}
			} else {
				foreach ($path->children as $child)
					array_push($children_after, $child);
			}

			if (count($children_before) > 0)
				$this->createChildrenData($children_before, $multiple, $to_update, $path->sub_model);
			
			$keys = $to_update->execute(true);
			if (isset($keys[$path->table->getName()][$path->sub_model][$key]))
				$key = $keys[$path->table->getName()][$path->sub_model][$key];
			foreach ($path->children as $child)
				if ($child->isReverse())
					$child->columns[$child->foreign_key->name] = $key;
			
			if (count($children_after) > 0)
				$this->createChildrenData($children_after, $multiple, $to_update, $path->sub_model);
			
			return $key;
		}
		
		private function createChildrenData($children, $multiple, &$to_update, $parent_sub_model) {
			// check DataScreen for children
			foreach (\DataModel::get()->getDataScreens() as $screen) {
				if (!$multiple && !($screen instanceof \datamodel\SimpleDataScreen)) continue;
				if ($multiple && !($screen instanceof \datamodel\MultipleDataScreen)) continue;
				$tables = $screen->getTables();
				$paths = array();
				foreach ($tables as $t) {
					foreach ($children as $child)
						if ($child->table->getName() == $t) {
							array_push($paths, $child);
							break;
						}
				}
				if (count($paths) == count($tables)) {
					$key = $screen->createData($paths, $multiple);
					// remove children handled by the screen
					foreach ($paths as $p) {
						if (!$p->isReverse()) {
							// the key will be interesting for the parent path
							$to_update->addValue($p->foreign_key->table->getName(), $parent_sub_model, null, $p->foreign_key->name, $key);
						}
						for ($i = 0; $i < count($children); $i++)
							if ($children[$i] == $p) {
								array_splice($children, $i, 1);
								break;
							}
					}
				}
			}
			// create data for remaining children
			foreach ($children as $child) {
				$come_from = null;
				if ($child instanceof DataPath_Join) {
					if ($child->isReverse()) {
						$come_from = $child->foreign_key->name;
					} else {
					}
				}
				$found = false;
				foreach (\DataModel::get()->getDataScreens() as $screen) {
					if (!$multiple && !($screen instanceof \datamodel\SimpleDataScreen)) continue;
					if ($multiple && !($screen instanceof \datamodel\MultipleDataScreen)) continue;
					$tables = $screen->getTables();
					if (count($tables) == 1 && $tables[0] == $child->table->getName()) {
						$key = $screen->createData(array($child), $multiple);
						if ($come_from == null) {
							// the key will be interesting for the parent path
							$to_update->addValue($child->foreign_key->table->getName(), $parent_sub_model, null, $child->foreign_key->name, $key);
						}
						$found = true;
						break;
					}
				}
				if (!$found) {
					$display = \DataModel::get()->getTableDataDisplay($child->table->getName());
					$screen = new GenericDataScreen($display);
					$key = $screen->createData(array($child), $multiple);
					if ($come_from == null) {
						// the key will be interesting for the parent path
						$to_update->addValue($child->foreign_key->table->getName(), $parent_sub_model, null, $child->foreign_key->name, $key);
					}
				}
			}							
		}
	
	}
	
}
?>