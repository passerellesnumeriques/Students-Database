<?php
require_once("component/data_model/Model.inc");

class DataValues {
	
	private $tables = array();
	
	public function addTableColumnValue($table_name, $column_name, $value) {
		if (!isset($this->tables[$table_name]))
			$this->tables[$table_name] = new TableDataValues();
		$this->tables[$table_name]->addColumnValue($column_name, $value);
	}
	public function addTableDataValue($table_name, $data_name, $value) {
		if (!isset($this->tables[$table_name]))
			$this->tables[$table_name] = new TableDataValues();
		$this->tables[$table_name]->addDataValue($data_name, $value);
	}
	
	public function hasColumnValue($table_name, $column_name) {
		if (!isset($this->tables[$table_name])) return false;
		return $this->tables[$table_name]->hasColumnValue($column_name);
	}
	public function hasDataValue($table_name, $data) {
		if (!isset($this->tables[$table_name])) return false;
		return $this->tables[$table_name]->hasDataValue($data);
	}
	
	public function getColumnValue($table_name, $column_name) {
		return $this->tables[$table_name]->getColumnValue($column_name);
	}
	public function getDataValue($table_name, $data) {
		return $this->tables[$table_name]->getDataValue($data);
	}
	
	public function getColumns($table_name) {
		if (!isset($this->tables[$table_name])) return array();
		return $this->tables[$table_name]->getColumns();
	}
	
}

class TableDataValues {
	
	private $columns_values = array();
	private $data_values = array();
	
	public function addColumnValue($column_name, $value) {
		$this->columns_values[$column_name] = $value;
	}
	public function addDataValue($data_name, $value) {
		$this->data_values[$data_name] = $value;
	}
	
	public function hasColumnValue($column_name) {
		return isset($this->columns_values[$column_name]);
	}
	public function hasDataValue($data) {
		if (isset($this->data_values[$data->getDisplayName()])) return true;
		$cell = $data->getSingleCell();
		if ($cell <> null) {
			return isset($this->columns_values[$cell["column"]]);
		}
		$columns = $data->getHandledColumns();
		$ok = true;
		foreach ($columns as $cname) if (!isset($this->columns_values[$cname])) { $ok = false; break; }
		return $ok;
	}
	
	public function getColumnValue($column_name) {
		return @$this->columns_values[$column_name];
	}
	public function getDataValue($data) {
		if (isset($this->data_values[$data->getDisplayName()]))
			return $this->data_values[$data->getDisplayName()];
		$cell = $data->getSingleCell();
		if ($cell <> null) {
			if (isset($this->columns_values[$cell["column"]]))
				return $this->columns_values[$cell["column"]];
		}
		// TODO
		return null;
	}
	
	public function getColumns() { return $this->columns_values; }
}

function existingDataScreenFromKey(&$page, $root_table_name, $sub_model, $row_key) {
	$row = SQLQuery::create()->select($root_table_name)->selectSubModelForTable($root_table_name, $sub_model)->whereKey($root_table_name, $row_key)->executeSingleRow();
	return existingDataScreenFromRow($page, $root_table_name, $sub_model, $row, null);
}
function existingDataScreenFromRow(&$page, $root_table_name, $sub_model, $row) {
	$values = new DataValues();
	foreach ($row as $col=>$val)
		$values->addTableColumnValue($root_table_name, $col, $val);
	$prefilled_values = new DataValues();
	return dataScreen($page, $root_table_name, $sub_model, $values, $prefilled_values, null);
}

function newDataScreen(&$page, $root_table_name, $sub_model, $fixed_values, $prefilled_values, &$structure_name) {
	$structure_name = $page->generateID();
	$html = "";
	$html .= "<script type='text/javascript'>\n";
	$html .= "$structure_name = [];\n";
	$html .= "</script>\n";
	$html .= dataScreen($page, $root_table_name, $sub_model, $fixed_values, $prefilled_values, $structure_name);
	return $html;
}


