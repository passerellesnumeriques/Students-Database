<?php 
namespace datamodel {

	/**
	 * Data screens define how to display data from tables, as well as the creation of new data
	 */
	interface DataScreen {
		/**
		 * Get the tables handled by the screen
		 * @return string[] list of tables
		 */
		public function getTables();
		/**
		 * Create new data
		 * @param array $paths paths of the data to be created
		 * @param boolean $multiple if true, several data are created at the same time
		 */
		public function createData($paths, $multiple);
	}
	
	/**
	 * Define a screen for a single data
	 */
	interface SimpleDataScreen extends DataScreen {
		
		/**
		 * Get the icon
		 * @return string the URL of the icon or null if no icon
		 */
		public function getIcon();
		/**
		 * Get the name of the section in which the screen will be displayed
		 * @return string the name
		 */
		public function getName();
		
		/**
		 * Generate the screen 
		 * @param Page $page the page in which the screen is generated
		 * @param DataPath[] $paths list of paths of the data to be displayed
		 * @param DataValues $values values already set
		 * @param DataValues $prefilled_values values to pre-fill
		 * @param string $structure_name for new data, name of the JSON structure to be filled
		 */
		public function generate(&$page, $paths, $values, $prefilled_values, $structure_name);
		/**
		 * Generate the end of the screen
		 * @param Page $page the page
		 * @param DataPath[] $paths the paths
		 * @param DataValues $values values already set
		 * @param DataValues $prefilled_values pre-filled values
		 * @param string|NULL $structure_name name of the JSON structure for new data
		 */
		public function generateEndOfSection(&$page, $paths, $values, $prefilled_values, $structure_name);
		
	}
	
	/**
	 * Define DataDisplay to be included when displaying several data at the same time
	 *
	 */
	interface MultipleDataScreen extends DataScreen {
		/**
		 * Get the list of displayable data
		 * @param string $come_from from where we come from
		 * @param integer|null $sub_model sub model
		 * @param boolean $for_new_data true if we are creating data, false if editing only
		 */
		public function getDataDisplay($come_from, $sub_model, $for_new_data);
	}
	
	/**
	 * Define a screen used when no DataScreen is defined on tables: show data in a table
	 */
	class GenericDataScreen implements DataScreen, SimpleDataScreen, MultipleDataScreen {
	
		/** @var \datamodel\TableDataDisplay $display provides the DataDisplay to be displayed */
		protected $display;
		
