<?php 
$sms_path = realpath(dirname(__FILE__)."/../sms");
set_include_path($sms_path);
chdir($sms_path);
include('install_config.inc');
require_once 'DataBaseSystem_MySQL.inc';
require_once("SQLQuery.inc");
require_once 'component/PNApplication.inc';
global $local_domain, $domain;
$domain = $local_domain;
if (PNApplication::$instance == null) {
	PNApplication::$instance = new PNApplication();
	PNApplication::$instance->local_domain = $local_domain;
	PNApplication::$instance->current_domain = $local_domain;
	PNApplication::$instance->init();
}
require_once("component/data_model/Model.inc");

global $db;
$db = SQLQuery::getDataBaseAccessWithoutSecurity();

global $id_counter, $inserted, $removed;
$id_counter = 1;
$inserted = array();
$removed = array();
/**
 * @param datamodel\Table $table
 * @param string[] $done
 */
function getTableDiff(&$table, &$done) {
	global $db, $domain, $id_counter, $inserted, $removed;
	$table_name = $table->getName();
	if ($table_name == "DataLocks") return;
	if ($table_name == "Users") return;
	array_push($done, $table_name);
	
	$cols = $table->internalGetColumns();
	
	foreach ($cols as $col) {
		if ($col instanceof datamodel\ForeignKey) {
			if (!in_array($col->foreign_table, $done)) {
				// we have a foreign key to a table we didn't go through => let go through
				getTableDiff(DataModel::get()->internalGetTable($col->foreign_table), $done);
			}
		}
	}
	set_time_limit(30);

	// get new entities here
	$pk = $table->getPrimaryKey();
	if ($pk <> null) {
		$pk_name = "`".$pk->name."`";
	} else {
		$key = $table->getKey();
		$pk_name = "";
		for ($i = 0; $i < count($key); $i++) {
			if ($i > 0) $pk_name .= ",";
			$pk_name .= "`".$key[$i]."`";
		}
		$pk_name .= "";
	}
	$res = $db->execute("SELECT * FROM `selectiontravel_$domain`.`$table_name` WHERE ($pk_name) NOT IN (SELECT $pk_name FROM `selectiontravel_init`.`$table_name`)");
	while (($row = $db->nextRow($res)) <> null) {
		set_time_limit(30);
		if ($pk instanceof datamodel\PrimaryKey) {
			$new_id = $row[$pk->name];
			$mapping = $id_counter++;
			if (!isset($inserted[$table_name])) $inserted[$table_name] = array();
			$inserted[$table_name][$new_id] = $mapping;
			echo "#ID$mapping=";
		}
		echo "INSERT INTO `$table_name` (";
		$first = true;
		foreach ($table->internalGetColumns() as $col) {
			if ($col instanceof datamodel\PrimaryKey) continue;
			if ($first) $first = false; else echo ",";
			echo "`".$col->name."`";
		}
		echo ") VALUE (";
		$first = true;
		foreach ($table->internalGetColumns() as $col) {
			if ($col instanceof datamodel\PrimaryKey) continue;
			if ($first) $first = false; else echo ",";
			$value = $row[$col->name];
			if ($col instanceof datamodel\ForeignKey) {
				if (isset($inserted[$col->foreign_table][$value]))
					$value = "##ID".$inserted[$col->foreign_table][$value]."##";
			}
			if ($value === null) echo "NULL";
			else if ($col instanceof datamodel\ColumnInteger) echo $value;
			else echo "'".$db->escapeString($value)."'";
		}
		echo ")\n";
	}
	
	// get removed entities
	set_time_limit(30);
	$sql = "SELECT $pk_name FROM `selectiontravel_init`.`$table_name` WHERE ";
	// do not include the ones which will be automatically removed due to links
	foreach ($cols as $col)
		if ($col instanceof datamodel\ForeignKey) {
			if ($col->remove_foreign_when_primary_removed) {
				if (isset($removed[$col->foreign_table])) {
					$sql .= "`".$col->name."` NOT IN (";
					$first = true;
					foreach ($removed[$col->foreign_table] as $id) {
						if ($first) $first = false; else $sql .= ",";
						$sql .= $id;
					}
					$sql .= ") AND ";
				}
			}
		}
	$sql .= "($pk_name) NOT IN (SELECT $pk_name FROM `selectiontravel_$domain`.`$table_name`)";
	$res = $db->execute($sql);
	while (($row = $db->nextRow($res)) <> null) {
		set_time_limit(30);
		echo "DELETE FROM `$table_name` WHERE ";
		if ($pk <> null) {
			echo "$pk_name=".$row[$pk->name];
			if (!isset($removed[$table_name])) $removed[$table_name] = array();
			array_push($removed[$table_name], $row[$pk->name]);
		} else {
			$first = true;
			foreach ($key as $colname) {
				if ($first) $first = false; else echo " AND ";
				echo "`$colname`";
				if ($row[$colname] === null) echo " IS NULL";
				else {
					echo "=";
					if ($table->internalGetColumn($colname) instanceof datamodel\ColumnInteger) echo $row[$colname];
					else echo "'".$db->escapeString($row[$colname])."'";
				}
			}
		}
		echo "\n";
	}
	
	// get modified rows
	$sql = "SELECT ";
	if ($pk <> null) $sql .= "`t1`.`".$pk->name."` AS `pk`";
	else {
		for ($i = 0; $i < count($key); $i++) {
			if ($i > 0) $sql .= ",";
			$sql .= "`t1`.`".$key[$i]."` AS `k$i`";
		}
	}
	$modifiable_columns = array();
	for ($i = 0; $i < count($cols); $i++) {
		$col = $cols[$i];
		if ($col == $pk) continue;
		if ($pk == null && in_array($col->name, $key)) continue;
		$sql .= ",`t1`.`".$col->name."` AS `c$i`";
		$sql .= ",(`t1`.`".$col->name."` <> `t2`.`".$col->name."`) AS `m$i`";
		array_push($modifiable_columns, $i);
	}
	if (count($modifiable_columns) > 0) {
		$sql .= " FROM `selectiontravel_$domain`.`$table_name` AS `t1`";
		$sql .= " JOIN `selectiontravel_init`.`$table_name` AS `t2` ON ";
		if ($pk <> null) $sql .= "`t1`.`".$pk->name."`=`t2`.`".$pk->name."`";
		else for ($i = 0; $i < count($key); $i++) {
			if ($i > 0) $sql .= " AND ";
			$sql .= "`t1`.`".$key[$i]."`=`t2`.`".$key[$i]."`";
		}
		$sql .= " HAVING ";
		$first = true;
		foreach ($modifiable_columns as $i) {
			if ($first) $first = false; else $sql .= " OR ";
			$sql .= "`m$i` > 0";
		}
		set_time_limit(30);
		$res = $db->execute($sql);
		while (($row = $db->nextRow($res)) <> null) {
			set_time_limit(30);
			echo "UPDATE `$table_name` SET ";
			$first = true;
			foreach ($modifiable_columns as $i) {
				if ($row["m$i"] == 0) continue;
				if ($first) $first = false; else echo ",";
				echo "`".$cols[$i]->name."`=";
				if ($row["c$i"] === null) echo "NULL";
				else if ($cols[$i] instanceof datamodel\ColumnInteger) echo $row["c$i"];
				else echo "'".$db->escapeString($row["c$i"])."'";
			}
			echo " WHERE ";
			if ($pk <> null) echo "`".$pk->name."`=".$row["pk"];
			else for ($i = 0; $i < count($key); $i++) {
				if ($i > 0) echo " AND ";
				echo "`".$key[$i]."`";
				if ($row["k$i"] === null) echo " IS NULL";
				else {
					echo "=";
					if ($table->internalGetColumn($key[$i]) instanceof datamodel\ColumnInteger) echo $row["k$i"];
					else echo "'".$db->escapeString($row["k$i"])."'";
				} 
			}
			echo "\n";
		}
	}
}
$done = array();
foreach (DataModel::get()->internalGetTables(false) as $table)
	getTableDiff($table, $done);


/*
SELECT
 t1.id,
 (t1.first_name <> t2.first_name) AS fn_changed,
 (t1.last_name <> t2.last_name) AS ln_changed
FROM 
`selectiontravel_PNC`.`people` AS t1
JOIN `selectiontravel_init`.`people` AS t2 ON t1.id=t2.id
HAVING fn_changed > 0 OR ln_changed > 0
 */
?>