<?php 
namespace datamodel {
	
	abstract class DataScreen {
		
		public abstract function getTables();
		
		public abstract function getIcon();
		public abstract function getName();
		
		public abstract function generate(&$page, $paths, $values, $prefilled_values, $structure_name);
		
		public abstract function createData($paths, $multiple);
		
	}
	
	class GenericDataScreen extends DataScreen {
	
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
		
		public function generate(&$page, $paths, $values, $prefilled_values, $structure_name) {
			$path = $paths[0];
			$from = $path instanceof DataPath_Join && $path->isReverse() ? $path->foreign_key_name : null;
			//var_dump($values);
			$key_value = getKeyValue($path->table, $values);
			$is_new = $key_value == null || intval($key_value) < 0;
			$fixed_columns = array_merge($values->getColumns($path->table->getName()));
			echo $this->generateDataTable($page, $path, $this->display->getDataDisplay($from), $fixed_columns, $values, $prefilled_values, $structure_name, $is_new);
		}
		
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
			foreach ($list as $data) {
				$can_edit = $is_new ? $data->isEditableForNewData() : $data->isEditable();
				if ($is_new && !$can_edit) continue;
				foreach ($data->getHandledColumns() as $c) {
					if (isset($fixed_columns[$c]))
						unset($fixed_columns[$c]);
				}
				$has_something = true;
				$html .= "<tr>";
				$html .= "<td>".htmlentities($data->getDisplayName())."</td>";
				$id = $page->generateID();
				$html .= "<td id='$id'>";
				$tf = $data->getTypedField($path->sub_model);
				if ($values->hasDataValue($path->table->getName(), $data))
					$value = $values->getDataValue($path->table->getName(), $data);
				else if ($is_new && $prefilled_values->hasDataValue($path->table->getName(), $data))
					$value = $prefilled_values->getDataValue($path->table->getName(), $data);
				else
					$value = $data->getNewData();
				$page->addJavascript("/static/widgets/typed_field/typed_field.js");
				$page->requireJavascript($tf[0].".js");
				// TODO check can really edit the columns
				if ($is_new) {
					$script .= "field = new ".$tf[0]."(".json_encode($value).",".json_encode($can_edit).",".$tf[1].");\n";
					$script .= "document.getElementById('$id').appendChild(field.getHTMLElement());\n";
					if ($structure_name <> null)
						$script .= "path.data.push({name:".json_encode($data->getDisplayName()).",field:field,getValue:function(){return this.field.getCurrentData();},validate:function(){ return this.field.error; }});\n";
				} else if ($can_edit) {
					$from = $path instanceof DataPath_Join && $path->isReverse() ? $path->foreign_key_name : null;
					$key_value = getKeyValue($path->table, $values);
					$page->addJavascript("/static/data_model/DataDisplay.js");
					$page->addJavascript("/static/data_model/editable_field.js");
					$page->addJavascript("/static/data_model/editable_datadisplay.js");
					$script .= "field = new editable_datadisplay(document.getElementById('$id'), ".$data->javascriptDataDisplay($path->sub_model).", ".json_encode($from).", ".json_encode($key_value).", ".json_encode($value).");\n";
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
			foreach ($this->display->getDataDisplay($come_from) as $data) {
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
			$keys = $to_update->execute(true);
			if (isset($keys[$path->table->getName()][$path->sub_model][$key]))
				$key = $keys[$path->table->getName()][$path->sub_model][$key];
			foreach ($path->children as $child)
				if ($child->isReverse())
					$child->columns[$child->foreign_key->name] = $key;
			
			// check DataScreen for children
			if (!$multiple)
			foreach (\DataModel::get()->getDataScreens() as $screen) {
				$tables = $screen->getTables();
				$paths = array();
				foreach ($tables as $t) {
					foreach ($path->children as $child)
						if ($child->table->getName() == $t) {
						array_push($paths, $child);
						break;
					}
				}
				if (count($paths) == count($tables)) {
					$screen->createData($paths, $multiple);
					// remove children handled by the screen
					foreach ($paths as $p) {
						for ($i = 0; $i < count($path->children); $i++)
							if ($path->children[$i] == $p) {
								array_splice($path->children, $i, 1);
								break;
							}
					}
				}
			}
			// create data for remaining children
			foreach ($path->children as $child) {
				$come_from = null;
				if ($child instanceof DataPath_Join) {
					if ($child->isReverse()) {
						$come_from = $child->foreign_key->name;
					} else {
					}
				}
				$found = false;
				if (!$multiple)
				foreach (\DataModel::get()->getDataScreens() as $screen) {
					$tables = $screen->getTables();
					if (count($tables) == 1 && $tables[0] == $child->table->getName()) {
						$screen->createData(array($child), $multiple);
						$found = true;
						break;
					}				
				}
				if (!$found) {
					$display = \DataModel::get()->getTableDataDisplay($child->table->getName());
					$screen = new GenericDataScreen($display);
					$screen->createData(array($child), $multiple);
				}
			}
			return $key;
		}
	
	}
	
}
?>