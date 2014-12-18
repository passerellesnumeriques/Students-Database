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
			$sub_models_to_remove = array();
			$locks = array();
			SQLQuery::create()->browseTablesForRemove($table, $sub_model_instance, array($row_key), null, $locks, $to_remove, $to_update, $sub_models_to_remove);
			//$this->browseTablesForRemove($table, $sub_model_instance, array($row_key), $to_remove, $to_update);
			for ($i = 0; $i < count($to_remove[$table->getSQLNameFor($sub_model_instance)]["keys"]); $i++)
				if ($to_remove[$table->getSQLNameFor($sub_model_instance)]["keys"][$i] == $row_key) {
					array_splice($to_remove[$table->getSQLNameFor($sub_model_instance)]["keys"], $i, 1);
					break;
				}
			require_once 'component/data_model/DataBaseLock.inc';
			DataBaseLock::unlockMultiple($locks);
			$str_remove = "";
			foreach ($to_remove as $table_sql_name=>$info) {
				$t = DataModel::get()->getTableFromSQLName($table_sql_name);
				if (isset($info["keys"])) {
					$keys = $info["keys"];
					$rows = SQLQuery::getRows($t, $keys);
					foreach ($rows as $row) {
						$descr = $t->getRowDescription($row);
						if ($descr <> "")
							$str_remove .= "<li>".$descr."</li>";
					}
				} else {
					$wheres = $info["where"];
					foreach ($wheres as $where) {
						$q = SQLQuery::create()->select($t->getName())->selectSubModelForTable($t, DataModel::get()->getSubModelInstanceFromSQLName($table_sql_name));
						foreach ($where as $cname=>$cval) $q->whereValue($t->getName(), $cname, $cval);
						$rows = $q->execute();
						foreach ($rows as $row) {
							$descr = $t->getRowDescription($row);
							if ($descr <> "")
								$str_remove .= "<li>".$descr."</li>";
						}
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
					$descr = $t->getRowDescription($row);
					if ($descr <> null) {
						$str_update .= "<li>";
						$str_update .= $descr;
						$str_update .= "<br/>will be unlinked from ";
						$str_update .= $ft->getRowDescriptionByKey($row[$col_name]);
						$str_update .= "</li>";
					}
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
			PNApplication::error("You cannot remove ".$description, $e);
		}
		PNApplication::printErrors();
	}
	
}
?>