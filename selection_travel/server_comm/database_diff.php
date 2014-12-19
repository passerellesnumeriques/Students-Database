<?php 
function progress($text, $pos = null, $total = null) {
	$f = fopen(dirname(__FILE__)."/database_diff_progress","w");
	fwrite($f, ($pos !== null ? "%$pos,$total%" : "").$text);
	fclose($f);
}
progress("Initializing synchronization");

function removeDirectory($path) {
	$dir = opendir($path);
	while (($filename = readdir($dir)) <> null) {
		if ($filename == ".") continue;
		if ($filename == "..") continue;
		if (is_dir($path."/".$filename))
			removeDirectory($path."/".$filename);
		else
			unlink($path."/".$filename);
	}
	closedir($dir);
	if (!@rmdir($path))
		rmdir($path);
}
if (file_exists(dirname(__FILE__)."/data")) removeDirectory(dirname(__FILE__)."/data");
mkdir(dirname(__FILE__)."/data");

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

global $id_counter, $inserted, $removed, $f, $total_tables, $storage_added, $storage_updated;
$id_counter = 1;
$inserted = array();
$removed = array();
$f = fopen(dirname(__FILE__)."/data/database.sql_diff","w");
$total_tables = 0;
$storage_added = array();
$storage_updated = array();
/**
 * @param datamodel\Table $table
 * @param string[] $done
 */
function getTableDiff(&$table, $sub_model, &$done) {
	global $db, $domain, $id_counter, $inserted, $removed, $f, $total_tables, $storage_added, $storage_updated;
	$table_name = $table->getSQLNameFor($sub_model);
	if ($table_name == "DataLocks") return;
	if ($table_name == "Users") return;
	if (in_array($table_name, $done)) return;
	array_push($done, $table_name);
	
	$cols = $table->internalGetColumnsFor($sub_model);
	
	foreach ($cols as $col) {
		if ($col instanceof datamodel\ForeignKey) {
			if (!in_array($col->foreign_table, $done)) {
				// we have a foreign key to a table we didn't go through => let go through
				getTableDiff(DataModel::get()->internalGetTable($col->foreign_table), $sub_model, $done);
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
			fwrite($f,"#ID$mapping=");
			if ($table_name == "Storage") array_push($storage_added, $new_id);
		}
		fwrite($f,"INSERT INTO `$table_name` (");
		$first = true;
		foreach ($cols as $col) {
			if ($col instanceof datamodel\PrimaryKey) continue;
			if ($first) $first = false; else fwrite($f,",");
			fwrite($f,"`".$col->name."`");
		}
		fwrite($f,") VALUE (");
		$first = true;
		foreach ($cols as $col) {
			if ($col instanceof datamodel\PrimaryKey) continue;
			if ($first) $first = false; else fwrite($f,",");
			$value = $row[$col->name];
			if ($col instanceof datamodel\ForeignKey) {
				if (isset($inserted[$col->foreign_table][$value]))
					$value = "##ID".$inserted[$col->foreign_table][$value]."##";
			}
			if ($value === null) fwrite($f,"NULL");
			else if ($col instanceof datamodel\ColumnInteger) fwrite($f,$value);
			else fwrite($f,"'".$db->escapeString($value)."'");
		}
		fwrite($f,")\n");
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
		fwrite($f,"DELETE FROM `$table_name` WHERE ");
		if ($pk <> null) {
			fwrite($f,"$pk_name=".$row[$pk->name]);
			if (!isset($removed[$table_name])) $removed[$table_name] = array();
			array_push($removed[$table_name], $row[$pk->name]);
		} else {
			$first = true;
			foreach ($key as $colname) {
				if ($first) $first = false; else fwrite($f," AND ");
				fwrite($f,"`$colname`");
				if ($row[$colname] === null) fwrite($f," IS NULL");
				else {
					fwrite($f,"=");
					if ($table->internalGetColumnFor($colname, $sub_model) instanceof datamodel\ColumnInteger) fwrite($f,$row[$colname]);
					else fwrite($f,"'".$db->escapeString($row[$colname])."'");
				}
			}
		}
		fwrite($f,"\n");
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
			if ($table_name == "Storage") array_push($storage_updated, $row["pk"]);
			fwrite($f,"UPDATE `$table_name` SET ");
			$first = true;
			foreach ($modifiable_columns as $i) {
				if ($row["m$i"] == 0) continue;
				if ($first) $first = false; else fwrite($f,",");
				fwrite($f,"`".$cols[$i]->name."`=");
				if ($row["c$i"] === null) fwrite($f,"NULL");
				else if ($cols[$i] instanceof datamodel\ColumnInteger) fwrite($f,$row["c$i"]);
				else fwrite($f,"'".$db->escapeString($row["c$i"])."'");
			}
			fwrite($f," WHERE ");
			if ($pk <> null) fwrite($f,"`".$pk->name."`=".$row["pk"]);
			else for ($i = 0; $i < count($key); $i++) {
				if ($i > 0) fwrite($f," AND ");
				fwrite($f,"`".$key[$i]."`");
				if ($row["k$i"] === null) fwrite($f," IS NULL");
				else {
					fwrite($f,"=");
					if ($table->internalGetColumnFor($key[$i], $sub_model) instanceof datamodel\ColumnInteger) fwrite($f,$row["k$i"]);
					else fwrite($f,"'".$db->escapeString($row["k$i"])."'");
				} 
			}
			fwrite($f,"\n");
		}
	}
	progress("Analyzing modifications you made on your database",count($done),$total_tables);
}
$done = array();
$root_tables = DataModel::get()->internalGetTables(false);
$sm = DataModel::get()->getSubModel("SelectionCampaign");
$campaign_id = SQLQuery::create()->select("SelectionCampaign")->field("id")->executeSingleValue();
$sm_tables = $sm->internalGetTables();
$total_tables = count($root_tables)+count($sm_tables);
progress("Analyzing modifications you made on your database",0,$total_tables);
foreach ($root_tables as $table)
	getTableDiff($table, null, $done);
