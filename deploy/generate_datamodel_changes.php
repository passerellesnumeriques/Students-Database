<?php 
global $has_errors;
$has_errors = false;
set_error_handler(function($severity, $message, $filename, $lineno) {
	if (error_reporting() == 0) return true;
	$has_errors = true;
	return true;
});

// write the new datamodel
$f = fopen(realpath($_POST["path"])."/datamodel/datamodel.json","w");
fwrite($f,$_POST["datamodel_json"]);
fclose($f);
$f = fopen(realpath($_POST["path"])."/datamodel/datamodel.sql","w");
fwrite($f,$_POST["datamodel_sql"]);
fclose($f);

// write the migration script
$changes = json_decode($_POST["changes"],true);
$f = fopen($_POST["path"]."/migration/datamodel_update.php","w");

fwrite($f,"<?php \n");
fwrite($f,"global \$db_config;\n");
fwrite($f,"require_once(\"DataBaseSystem_\".\$db_config[\"type\"].\".inc\");\n");
fwrite($f,"\$db_system_class = \"DataBaseSystem_\".\$db_config[\"type\"];\n");
fwrite($f,"\$db_system = new \$db_system_class;\n");
fwrite($f,"\$res = \$db_system->connect(\$db_config[\"server\"], \$db_config[\"user\"], \$db_config[\"password\"]);\n");
fwrite($f,"if (\$res <> DataBaseSystem::ERR_OK)\n");
fwrite($f,"\tdie(\"Error: unable to migrate because we cannot connect to the database\");\n");
fwrite($f,"require_once(\"component/data_model/DataBaseUtilities.inc\");\n");
fwrite($f,"foreach (PNApplication::\$instance->getDomains() as \$domain=>\$conf) {\n");
fwrite($f,"\$db_system->execute(\"USE \".\$db_config['prefix'].\$domain);\n");

