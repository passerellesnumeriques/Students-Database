<?php
use datamodel\GenericDataScreen;

require_once("component/data_model/Model.inc");
require_once("component/data_model/DataScreen.inc");

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
		if (count($columns) == 0) return false;
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

function existingDataScreenFromKey(&$page, $root_table_name, $sub_model, $row_key, $sub_models = null) {
	$row = SQLQuery::create()->select($root_table_name)->selectSubModelForTable($root_table_name, $sub_model)->whereKey($root_table_name, $row_key)->executeSingleRow();
	return existingDataScreenFromRow($page, $root_table_name, $sub_model, $row, $sub_models);
}
function existingDataScreenFromRow(&$page, $root_table_name, $sub_model, $row, $sub_models = null) {
	$values = new DataValues();
	foreach ($row as $col=>$val)
		$values->addTableColumnValue($root_table_name, $col, $val);
	$prefilled_values = new DataValues();
	return dataScreen($page, $root_table_name, $sub_model, $sub_models, $values, $prefilled_values, null);
}

function newDataScreen(&$page, $root_table_name, $sub_model, $sub_models, $fixed_values, $prefilled_values, &$structure_name) {
	$structure_name = $page->generateID();
	$html = "";
	$html .= "<script type='text/javascript'>\n";
	$html .= "$structure_name = [];\n";
	$html .= "</script>\n";
	$html .= dataScreen($page, $root_table_name, $sub_model, $sub_models, $fixed_values, $prefilled_values, $structure_name);
	return $html;
}


function dataScreen(&$page, $root_table_name, $sub_model, $sub_models, $values, $prefilled_values, $structure_name) {
	require_once("component/data_model/DataPath.inc");
	$paths = DataPathBuilder::searchFrom($root_table_name, $sub_model, false, $sub_models);
	$root = DataPathBuilder::buildPathsTree($paths);
	
	$sections = array();
	
	// handle root
	$screens = DataModel::get()->getDataScreens();
	$screen = null;
	foreach ($screens as $s) {
		if (!($s instanceof \datamodel\SimpleDataScreen)) continue;
		$tables = $s->getTables();
		if (count($tables) == 1 && $tables[0] == $root->table->getName()) {
			$screen = $s;
			break;
		}
	}
	if ($screen == null) {
		$display = DataModel::get()->getTableDataDisplay($root_table_name);
		if ($display == null) return;
		$screen = new datamodel\GenericDataScreen($display);
	}
	$ob = ob_get_contents();
	ob_clean();
	$screen->generate($page, array($root), $values, $prefilled_values, $structure_name);
	$h = ob_get_contents();
	ob_clean();
	$screen->generateEndOfSection($page, array($root), $values, $prefilled_values, $structure_name);
	$end = ob_get_contents();
	ob_clean();
	echo $ob;
	if ($h <> "")
		array_push($sections, array(
				$screen->getIcon(),
				$screen->getName(),
				$h,
				$end
		));
	
	buildDataScreenChildren($page, $root, $values, $prefilled_values, $sections, $structure_name);
	
	$page->requireJavascript("section.js");
	$html = "";
	foreach ($sections as $s) {
		$id = $page->generateID();
		$html .= "<div id='$id'";
		if ($s[0] <> null) $html .= " icon='".$s[0]."'";
		$html .= " title=\"".toHTML($s[1])."\"";
		$html .= " collapsable='false' css='soft' style='display:inline-block;margin:10px;vertical-align:top'>";
		$html .= $s[2];
		$html .= $s[3];
		$html .= "</div>";
		$html .= "<script type='text/javascript'>sectionFromHTML('$id');</script>";
	}
	return $html;
}

