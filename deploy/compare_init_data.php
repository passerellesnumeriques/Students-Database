<?php
global $has_errors;
$has_errors = false;
set_error_handler(function($severity, $message, $filename, $lineno) {
	if (error_reporting() == 0) return true;
	$has_errors = true;
	return true;
});

$previous_path = realpath($_POST["path"])."/latest/init_data";
$new_path = realpath($_POST["path"])."/init_data";

$previous_files = array();
$new_files = array();

function searchFiles($path, $rel, &$files) {
	$dir = opendir($path);
	while (($file = readdir($dir)) <> null) {
		if ($file == "." || $file == "..") continue;
		if (is_dir($path."/".$file))
			searchFiles($path."/".$file, $rel.$file."/", $files);
		else 
			array_push($files, $rel.$file);
	}
	closedir($dir);
}

set_time_limit(60);
searchFiles($previous_path, "/", $previous_files);
searchFiles($new_path, "/", $new_files);

$change = array();
$remove = array();
for ($i = 0; $i < count($previous_files); $i++) {
	set_time_limit(60);
	$file = $previous_files[$i];
	$found = false;
	for ($j = 0; $j < count($new_files); ++$j)
		if ($new_files[$j] == $file) { 
			$found = true;
			array_splice($new_files,$j,1);
			break;
		}
	if (!$found) { array_push($remove, $file); continue; }
	$s1 = file_get_contents($previous_path.$file);
	$s2 = file_get_contents($new_path.$file);
	if ($s1 <> $s2) array_push($change, $file);
}
$add = $new_files;

function remove_directory($path) {
	$dir = opendir($path);
	while (($filename = readdir($dir)) <> null) {
		if ($filename == ".") continue;
		if ($filename == "..") continue;
		if (is_dir($path."/".$filename))
			remove_directory($path."/".$filename);
		else
			unlink($path."/".$filename);
	}
	closedir($dir);
	rmdir($path);
}