$new_tables_root = array();
$new_tables_sm = array();
$removed_tables_root = array();
$removed_tables_sm = array();
$rename_tables_root = array();
$rename_tables_sm = array();
$new_columns_root = array();
$new_columns_sm = array();
$removed_columns_root = array();
$removed_columns_sm = array();
$rename_columns_root = array();
$rename_columns_sm = array();
$indexes_added_root = array();
$indexes_removed_root = array();
$indexes_added_sm = array();
$indexes_removed_sm = array();
foreach ($changes as $change) {
	switch ($change["type"]) {
		case "add_table":
			if ($change["parent_table"] == null)
				array_push($new_tables_root, $change["table"]["name"]);
			else {
				if (!isset($new_tables_sm[$change["parent_table"]]))
					$new_tables_sm[$change["parent_table"]] = array();
				array_push($new_tables_sm[$change["parent_table"]], $change["table"]["name"]);
			}
			break;
		case "remove_table":
			if ($change["parent_table"] == null)
				array_push($removed_tables_root, $change["table_name"]);
			else {
				if (!isset($removed_tables_sm[$change["parent_table"]]))
					$removed_tables_sm[$change["parent_table"]] = array();
				array_push($removed_tables_sm[$change["parent_table"]], $change["table_name"]);
			}
			break;
		case "rename_table":
			if ($change["parent_table"] == null)
				array_push($rename_tables_root, array($change["old_table_name"],$change["new_table_name"]));
			else {
				if (!isset($rename_tables_sm[$change["parent_table"]]))
					$rename_tables_sm[$change["parent_table"]] = array();
				$removed_tables_sm[$change["parent_table"]][$change["old_table_name"]] = $change["new_table_name"];
			}
			break;
		case "add_column":
			if ($change["parent_table"] == null) {
				if (!isset($new_columns_root[$change["table"]])) $new_columns_root[$change["table"]] = array();
				array_push($new_columns_root[$change["table"]], $change["column"]);
			} else {
				if (!isset($new_columns_sm[$change["parent_table"]])) $new_columns_sm[$change["parent_table"]] = array();
				if (!isset($new_columns_sm[$change["parent_table"]][$change["table"]])) $new_columns_sm[$change["parent_table"]][$change["table"]] = array();
				array_push($new_columns_sm[$change["parent_table"]][$change["table"]], $change["column"]);
			}
			break;
		case "remove_column":
			if ($change["parent_table"] == null) {
				if (!isset($removed_columns_root[$change["table"]])) $removed_columns_root[$change["table"]] = array();
				array_push($removed_columns_root[$change["table"]], $change["column"]);
			} else {
				if (!isset($removed_columns_sm[$change["parent_table"]])) $removed_columns_sm[$change["parent_table"]] = array();
				if (!isset($removed_columns_sm[$change["parent_table"]][$change["table"]])) $removed_columns_sm[$change["parent_table"]][$change["table"]] = array();
				array_push($removed_columns_sm[$change["parent_table"]][$change["table"]], $change["column"]);
			}
			break;
		case "rename_column":
			if ($change["parent_table"] == null) {
				if (!isset($rename_columns_root[$change["new_table_name"]])) $rename_columns_root[$change["new_table_name"]] = array();
				$rename_columns_root[$change["new_table_name"]][$change["old_column_name"]] = $change["new_column_name"];
			} else {
				if (!isset($rename_columns_sm[$change["parent_table"]])) $rename_columns_sm[$change["parent_table"]] = array();
				if (!isset($rename_columns_sm[$change["parent_table"]][$change["table"]])) $rename_columns_sm[$change["parent_table"]][$change["table"]] = array();
				$rename_columns_sm[$change["parent_table"]][$change["table"]][$change["old_column_name"]] = $change["new_column_name"];
			}
			break;
		case "column_spec":
			if ($change["parent_table"] == null) {
				if (!isset($rename_columns_root[$change["new_table_name"]])) $rename_columns_root[$change["new_table_name"]] = array();
				$rename_columns_root[$change["new_table_name"]][$change["old_spec"]["name"]] = $change["new_spec"]["name"];
			} else {
				if (!isset($rename_columns_sm[$change["parent_table"]])) $rename_columns_sm[$change["parent_table"]] = array();
				if (!isset($rename_columns_sm[$change["parent_table"]][$change["table"]])) $rename_columns_sm[$change["parent_table"]][$change["table"]] = array();
				$rename_columns_sm[$change["parent_table"]][$change["table"]][$change["old_spec"]["name"]] = $change["new_spec"]["name"];
			}
			break;
		case "index_removed":
			if ($change["parent_table"] == null) {
				if (!isset($indexes_removed_root[$change["table"]])) $indexes_removed_root[$change["table"]] = array();
				array_push($indexes_removed_root[$change["table"]], $change);
			} else {
				if (!isset($indexes_removed_sm[$change["parent_table"]])) $indexes_removed_sm[$change["parent_table"]] = array();
				if (!isset($indexes_removed_sm[$change["parent_table"]][$change["table"]])) $indexes_removed_sm[$change["parent_table"]][$change["table"]] = array();
				array_push($indexes_removed_sm[$change["parent_table"]][$change["table"]], $change);
			}
			break;
		case "index_added":
			if ($change["parent_table"] == null) {
				if (!isset($indexes_added_root[$change["table"]])) $indexes_added_root[$change["table"]] = array();
				array_push($indexes_added_root[$change["table"]], $change);
			} else {
				if (!isset($indexes_added_sm[$change["parent_table"]])) $indexes_added_sm[$change["parent_table"]] = array();
				if (!isset($indexes_added_sm[$change["parent_table"]][$change["table"]])) $indexes_added_sm[$change["parent_table"]][$change["table"]] = array();
				array_push($indexes_added_sm[$change["parent_table"]][$change["table"]], $change);
			}
			break;
		default: die("Unknown datamodel change type ".$change["type"]);
	}
}
// first add new tables
foreach ($new_tables_root as $table) {
	fwrite($f, "\$table = DataModel::get()->internalGetTable(\"".$table."\");\n");
	fwrite($f, "DataBaseUtilities::createTable(\$db_system, \$table);\n");
}
foreach ($new_tables_sm as $parent_table=>$tables) {
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\"$parent_table\");\n");
	foreach ($tables as $table) {
		fwrite($f, "\$table = \$sm->internalGetTable(\"".$table."\");\n");
		fwrite($f, "foreach (\$sm->getExistingInstances() as \$sub_model)\n");
		fwrite($f, "\tDataBaseUtilities::createTable(\$db_system, \$table, \"_\".\$sub_model);\n");
	}
}
// then, remove tables
foreach ($removed_tables_root as $table) {
	fwrite($f, "\$db_system->execute(\"DROP TABLE `".$table."`\");\n");
}
foreach ($removed_tables_sm as $parent_table=>$tables) {
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\"$parent_table\");\n");
	foreach ($tables as $table) {
		fwrite($f, "foreach (\$sm->getExistingInstances() as \$sub_model)\n");
		fwrite($f, "\t\$db_system->execute(\"DROP TABLE `".$table."_\".\$sub_model.\"`\");\n");
	}
}
// then, rename tables
foreach ($rename_tables_root as $rename) {
	fwrite($f, "\$db_system->execute(\"RENAME TABLE `".$rename[0]."` TO `".$rename[1]."`\");\n");
}
foreach ($rename_tables_sm as $parent_table=>$renames) {
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\"$parent_table\");\n");
	fwrite($f, "foreach (\$sm->getExistingInstances() as \$sub_model) {\n");
	foreach ($renames as $from=>$to) {
		fwrite($f, "\t\$db_system->execute(\"RENAME TABLE `".$from."_\".\$sub_model.\"` TO `".$to."_\".\$sub_model.\"`\");\n");
	}
	fwrite($f, "}\n");
}
//then, remove indexes
foreach ($indexes_removed_root as $table_name=>$changes) {
	foreach ($changes as $c) {
		if ($c["index_name"] == "PRIMARY") {
			fwrite($f, "\$db_system->execute(\"DROP INDEX `PRIMARY` ON `$table_name`\");\n");
			// remove auto_increment
			fwrite($f, "\$table = DataModel::get()->internalGetTable(\"".$table_name."\");\n");
			fwrite($f, "\$col = \$table->internalGetColumn(\"".$c["key"]."\");\n");
			fwrite($f, "if (\$col <> null) \$db_system->execute(\"ALTER TABLE `$table_name` CHANGE COLUMN \".\$col->get_sql());\n");
		} else
			fwrite($f, "\$db_system->execute(\"DROP INDEX `".$c["index_name"]."` ON `$table_name`\");\n");
	}
}
foreach ($indexes_removed_sm as $parent_table=>$list) {
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\"$parent_table\");\n");
	fwrite($f, "foreach (\$sm->getExistingInstances() as \$sub_model) {\n");
	foreach ($list as $table_name=>$changes) {
		foreach ($changes as $c) {
			if ($c["index_name"] == "PRIMARY") {
				fwrite($f, "\$db_system->execute(\"DROP INDEX `PRIMARY` ON `".$table_name."_\".\$sub_model.\"`\");\n");
				// remove auto_increment
				fwrite($f, "\$table = \$sm->internalGetTable(\"".$table_name."\");\n");
				fwrite($f, "\$col = \$table->internalGetColumn(\"".$c["key"]."\");\n");
				fwrite($f, "if (\$col <> null) \$db_system->execute(\"ALTER TABLE `".$table_name."_\".\$sub_model.\"` CHANGE COLUMN \".\$col->get_sql());\n");
			} else
				fwrite($f, "\$db_system->execute(\"DROP INDEX `".$c["index_name"]."` ON `".$table_name."_\".\$sub_model.\"`\");\n");
		}
	}
	fwrite($f, "}\n");
}
// then, add new columns
foreach ($new_columns_root as $table_name=>$new_cols) {
	fwrite($f, "\$table = DataModel::get()->internalGetTable(\"".$table_name."\");\n");
	foreach ($new_cols as $col) {
		fwrite($f, "\$col = \$table->internalGetColumn(\"".$col["name"]."\");\n");
		$sql = "ALTER TABLE `$table_name`";
		$sql .= " ADD COLUMN \".\$col->get_sql().\"";
		if ($col["type"] == "PrimaryKey") $sql .= " PRIMARY KEY";
		fwrite($f, "\$db_system->execute(\"$sql\");\n");
	}
}
foreach ($new_columns_sm as $parent_table=>$new_columns) {
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\"$parent_table\");\n");
	fwrite($f, "foreach (\$sm->getExistingInstances() as \$sub_model) {\n");
	foreach ($new_columns as $table_name=>$new_cols) {
		fwrite($f, "\t\$table = \$sm->internalGetTable(\"".$table_name."\");\n");
		foreach ($new_cols as $col) {
			fwrite($f, "\t\$col = \$table->internalGetColumn(\"".$col["name"]."\");\n");
			$sql = "ALTER TABLE `".$table_name."_"."\".\$sub_model.\"`";
			$sql .= " ADD COLUMN \".\$col->get_sql().\"";
			if ($col["type"] == "PrimaryKey") $sql .= " PRIMARY KEY";
			fwrite($f, "\t\$db_system->execute(\"$sql\");\n");
		}
	}
	fwrite($f, "}\n");
}
// then, remove columns
foreach ($removed_columns_root as $table_name=>$cols) {
	foreach ($cols as $col)
		fwrite($f, "\$db_system->execute(\"ALTER TABLE `$table_name` DROP COLUMN `$col`\");\n");
}
foreach ($removed_columns_sm as $parent_table=>$to_remove) {
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\"$parent_table\");\n");
	fwrite($f, "foreach (\$sm->getExistingInstances() as \$sub_model) {\n");
	foreach ($to_remove as $table_name=>$cols)
		foreach ($cols as $col)
			fwrite($f, "\t\$db_system->execute(\"ALTER TABLE `".$table_name."_\".\$sub_model.\"` DROP COLUMN `$col`\");\n");
	fwrite($f, "}\n");
}
// then, rename/change columns
foreach ($rename_columns_root as $table_name=>$renames) {
	fwrite($f, "\$table = DataModel::get()->internalGetTable(\"".$table_name."\");\n");
	foreach ($renames as $old_name=>$new_name) {
		$sql = "ALTER TABLE `$table_name`";
		$sql .= " CHANGE COLUMN `$old_name` \".\$table->internalGetColumn(\"$new_name\")->get_sql().\"";
		fwrite($f, "\$db_system->execute(\"$sql\");\n");
	}
}
foreach ($rename_columns_sm as $parent_table=>$list) {
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\"$parent_table\");\n");
	fwrite($f, "foreach (\$sm->getExistingInstances() as \$sub_model) {\n");
	foreach ($list as $table_name=>$renames) {
		fwrite($f, "\t\$table = \$sm->internalGetTable(\"".$table_name."\");\n");
		foreach ($renames as $old_name=>$new_name) {
			$sql = "ALTER TABLE `".$table_name."_\".\$sub_model.\"`";
			$sql .= " CHANGE COLUMN `$old_name` \".\$table->internalGetColumn(\"$new_name\")->get_sql().\"";
			fwrite($f, "\t\$db_system->execute(\"$sql\");\n");
		}
	}
	fwrite($f, "}\n");
}
// then, add indexes
foreach ($indexes_added_root as $table_name=>$changes) {
	foreach ($changes as $c) {
		if ($c["index_name"] == "PRIMARY") {
			// add auto_increment
			//fwrite($f, "\$table = DataModel::get()->internalGetTable(\"".$table_name."\");\n");
			//fwrite($f, "\$db_system->execute(\"ALTER TABLE `$table_name` CHANGE COLUMN \".\$table->internalGetColumn(\"".$c["key"]."\")->get_sql());\n");
			// add index
			//fwrite($f, "\$db_system->execute(\"ALTER TABLE `$table_name` ADD PRIMARY KEY (`".$c["key"]."`)\");\n");
		} else if ($c["index_name"] == "table_key") {
			$sql = "ALTER TABLE `$table_name` ADD UNIQUE KEY `table_key` (";
			$first = true;
			foreach ($c["key"] as $colname) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= "`$colname`";
			}
			$sql .= ")";
			fwrite($f, "\$db_system->execute(\"$sql\");\n");
		} else {
			$sql = "ALTER TABLE `$table_name` ADD KEY `".$c["index_name"]."` (";
			$first = true;
			foreach ($c["columns"] as $colname) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= "`$colname`";
			}
			$sql .= ")";
			fwrite($f, "\$db_system->execute(\"$sql\");\n");
		}
	}
}
foreach ($indexes_added_sm as $parent_table=>$list) {
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\"$parent_table\");\n");
	fwrite($f, "foreach (\$sm->getExistingInstances() as \$sub_model) {\n");
	foreach ($list as $table_name=>$changes) {
		foreach ($changes as $c) {
			if ($c["index_name"] == "PRIMARY") {
				// add auto_increment
				fwrite($f, "\$table = \$sm->internalGetTable(\"".$table_name."\");\n");
				fwrite($f, "\$db_system->execute(\"ALTER TABLE `".$table_name."_\".\$sub_model.\"` CHANGE COLUMN \".]$table->internalGetColumn(\"".$c["key"]."\")->get_sql());\n");
				// add index
				fwrite($f, "\$db_system->execute(\"ALTER TABLE `".$table_name."_\".\$sub_model.\"` ADD PRIMARY KEY (`".$c["key"]."`)\");\n");
			} else if ($c["index_name"] == "table_key") {
				$sql = "ALTER TABLE `".$table_name."_\".\$sub_model.\"` ADD UNIQUE KEY `table_key` (";
				$first = true;
				foreach ($c["key"] as $colname) {
					if ($first) $first = false; else $sql .= ",";
					$sql .= "`$colname`";
				}
				$sql .= ")";
				fwrite($f, "\$db_system->execute(\"$sql\");\n");
			} else {
				$sql = "ALTER TABLE `".$table_name."_\".\$sub_model.\"` ADD KEY `".$c["index_name"]."` (";
				$first = true;
				foreach ($c["columns"] as $colname) {
					if ($first) $first = false; else $sql .= ",";
					$sql .= "`$colname`";
				}
				$sql .= ")";
				fwrite($f, "\$db_system->execute(\"$sql\");\n");
			}
		}
	}
	fwrite($f,"}\n");
}
fwrite($f,"}\n"); // foreach domain
fwrite($f,"?>");
fclose($f);

if ($has_errors) die();
?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Data Model migration script generated.<br/>
Copying files...
<form name='deploy' method="POST" action="copy.php">
<input type='hidden' name='version' value='<?php echo $_POST["version"];?>'/>
<input type='hidden' name='path' value='<?php echo $_POST["path"];?>'/>
<input type='hidden' name='latest' value='<?php echo $_POST["latest"];?>'/>
<input type='hidden' name='channel' value='<?php echo $_POST["channel"];?>'/>
</form>

</div>
<script type='text/javascript'>
document.forms['deploy'].submit();
</script>
<?php include("footer.inc");?>