<?php 
class service_get_remove_confirmation_content extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Generate explaination about the consequences of a remove"; }
	public function inputDocumentation() { echo "<code>table, row_key, [sub_model]</code>: which row in which table is going to be removed"; }
	public function outputDocumentation() { echo "HTML code to be displayed as a confirmation message to the user"; }
	
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		try {
			$table = DataModel::get()->getTable($input["table"]);
			$row_key = $input["row_key"];
			$sub_model_instance = @$input["sub_model"];
			if ($table->getPrimaryKey() <> null) $row_key = intval($row_key);
			
			$description = $table->getRowDescriptionByKey($row_key);
			
			$to_remove = array();
			$to_update = array();
			$this->browseTablesForRemove($table, $sub_model_instance, array($row_key), $to_remove, $to_update);
			for ($i = 0; $i < count($to_remove[$table->getSQLNameFor($sub_model_instance)]["keys"]); $i++)
				if ($to_remove[$table->getSQLNameFor($sub_model_instance)]["keys"][$i] == $row_key) {
					array_splice($to_remove[$table->getSQLNameFor($sub_model_instance)]["keys"], $i, 1);
					break;
				}
			$str_remove = "";
			foreach ($to_remove as $table_sql_name=>$info) {
				$t = DataModel::get()->getTableFromSQLName($table_sql_name);
				if (isset($info["keys"])) {
					$keys = $info["keys"];
					$rows = SQLQuery::getRows($t, $keys);
					foreach ($rows as $row)
						$str_remove .= "<li>".$t->getRowDescription($row)."</li>";
				} else {
					$wheres = $info["where"];
					foreach ($wheres as $where) {
						$q = SQLQuery::create()->select($t->getName())->selectSubModelForTable($t, DataModel::get()->getSubModelInstanceFromSQLName($table_sql_name));
						foreach ($where as $cname=>$cval) $q->whereValue($t->getName(), $cname, $cval);
						$rows = $q->execute();
						foreach ($rows as $row)
							$str_remove .= "<li>".$t->getRowDescription($row)."</li>";
					}
				}
			}
			$str_update = "";
			foreach ($to_update as $a) {
				$table_sql_name = $a[0];
				$col_name = $a[1];
				$keys = $a[2];
				$t = DataModel::get()->getTableFromSQLName($table_sql_name);
				$sm = DataModel::get()->getSubModelInstanceFromSQLName($table_sql_name);
			
				$rows_to_update = SQLQuery::create()->selectSubModelForTable($t, $sm)->select($t->getName())->whereIn($t->getName(), $col_name, $keys)->execute();
			
				$ft = DataModel::get()->getTable($t->getColumn($col_name, $sm)->foreign_table);
				foreach ($rows_to_update as $row) {
					$str_update .= "<li>";
					$str_update .= $t->getRowDescription($row);
					$str_update .= "<br/>will be unlinked from ";
					$str_update .= $ft->getRowDescriptionByKey($row[$col_name]);
					$str_update .= "</li>";
				}
			}
				
			echo "<b>Are you sure you want to remove ".$description." ?</b><br/>";
			if ($str_remove <> "" || $str_update <> "") {
				echo "<br/>It is used by some other elements in the database.<br/>";
				if ($str_remove <> "") {
					echo "<b>The following elements will be also removed</b>:<ul>";
					echo $str_remove;
					echo "</ul>";
				}
				if ($str_update <> "") {
					echo "<b>The following elements will be modified</b>:<ul>";
					echo $str_update;
					echo "</ul>";
				}
			} 
		} catch (Exception $e) {
			PNApplication::error("You cannot remove ".$description.": ".$e->getMessage());
		}
		PNApplication::printErrors();
	}
	
	private function browseTablesForRemove(&$table, $sub_model_instance, $keys, &$to_remove, &$to_update) {
		// remove keys that are already marked as to_remove
		if (isset($to_remove[$table->getSQLNameFor($sub_model_instance)])) {
			for ($i = 0; $i < count($keys); $i++) {
				if (in_array($keys[$i], $to_remove[$table->getSQLNameFor($sub_model_instance)]["keys"])) {
					array_splice($keys, $i, 1);
					$i--;
				}
			}
		}
	
		if (count($keys) == 0) return; // nothing to do
			
		// check access rights
		if (!$table->mayRemove())
			throw new Exception("Access denied: remove in table '".$table->getName()."'");
		if (!$table->canRemoveAny()) {
			// we need to apply filters
			$q = SQLQuery::create();
			$q->bypassSecurity();
			$table_alias = $q->generateTableAlias();
			$q->select(array($table->getName()=>$table_alias));
			if ($sub_model_instance <> null)
				$q->selectSubModel($table->getParentTable(), $sub_model_instance);
			if ($table->getPrimaryKey() <> null)
				$q->whereIn($table_alias, $table->getPrimaryKey()->name, $keys);
			else {
				$pk = $table->getKey();
				$where = "";
				foreach ($keys as $key) {
					if (strlen($where) > 0) $where .= " OR ";
					$where .= "(";
					$first = true;
					foreach ($pk as $pk_name) {
						if ($first) $first = false; else $where .= " AND ";
						$where .= "`".$table_alias."`.`".$pk_name."`='".SQLQuery::escape($key[$pk_name])."'";
					}
					$where .= ")";
				}
				$q->where($where);
			}
			$table->prepareSelectToDetermineRemoveAccess($q, $table_alias, $locks);
			$rows = $q->execute();
			if (count($rows) <> count($keys)) throw new Exception("Invalid keys to remove in table '".$table->getName()."': ".count($keys)." keys given, ".count($rows)." rows found.");
			$result = $table->filterRemoveAccess($rows);
			if (count($rows) <> count($result)) throw new Exception("Access denied: ".(count($rows)-count($result))." rows cannot be removed among the ".count($rows)." rows to remove in table '".$table->getName()."'");
			$rows = $result;
		}
	
		// we can remove those keys
		if (!isset($to_remove[$table->getSQLNameFor($sub_model_instance)])) {
			$key_name = $table->getPrimaryKey() <> null ? $table->getPrimaryKey()->name : $table->getKey();
			$to_remove[$table->getSQLNameFor($sub_model_instance)] = array("keys"=>array(),"key_name"=>$key_name);
		}
		foreach ($keys as $key)
			array_push($to_remove[$table->getSQLNameFor($sub_model_instance)]["keys"], $key);
	
		// search for foreign keys in this table, which may imply other remove
		$new_remove = array();
		foreach ($table->internalGetColumnsFor($sub_model_instance) as $col) {
			if (!($col instanceof datamodel\ForeignKey)) continue;
			if (!$col->remove_primary_when_foreign_removed) continue;
			// we have one
			$sub_model = null;
			if ($table->getModel() instanceof SubDataModel) {
				$t = DataModel::get()->internalGetTable($col->foreign_table);
				if ($t->getModel() instanceof SubDataModel && $t->getModel()->getParentTable() == $table->getModel()->getParentTable())
					$sub_model = $sub_model_instance;
			}
			// search corresponding keys
			$q = SQLQuery::create()->bypassSecurity();
			$table_alias = $q->generateTableAlias();
			$q->select(array($table->getName()=>$table_alias));
			if ($sub_model_instance <> null) $q->selectSubModel($table->getModel()->getParentTable(), $sub_model_instance);
			if ($table->getPrimaryKey() <> null)
				$q->whereIn($table_alias, $table->getPrimaryKey()->name, $keys);
			else {
				$pk = $table->getKey();
				$where = "";
				foreach ($keys as $key) {
					if (strlen($where) > 0) $where .= " OR ";
					$where .= "(";
					$first = true;
					foreach ($pk as $pk_name) {
						if ($first) $first = false; else $where .= " AND ";
						$where .= "`".$table_alias."`.`".$pk_name."`='".SQLQuery::escape($key[$pk_name])."'";
					}
					$where .= ")";
				}
				$q->where($where);
			}
			$q->field($table_alias, $col->name);
			$rows = $q->executeSingleField();
				
			if (!isset($new_remove[$col->foreign_table]))
				$new_remove[$col->foreign_table] = array();
			if (!isset($new_remove[$col->foreign_table][$sub_model]))
				$new_remove[$col->foreign_table][$sub_model] = array();
			array_push($new_remove[$col->foreign_table][$sub_model], $rows);
		}
	
		// search for foreign keys in other tables which are linked to the keys we want to remove
		if ($table->getModel() instanceof SubDataModel) {
			// we are in a sub model: everything must be inside this sub model
			foreach ($table->getModel()->internalGetTables() as $t) {
				$sm = $t->getModel() instanceof SubDataModel ? $sub_model_instance : null;
				$this->checkForeignKeysForRemove($table, $sub_model_instance, $keys, $t, $sm, $to_remove, $to_update);
			}
		} else {
			// we are on the root model, we may have link everywhere
			foreach (DataModel::get()->internalGetTables() as $t) {
				if ($t->getModel() instanceof SubDataModel) {
					foreach ($t->getModel()->getExistingInstances() as $smi) {
						$sm = $smi;
						$this->checkForeignKeysForRemove($table, $sub_model_instance, $keys, $t, $sm, $to_remove, $to_update);
					}
				} else {
					$this->checkForeignKeysForRemove($table, $sub_model_instance, $keys, $t, null, $to_remove, $to_update);
				}
			}
		}
	}

	private function checkForeignKeysForRemove(&$table, $sub_model_instance, $keys, &$t, $sm, &$to_remove, &$to_update) {
		$cols = $t->internalGetColumnsFor($sm);
		foreach ($cols as $col) {
			if (!($col instanceof \datamodel\ForeignKey)) continue;
			if ($col->foreign_table <> $table->getName()) continue;
			if ($col->remove_foreign_when_primary_removed) {
				$primary = $t->getPrimaryKey();
				if ($primary <> null) {
					// search keys for rows matching
					$q = SQLQuery::create();
					$table_alias = $q->generateTableAlias();
					$q->select(array($t->getName()=>$table_alias));
					if ($sm <> null)
						$q->selectSubModel($t->getModel()->getParentTable(), $sm);
					$q->whereIn($table_alias, $col->name, $keys);
					$q->field($table_alias, $primary->name);
					$rows = $q->executeSingleField();
					$this->browseTablesForRemove($t, $sm, $rows, $to_remove, $to_update);
				} else {
					// no primary key, this must be done with a where
					if (!$t->canRemoveAny()) {
						$q = SQLQuery::create();
						$table_alias = $q->generateTableAlias();
						$q->select(array($t->getName()=>$table_alias));
						if ($sm <> null)
							$q->selectSubModel($t->getModel()->getParentTable(), $sm);
						$q->whereIn($table_alias, $col->name, $keys);
						$t->prepareSelectToDetermineRemoveAccess($q, $table_alias, $locks);
						$rows = $q->execute();
						$filtered = $t->filterRemoveAccess($rows);
						if (count($filtered) <> count($rows))
							throw new Exception("Access denied: remove from table '".$t->getName()."' due to link to removed data from table '".$table->getName()."': ".(count($rows)-count($filtered))." row(s) cannot be removed");
					}
					if (!isset($to_remove[$t->getSQLNameFor($sm)]))
						$to_remove[$t->getSQLNameFor($sm)] = array("where"=>array());
					foreach ($keys as $key)
						array_push($to_remove[$t->getSQLNameFor($sm)]["where"], array($col->name=>$key));
				}
			} else {
				if (!$t->mayModify())
					throw new Exception("Access denied: cannot modify table '".$t->getName()."': necessary to remove link to table '".$table->getName()."'");

				if (!$t->canModifyEverything()) {
					// select matching rows
					$q = SQLQuery::create();
					$table_alias = $q->generateTableAlias();
					$q->select(array($t->getName()=>$table_alias));
					if ($t->getModel() instanceof SubDataModel)
						$q->selectSubModel($t->getModel()->getParentTable(), $sub_model_instance);
					$t->prepareSelectToDetermineWriteAccess($q, $table_alias);
					$prepared_rows = $q->execute();
					if (count($prepared_rows) == 0) continue; // nothing to do there
					$allowed_rows = $t->filterWriteAccess($prepared_rows);
					if (count($allowed) <> count($prepared_rows))
						throw new Exception("Access denied: some rows cannot be modified in table '".$t->getName()."' but are linked to rows which need to be removed in table '".$table->getName()."'");
				}
	
				// we can update
				array_push($to_update, array($t->getSQLNameFor($sm),$col->name,$keys));
			}
		}
	}
	
}
?>