function buildDataScreenChildren(&$page, $path, $values, $prefilled_values, &$sections, $structure_name) {
	$parent_display = DataModel::get()->getTableDataDisplay($path->table->getName());
	if ($parent_display == null) return;
	$screens = DataModel::get()->getDataScreens();
	$avail_screens = array();
	foreach ($screens as $s) {
		if (!($s instanceof \datamodel\SimpleDataScreen)) continue;
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
		else array_push($avail_screens, array($s, $children));
	}
	
	// create generic screens for remaining paths
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
		$screen = new datamodel\GenericDataScreen($display);
		array_push($avail_screens, array($screen, array($child)));
	}
	
	// generate screens
	foreach ($avail_screens as $s) {
		// get data from database if not new
		//echo "<br/>Values: ".toHTML(var_export($values,true))."<br/>";
		foreach ($s[1] as $child) {
			$key_value = getKeyValue($path->table, $values);
			//echo "<br/>Key for ".$child->table->getName()." (".$path->table->getName()."): ".var_export($key_value,true)."<br/>";
			if ($key_value <> null) {
				//if ($child->unique) {
					if ($child->isReverse()) {
						$values->addTableColumnValue($child->table->getName(), $child->foreign_key->name, $key_value);
					}
					$q = SQLQuery::create()
#DEV
					->noWarning()
#END
					;
					$q->selectSubModelForTable($path->table, $path->sub_model);
					$q->selectSubModelForTable($child->table, $child->sub_model);
					$q->select($path->table->getName());
					$q->whereKey($path->table, $key_value);
					$fields_matching = array();
					if ($child->isReverse()) {
						$fields_matching[$path->table->getPrimaryKey()->name] = $child->foreign_key->name;
					} else {
						$fields_matching[$child->foreign_key->name] = $child->table->getPrimaryKey()->name;
					}
					$q->join($path->table->getName(), $child->table->getName(), $fields_matching);
					$q->fieldsOfTable($child->table->getName());
						
					$display = DataModel::get()->getTableDataDisplay($child->table->getName());
					if ($display <> null) {
						if ($child->isReverse())
							$from = $child->foreign_key->name;
						else
							$from = null;
						$data_aliases = array();
						foreach ($display->getDataDisplay($from, $child->sub_model) as $data) {
							$child->parent = new DataPath_Table($path->table, $path->sub_model);
							$data_aliases[$data->getDisplayName()] = $data->buildSQL($q, $child);
							$child->parent = $path;
						}
					}
					$row = $q->executeSingleRow();
					foreach ($child->table->internalGetColumnsFor($child->sub_model) as $col) {
						if (isset($row[$col->name]))
							$values->addTableColumnValue($child->table->getName(), $col->name, $row[$col->name]);
					}
					if ($display <> null)
						foreach ($display->getDataDisplay($from, $child->sub_model) as $data) {
							$values->addTableDataValue($child->table->getName(), $data->getDisplayName(), $data->getData($row, $data_aliases[$data->getDisplayName()]));
							$cell = $data->getSingleCell();
							if ($cell <> null)
								$values->addTableColumnValue($cell["table"], $cell["column"], $data->getData($row, $data_aliases[$data->getDisplayName()]));
						}
				//} else {
				//	// not unique, just put the key
				//	if ($child->isReverse())
				//		$values->addTableColumnValue($child->table->getName(), $child->foreign_key->name, $values->getColumnValue($path->table->getName(), $path->table->getPrimaryKey()->name));
				//	else
				//		$values->addTableColumnValue($child->table->getName(), $child->table->getPrimaryKey()->name, $child->foreign_key->name);
				//}
			}
		}
		//echo "<br/>Values: ".toHTML(var_export($values,true))."<br/>";
		$ob = ob_get_contents();
		ob_clean();
		$s[0]->generate($page, $s[1], $values, $prefilled_values, $structure_name);
		$h = ob_get_contents();
		ob_clean();
		$s[0]->generateEndOfSection($page, $s[1], $values, $prefilled_values, $structure_name);
		$end = ob_get_contents();
		ob_clean();
		echo $ob;
		if ($h <> "") {
			$sec = null;
			for ($i = 0; $i < count($sections); $i++)
				if ($sections[$i][0] == $s[0]->getIcon() && $sections[$i][1] == $s[0]->getName()) { $sec = $i; break; }
			if ($sec === null) {
				array_push($sections, array(
					$s[0]->getIcon(),
					$s[0]->getName(),
					$h,
					$end
				));
			} else {
				$sections[$sec][2] .= $h;
				$sections[$sec][3] .= $end;
			}
		}
			
	}

	foreach ($avail_screens as $s)
		foreach ($s[1] as $child)
			buildDataScreenChildren($page, $child, $values, $prefilled_values, $sections, $structure_name);
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
?>