foreach ($sm_tables as $table)
	getTableDiff($table, $campaign_id, $done);

fclose($f);

// send to server

global $campaign_id, $app_version, $synch_uid, $username, $synch_key, $server;
$synch_uid = file_get_contents(dirname(__FILE__)."/synch.uid");
$username = file_get_contents($sms_path."/conf/selection_travel_username");
$synch_key = $_POST["synch_key"];
$server = $_POST["server"];
$app_version = file_get_contents($sms_path."/version");

function sendFile($path, $type, $info) {
	global $campaign_id, $app_version, $synch_uid, $username, $synch_key, $server;
	$c = curl_init("http://$server/dynamic/selection/service/travel/synch_file?type=$type&campaign=".$campaign_id.($info <> null ? $info : ""));
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_INFILESIZE, filesize($path));
	curl_setopt($c, CURLOPT_POSTFIELDS, array("username"=>$username,"uid"=>$synch_uid,"key"=>$synch_key,"storage_upload"=>"@".realpath($path),"filesize"=>filesize($path)));
	curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$app_version","User-Agent: Students Management Software - Travel Version Synchronization"));
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($c, CURLOPT_TIMEOUT, 1000);
	set_time_limit(1100);
	$result = curl_exec($c);
	if ($result === false) die("Error while sending data to the server: ".curl_error($c));
	$res = json_decode($result, true);
	if ($res == null) die("Unexpected data from the server: ".$result);
	if (isset($res["errors"]) && count($res["errors"]) > 0) {
		echo "Error while sending data to the server:<ul>";
		foreach ($res["errors"] as $err) echo "<li>$err</li>";
		echo "</ul>";
		die();
	}
	curl_close($c);
}

progress("Sending database modifications to the server");
sendFile(dirname(__FILE__)."/data/database.sql_diff", "database_diff", null);

$done = 0;
$total = count($storage_added)+count($storage_updated);
progress("Sending new files (pictures, documents...) to the server", $done, $total);
foreach ($storage_added as $id) {
	$path = PNApplication::$instance->storage->get_data_path($id);
	sendFile($path, "new_storage", "&file_id=".$inserted["Storage"][$id]);
	progress("Sending new files (pictures, documents...) to the server", ++$done, $total);
}
foreach ($storage_updated as $id) {
	$path = PNApplication::$instance->storage->get_data_path($id);
	sendFile($path, "updated_storage", "&file_id=".$id);
	progress("Sending new files (pictures, documents...) to the server", ++$done, $total);
}

// send signal to the server we are done
progress("Modifications sent to the server. The server is updating its database.");
$c = curl_init("http://$server/dynamic/selection/service/travel/synch_file?type=done&campaign=".$campaign_id);
curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($c, CURLOPT_POSTFIELDS, array("username"=>$username,"uid"=>$synch_uid,"key"=>$synch_key));
curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$app_version","User-Agent: Students Management Software - Travel Version Synchronization"));
curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($c, CURLOPT_TIMEOUT, 1000);
set_time_limit(1100);
$result = curl_exec($c);
if ($result === false) die("Error during server synchronization: ".curl_error($c));
$res = json_decode($result, true);
if ($res == null) die("Unexpected data from the server: ".$result);
if (isset($res["errors"]) && count($res["errors"]) > 0) {
	echo "Error during server synchronization:<ul>";
	foreach ($res["errors"] as $err) echo "<li>$err</li>";
	echo "</ul>";
	die();
}
curl_close($c);

// TODO deactivate
echo "OK";
?>