function dataScreen(&$page, $root_table_name, $sub_model, $values, $prefilled_values, $structure_name) {
	require_once("component/data_model/DataPath.inc");
	$paths = DataPathBuilder::searchFrom($root_table_name, $sub_model);
	$root = DataPathBuilder::buildPathsTree($paths);
	
	$sections = array();
	
	// handle root
	$screens = DataModel::get()->getDataScreens();
	$screen = null;
	foreach ($screens as $s) {
		$tables = $s->getTables();
		if (count($tables) == 1 && $tables[0] == $root->table->getName()) {
			$screen = $s;
			break;
		}
	}
	if ($screen <> null) {
		buildDataScreen($page, array($root->table->getName()=>$root), $screen, $values, $prefilled_values);
	} else {
		$display = DataModel::get()->getTableDataDisplay($root_table_name);
		if ($display == null) return;
		$h = buildDataScreenFromDisplay($page, $root, $display, null, $values, $prefilled_values, $structure_name);
		$category = $display->getCategory();
		array_push($sections, array($category->getIcon(), $category->getName(), $h));
	}
	
	buildDataScreenChildren($page, $root, $values, $prefilled_values, $sections, $structure_name);
	
	$page->require_javascript("section.js");
	$html = "";
	foreach ($sections as $s) {
		$id = $page->generateID();
		$html .= "<div id='$id'";
		if ($s[0] <> null) $html .= " icon='".$s[0]."'";
		$html .= " title=\"".htmlentities($s[1])."\"";
		$html .= " collapsable='false' css='soft' style='display:inline-block;margin:10px'>";
		$html .= $s[2];
		$html .= "</div>";
		$html .= "<script type='text/javascript'>section_from_html('$id');</script>";
	}
	return $html;
}

function buildDataScreenChildren(&$page, $path, $values, $prefilled_values, &$sections, $structure_name) {
	$screens = DataModel::get()->getDataScreens();
	$avail_screens = array();
	foreach ($screens as $s) {
		$tables = $s->getTables();
		$ok = true;
		$children = array();
		foreach ($tables as $table_name) {
			$found = false;
			for ($i = 0; $i < count($path->children); $i++) {
				$child = $path->children[$i];
				if ($child->table->getName() == $table_name) {
					array_push($children, $child);
					$found = true;
					array_splice($path->children, $i, 1);
					break;
				}
			}
			if (!$found) { $ok = false; break; }
		}
		if (!$ok) foreach ($children as $child) array_push($path->children, $child);
		else {
			array_push($avail_screens, array($s, $children));
		}
	}
	foreach ($avail_screens as $s) {
		$ob = ob_get_contents();
		ob_clean();
		$s[0]->generate($page, $s[1], $values, $prefilled_values, $structure_name);
		$h = ob_get_contents();
		ob_clean();
		echo $ob;
		if ($h <> "")
			array_push($sections, array(
				$s[0]->getIcon(),
				$s[0]->getName(),
				$h
			));
	}
	$parent_display = DataModel::get()->getTableDataDisplay($path->table->getName());
	foreach ($path->children as $child) {
		$display = DataModel::get()->getTableDataDisplay($child->table->getName());
		if ($display == null) continue;
		if ($child->isReverse()) {
			if (!$parent_display->canJoinToPrimary()) continue;
			if (!$display->canJoinFrom($child->foreign_key->name)) continue;
			$columns = $display->getNeededColumnsToJoinFrom($child->foreign_key->name);
			if ($columns <> null) {
				$cv = array();
				foreach ($columns as $cname) {
					$v = null;
					if ($values->hasColumnValue($path->table->getName(), $cname))
						$v = $values->getColumnValue($path->table->getName(), $cname);
					$cv[$cname] = $v;
				}
				if (!$display->checkJoinFrom($child->foreign_key->name, $cv)) continue;
			}
		} else {
			if (!$parent_display->canJoinFrom($child->foreign_key->name)) continue;
			if (!$display->canJoinToPrimary()) continue;
			$columns = $parent_display->getNeededColumnsToJoinFrom($child->foreign_key->name);
			if ($columns <> null) {
				$cv = array();
				foreach ($columns as $cname) {
					$v = null;
					if ($values->hasColumnValue($path->table->getName(), $cname))
						$v = $values->getColumnValue($path->table->getName(), $cname);
					$cv[$cname] = $v;
				}
				if (!$parent_display->checkJoinFrom($child->foreign_key->name, $cv)) continue;
			}
		}
		if ($child->isReverse())
			$from = $child->foreign_key->name;
		else
			$from = null;
		
		// get data from database if not new
		$key_value = getKeyValue($path->table, $values);
		if ($key_value <> null) {
			$q = SQLQuery::create()->noWarning();
			$q->selectSubModelForTable($path->table, $path->sub_model);
			$q->selectSubModelForTable($child->table, $child->sub_model);
			$q->select($path->table->getName());
			$q->whereKey($path->table, $key_value);
			$fields_matching = array();
			if ($child->isReverse()) {
				$fields_matching[$path->table->getPrimaryKey()->name] = $child->foreign_key->name;
			} else {
				$field_matching[$child->foreign_key->name] = $child->table->getPrimaryKey()->name;
			}
			$q->join($path->table->getName(), $child->table->getName(), $fields_matching);
			
			$data_aliases = array();
			foreach ($display->getDataDisplay($from) as $data) {
				$child->parent = new DataPath_Table($path->table, $path->sub_model);
				$data_aliases[$data->getDisplayName()] = $data->buildSQL($q, $child, array());
				$child->parent = $path;
			}
			$row = $q->executeSingleRow();
			foreach ($child->table->internalGetColumns($child->sub_model) as $col)
				if (isset($row[$col->name]))
					$values->addTableColumnValue($child->table->getName(), $col->name, $row[$col->name]);
			foreach ($display->getDataDisplay($from) as $data) {
				$values->addTableDataValue($child->table->getName(), $data->getDisplayName(), $data->getData($row, $data_aliases[$data->getDisplayName()]));
			}
		}
		
		$h = buildDataScreenFromDisplay($page, $child, $display, $from, $values, $prefilled_values, $structure_name);
		if ($h <> "") {
			$category = $display->getCategory();
			for ($i = 0; $i < count($sections); $i++) {
				if ($sections[$i][0] <> $category->getIcon()) continue;
				if ($sections[$i][1] <> $category->getName()) continue;
				$sections[$i][2] .= $h;
				break;
			}
			if ($i >= count($sections)) {
				array_push($sections, array($category->getIcon(), $category->getName(), $h));
			}
		}
		buildDataScreenChildren($page, $child, $values, $prefilled_values, $sections, $structure_name);
	}
}