		/**
		 * @param \datamodel\TableDataDisplay $display provides the DataDisplay to be displayed
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
		 * Generate the table, with one DataDisplay per row
		 * @param Page $page the page
		 * @param DataPath $path the path to reach the table
		 * @param \datamodel\DataDisplay[] $list list of data to display
		 * @param array $fixed_columns values which cannot be edited
		 * @param DataValues $values values already set
		 * @param array $prefilled_values values pre-filled
		 * @param string $structure_name name of the JSON structure if we are creating new data
		 * @param boolean $is_new true if we are creating new data
		 * @return string the HTML
		 */
		protected function generateDataTable(&$page, $path, $list, $fixed_columns, $values, $prefilled_values, $structure_name, $is_new) {
			if ($path->sub_model == "@link") return;
			$script = "";
			$html = "<table class='data_screen'><tbody>";
			$has_something = false;
			$readonly = false;
			if (!$is_new) {
				$key_value = getKeyValue($path->table, $values);
				//echo "KEY_VALUE=".var_export($key_value,true)."<br/>\nTable=".$path->table->getName()."<br/>\nPath=".toHTML($path->getString())."<br/>\nValues=".var_export($values,true)."<br/>\n<br/>\n";
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
				if (!$data->canAccess($path->sub_model)) return;
				$can_edit = $is_new ? $data->isEditableForNewData() : ($data->isEditable() && !$readonly);
				if ($is_new && !$can_edit && !$values->hasDataValue($path->table->getName(), $data)) continue;
				foreach ($data->getHandledColumns() as $c) {
					if (isset($fixed_columns[$c]))
						unset($fixed_columns[$c]);
				}
				$has_something = true;
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
						$value = $data->getData($row, $keys);
					else {
						$rows = array($row);
						$data->performSubRequests($q, $rows, $keys, $path);
						$value = $data->getData($rows[0], $keys);
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
				$sub = $data->getSubDataDisplay();
				$sub_indexes = array();
				if ($sub <> null && ($is_new || $can_edit)) {
					$sub_names = $sub->getDisplayNames();
					for ($sub_index = 0; $sub_index < count($sub_names); $sub_index++)
						if ($is_new && $sub->isEditableForNew($sub_index))
							array_push($sub_indexes, $sub_index);
						else if (!$is_new && $sub->isEditable($sub_index))
							array_push($sub_indexes, $sub_index);
				}
				if (count($sub_indexes) > 0) {
					$html .= "<tr>";
					$html .= "<td colspan=2 class='sub_data_title'>".toHTML($data->getDisplayName())."</td>";
					$html .= "</tr>";
					$sub_names = $sub->getDisplayNames();
					if ($is_new)
						$script .= "new_data = ".json_encode($value).";\n";
					$first_sub_index = true;
					foreach ($sub_indexes as $sub_index) {
						$html .= "<tr class='sub_data'>";
						$html .= "<td>".toHTML($sub_names[$sub_index])."</td>";
						$id = $page->generateID();
						$html .= "<td id='$id'>";
						if ($is_new) {
							$script .= "field = new ".$tf[0]."(new_data,".json_encode($can_edit).",objectMerge(".$tf[1].",{sub_data_index:$sub_index}));\n";
							$script .= "field.fillWidth();\n";
							$script .= "document.getElementById('$id').appendChild(field.getHTMLElement());\n";
							if ($structure_name <> null && $first_sub_index)
								$script .= "path.data.push({name:".json_encode($data->getDisplayName()).",sub_index:$sub_index,field:field,data:new_data,getValue:function(){return this.field.getCurrentData();},validate:function(){ var fields = window.top.sub_field_registry.getFields(window,this.data);for (var i = 0; i < fields.length; ++i) if (fields[i].error) return fields[i].error; return null; }});\n";
						} else if ($can_edit) {
							$from = $path instanceof DataPath_Join && $path->isReverse() ? $path->foreign_key_name : null;
							$page->addJavascript("/static/data_model/datadisplay.js");
							$page->addJavascript("/static/data_model/editable_field.js");
							$page->addJavascript("/static/data_model/editable_datadisplay.js");
							$script .= "field = new editable_datadisplay(document.getElementById('$id'), ".$data->javascriptDataDisplay($path->sub_model).", ".json_encode($from).", ".json_encode($kv).", ".json_encode($path->sub_model).", ".json_encode($value).");\n";
							$script .= "field.editable_field.fillContainer();\n";
						}
						$html .= "</td>";
						$html .= $this->endDataRow($data, $value, $sub_index, $is_new, $can_edit);
						$html .= "</tr>";
						$first_sub_index = false;
					}
				} else {
					$html .= "<tr>";
					$html .= "<td>".toHTML($data->getDisplayName())."</td>";
					$id = $page->generateID();
					$html .= "<td id='$id'>";
					if ($is_new) {
						$script .= "field = new ".$tf[0]."(".json_encode($value).",".json_encode($can_edit).",".$tf[1].");\n";
						$script .= "field.fillWidth();\n";
						$script .= "document.getElementById('$id').appendChild(field.getHTMLElement());\n";
						if ($structure_name <> null)
							$script .= "path.data.push({name:".json_encode($data->getDisplayName()).",sub_index:-1,field:field,getValue:function(){return this.field.getCurrentData();},validate:function(){ return this.field.error; }});\n";
					} else if ($can_edit) {
						$from = $path instanceof DataPath_Join && $path->isReverse() ? $path->foreign_key_name : null;
						$page->addJavascript("/static/data_model/datadisplay.js");
						$page->addJavascript("/static/data_model/editable_field.js");
						$page->addJavascript("/static/data_model/editable_datadisplay.js");
						$script .= "field = new editable_datadisplay(document.getElementById('$id'), ".$data->javascriptDataDisplay($path->sub_model).", ".json_encode($from).", ".json_encode($kv).", ".json_encode($path->sub_model).", ".json_encode($value).");\n";
						$script .= "field.editable_field.fillContainer();\n";
					} else {
						$page->addJavascript("/static/data_model/datadisplay.js");
						$script .= "field = new ".$tf[0]."(".json_encode($value).",false,".$tf[1].");\n";
						$script .= "document.getElementById('$id').appendChild(field.getHTMLElement());\n";
						$script .= "field.setDataDisplay(".$data->javascriptDataDisplay($path->sub_model).",".json_encode($kv).");\n";
					}
					$html .= "</td>";
					$html .= $this->endDataRow($data, $value, -1, $is_new, $can_edit);
					$html .= "</tr>";
				}
			}
			if (!$has_something) return "";
			$html .= "</tbody></table>";
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
		 * @param \datamodel\DataDisplay $data the data
		 * @param mixed $value the initial value
		 * @param integer $sub_index if this is a data display having sub-data
		 * @param boolean $is_new true if this is for a new data
		 * @param boolean $can_edit true if this is editable
		 * @return string the HTML
		 */
		protected function endDataRow($data, $value, $sub_index, $is_new, $can_edit) {
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
				foreach ($path->children as $child) {
					if (!$child->isReverse()) // the child is a direct join => we need to create it first
						array_push($children_before, $child);
					else
						array_push($children_after, $child);
				}
			}

			if (count($children_before) > 0) {
				$this->createChildrenData($children_before, $multiple, $to_update, $path->sub_model);
			}

			$this->display->finalizeCreateData($key, $path->sub_model, $to_update, $come_from);

			$keys = $to_update->execute(true);
			if (isset($keys[$path->table->getName()][$path->sub_model][$key]))
				$key = $keys[$path->table->getName()][$path->sub_model][$key];
			foreach ($path->children as $child)
				if ($child->isReverse())
					$child->columns[$child->foreign_key->name] = $key;
			
			if (count($children_after) > 0)
				$this->createChildrenData($children_after, $multiple, $to_update, $path->sub_model);
			
			if ($key == null && $path->table->getPrimaryKey() <> null) {
				$key = @$to_update->getValue($path->table->getName(), $path->sub_model, null, $path->table->getPrimaryKey()->name);
			}
			
			return $key;
		}
		
		/**
		 * Create new data for data reachable from this table
		 * @param DataPath[] $children the data reachable from this table
		 * @param boolean $multiple true if the screen is for several data
		 * @param \TablesToUpdate $to_update the values to insert/update in the database 
		 * @param integer|null $parent_sub_model the sub model
		 */
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