global $migration;
$migration = array();
function checkMigrationScripts($component) {
	global $migration;
	if (!file_exists(realpath($_POST["path"])."/www/component/$component/updates")) return;
	$path = realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"];
	if (file_exists($path)) {
		if (file_exists(realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"]."/before_datamodel.php") || file_exists(realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"]."/after_datamodel.php")) {
			$migration[$component] = array("","");
			if (file_exists(realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"]."/before_datamodel.php"))
				$migration[$component][0] = file_get_contents(realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"]."/before_datamodel.php");
			if (file_exists(realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"]."/after_datamodel.php"))
				$migration[$component][1] = file_get_contents(realpath($_POST["path"])."/www/component/$component/updates/".$_POST["version"]."/after_datamodel.php");
		}
	}
	remove_directory(realpath($_POST["path"])."/www/component/$component/updates");
}
$dir = opendir(realpath($_POST["path"])."/www/component");
while (($file = readdir($dir)) <> null) {
	if ($file == "." || $file == "..") continue;
	if (!is_dir(realpath($_POST["path"])."/www/component/$file")) continue;
	checkMigrationScripts($file);
}
closedir($dir);

$before = "";
$after = "";
foreach ($migration as $comp=>$content) {
	$before .= $content[0];
	$after .= $content[1];
}
if ($before <> "") {
	$f = fopen(realpath($_POST["path"])."/migration/before_datamodel.php","w");
	fwrite($f,$before);
	fclose($f);
}
if ($after <> "") {
	$f = fopen(realpath($_POST["path"])."/migration/after_datamodel.php","w");
	fwrite($f,$after);
	fclose($f);
}

if ($has_errors) die();

include("header.inc");
?>
<div style='flex:none;background-color:white;padding:10px'>
<form name='deploy' method="POST" action="create_zip.php" style='display:none'>
<input type='hidden' name='version' value='<?php echo $_POST["version"];?>'/>
<input type='hidden' name='path' value='<?php echo $_POST["path"];?>'/>
<input type='hidden' name='latest' value='<?php echo $_POST["latest"];?>'/>
<input type='hidden' name='channel' value='<?php echo $_POST["channel"];?>'/>
</form>
<?php 
if (count($migration) > 0) {
	echo "We found migration scripts in the following components:<ul>";
	foreach ($migration as $comp=>$content) {
		echo "<li>".$comp."</li>";
	}
	echo "</ul>";
}
if (count($change) == 0 && count($add) == 0 && count($remove) == 0) {
	// no change
	?>
	No change in the initial data.<br/>
	Generating zip files...
	</div>
	<script type='text/javascript'>
	document.forms['deploy'].submit();
	</script>
<?php 
	include("footer.inc");
	return;
}
$has_sql = false;
if (count($change) > 0) {
	echo "The following files changed:<ul>";
	foreach ($change as $file) {
		if (substr($file,strlen($file)-4) == ".sql") $has_sql = true;
		echo "<li>".htmlentities($file)."</li>";
	}
	echo "</ul>";
}
if (count($add) > 0) {
	echo "The following files have been added:<ul>";
	foreach ($add as $file) {
		if (substr($file,strlen($file)-4) == ".sql") $has_sql = true;
		echo "<li>".htmlentities($file)."</li>";
	}
	echo "</ul>";
}
if (count($remove) > 0) {
	echo "The following files have been removed:<ul>";
	foreach ($remove as $file) {
		if (substr($file,strlen($file)-4) == ".sql") $has_sql = true;
		echo "<li>".htmlentities($file)."</li>";
	}
	echo "</ul>";
}

function analyzeSQL($file) {
	set_time_limit(300);
	$sql = array();
	$f = fopen($file, "r");
	$table_name = null;
	$table_columns = null;
	while (($line = fgets($f, 20000))) {
		if (substr($line,0,13) == "INSERT INTO `") {
			$line = substr($line,13);
			$i = strpos($line,"`");
			$table_name = substr($line,0,$i);
			$line = substr($line, $i+1);
			$i = strpos($line,"(");
			$line = substr($line,$i+1);
			$table_columns = array();
			do {
				$i = strpos($line,"`");
				if ($i === false) break;
				$line = substr($line,$i+1);
				$i = strpos($line,"`");
				if ($i === false) break;
				array_push($table_columns, substr($line,0,$i));
				$line = substr($line,$i+1);
				$i = strpos($line, ",");
				if ($i === false) break;
				$line = substr($line, $i+1);
			} while ($line <> "");
			if (!isset($sql[$table_name])) $sql[$table_name] = array();
			continue;
		} else {
			if ($table_name == null) continue;
			if (substr($line,0,1) <> "(") continue;
			$line = substr($line,1);
			$row = array();
			$col_index = 0;
			do {
				if (substr($line,0,1) == "'") {
					$val = "'";
					$line = substr($line,1);
					do {
						$i = strpos($line,"'");
						$val .= substr($line,0,$i+1);
						$line = substr($line,$i+1);
						if (substr($line,0,1) == "'") {
							$val .= "'";
							$line = substr($line,1);
							continue;
						}
						break;
					} while ($line <> "");
					$row[$table_columns[$col_index++]] = $val;
					$line = trim($line);
					if ($line == "") break;
					$c = substr($line,0,1);
					if ($c == ")") {
						$line = trim(substr($line,1));
						break;
					}
					if ($c == ",") {
						$line = trim(substr($line,1));
						continue;
					}
					// ???
					$line = trim($line);
					continue;
				} else {
					$i = strpos($line, ",");
					$j = strpos($line, ")");
					if ($j !== false && ($i === false || $j < $i)) $i = $j;
					$row[$table_columns[$col_index++]] = substr($line,0,$i);
					$line = trim(substr($line,$i+1));
					if ($i === $j) break;
				}
			} while ($line <> "");
			if (count($row) <> 0) array_push($sql[$table_name], $row);
			if (trim($line) == ",") continue;
			if (trim($line) == ";") {
				$table_name = null;
			}
		}
	}
	fclose($f);
	return $sql;
}

if ($has_sql) {
	//$previous_datamodel = json_decode(file_get_contents($_POST["path"]."/latest/datamodel.json"),true);
	$new_datamodel = json_decode(file_get_contents($_POST["path"]."/datamodel/datamodel.json"),true);

	?>
	<script type='text/javascript'>
	var sql_count = 0;
	function initDataChanged(button, sql) {
		var form = document.forms['deploy'];
		var input = document.createElement("INPUT");
		input.type = "hidden";
		input.name = "sql"+(sql_count++);
		input.value = sql;
		form.appendChild(input);
		button.parentNode.parentNode.removeChild(button.parentNode);
	}
	</script>
	<?php 
	echo "<br/>";
	echo "Some of the files above are SQL files. What needs to be done in the migration script ?<br/>";
	echo "<ul>";
	$sql_id_count = 0;
	foreach ($remove as $file) {
		if (substr($file,strlen($file)-4) <> ".sql") continue;
		// TODO
	}
	foreach ($add as $file) {
		if (substr($file,strlen($file)-4) <> ".sql") continue;
		// TODO
	}
	foreach ($change as $file) {
		if (substr($file,strlen($file)-4) <> ".sql") continue;
		$prev_content = analyzeSQL($previous_path.$file);
		$new_content = analyzeSQL($new_path.$file);
		set_time_limit(300);
		foreach ($prev_content as $table_name=>$rows) {
			if (!isset($new_content[$table_name])) {
				echo "<li>Data from table $table_name are not present anymore (".count($rows)." rows)";
				// TODO
				echo "</li>";
				continue;
			}
			$new_rows = $new_content[$table_name];
			unset($new_content[$table_name]);
			$table = null;
			foreach ($new_datamodel["result"]["model"]["tables"] as $t) if ($t["name"] == $table_name) { $table = $t; break; }
			$key = $table["key"];
			set_time_limit(300);
			$prev = array();
			foreach ($rows as $row) {
				if (is_array($key)) {
					$key_value = "";
					foreach ($key as $k) { if ($key_value <> "") $key_value .= "-"; $key_value .= $row[$k]; }
				} else {
					$key_value = $row[$key];
				}
				$prev[$key_value] = $row;
			}
			$new = array();
			foreach ($new_rows as $row) {
				if (is_array($key)) {
					$key_value = "";
					foreach ($key as $k) { if ($key_value <> "") $key_value .= "-"; $key_value .= $row[$k]; }
				} else {
					$key_value = $row[$key];
				}
				$new[$key_value] = $row;
			}
			set_time_limit(300);
			foreach ($prev as $key=>$row) {
				$new_row = @$new[$key];
				if ($new_row == null) {
					echo "<li>Row removed from table $table_name: ";
					// TODO
					echo "</li>";
				} else {
					unset($new[$key]);
					$cols_changed = array();
					foreach ($row as $col=>$val)
						if ($new_row[$col] <> $val) $cols_changed[$col] = array($val,$new_row[$col]);
					if (count($cols_changed) == 0) continue; // same row, no change
					echo "<li>Row ".$table["key"]."=".$key_value." changed: ";
					$sql = "UPDATE `$table_name` SET ";
					$first = true;
					foreach ($cols_changed as $col=>$vals) {
						if ($first) $first = false; else { echo ", "; $sql .= ","; }
						echo "$col=".$vals[1]."(instead of ".$vals[0].")";
						$sql .= "`$col`=".$vals[1];
					}
					$sql .= " WHERE ";
					if (is_array($table["key"])) {
						$first = true;
						foreach ($table["key"] as $k) {
							if ($first) $first = false; else $sql .= " AND ";
							$sql .= "`$k`";
							if ($new_row[$k] === null) $sql .= " IS NULL"; else $sql .= "='".$new_row[$k]."'";
						}
					} else {
						$sql .= "`".$table["key"]."`='".$key."'";
					}
					$id = $sql_id_count++;
					echo "<script type='text/javascript'>window.sql$id = ".json_encode($sql).";</script>";
					echo "<button onclick=\"initDataChanged(this, window.sql$id);\">Yes, include this change</button>";
					echo "</li>";
				}
			}
			set_time_limit(300);
			foreach ($new as $key=>$row) {
				echo "<li>Row added into table $table_name:";
				// TODO
				echo "</li>";
			}
		}
		set_time_limit(300);
		foreach ($new_content as $table_name=>$rows) {
			echo "<li>Data for table $table_name:";
			// TODO
			echo "</li>";
		}
	}
	echo "</ul>";
	echo "<br/>";
}

?>
Are you sure you made the necessary migration scripts ?<br/>
<button onclick="this.parentNode.insertBefore(document.createTextNode('Generating zip files...'),this);this.parentNode.removeChild(this);document.forms['deploy'].submit();">Yes, finish to generate the release</button>
</div>
<?php include("footer.inc"); ?>