function getKeyValue($table, $values) {
	$pk = $table->getPrimaryKey();
	if ($pk <> null) {
		if (!$values->hasColumnValue($table->getName(), $pk->name))
			return null;
		return $values->getColumnValue($table->getName(), $pk->name);
	}
	$key = $table->getKey();
	$ok = true;
	$key_value = array();
	foreach ($key as $cname) 
		if (!$values->hasColumnValue($table->getName(), $cname)) { 
			$ok = false;
			break; 
		} else {
			$key_value[$cname] = $values->getColumnValue($table->getName(), $cname);
		}
	if (!$ok) return null;
	return $key_value;
}

function buildDataScreenFromDisplay(&$page, $path, $display, $from, $values, $prefilled_values, $structure_name) {
	$html = "";
	$key_value = getKeyValue($path->table, $values);
	$is_new = $key_value == null || intval($key_value) < 0;
	$script = "";
	$html .= "<table>";
	$has_something = false;
	$fixed_columns = array_merge($values->getColumns($path->table->getName()));
	foreach ($display->getDataDisplay($from) as $data) {
		$can_edit = $data->isEditable();
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
		$page->add_javascript("/static/widgets/typed_field/typed_field.js");
		$page->require_javascript($tf[0].".js");
		// TODO check can really edit the columns
		if ($is_new) {
			$script .= "field = new ".$tf[0]."(".json_encode($value).",".json_encode($can_edit).",".$tf[1].");\n";
			$script .= "document.getElementById('$id').appendChild(field.getHTMLElement());\n";
			if ($structure_name <> null)
				$script .= "path.data.push({name:".json_encode($data->getDisplayName()).",field:field,getValue:function(){return this.field.getCurrentData();},validate:function(){ return this.field.error; }});\n";
		} else if ($can_edit) {
			$page->add_javascript("/static/data_model/DataDisplay.js");
			$page->add_javascript("/static/data_model/editable_field.js");
			$page->add_javascript("/static/data_model/editable_datadisplay.js");
			$script .= "field = new editable_datadisplay(document.getElementById('$id'), ".$data->javascriptDataDisplay($path->sub_model).", ".json_encode($from).", ".json_encode($key_value).", ".json_encode($value).");\n";
			$script .= "field.editable_field.field.getHTMLElement().style.width = '100%';\n";
		} else {
			$script .= "field = new ".$tf[0]."(".json_encode($value).",false,".$tf[1].");\n";
			$script .= "document.getElementById('$id').appendChild(field.getHTMLElement());\n";
		}
		$html .= "</td>";
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
		$html .= "}};\n";
		$html .= "$structure_name.push(path);\n";
	}
	$html .= $script;
	$html .= "</script>\n";
	return $html;
}
?>