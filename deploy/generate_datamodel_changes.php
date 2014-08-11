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
fwrite($f,"\$db_system->execute(\"USE students_\".\$domain);\n");

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
				array_push($new_columns_root[$change["table"]], $change["column"]["name"]);
			} else {
				if (!isset($new_columns_sm[$change["parent_table"]])) $new_columns_sm[$change["parent_table"]] = array();
				if (!isset($new_columns_sm[$change["parent_table"]][$change["table"]])) $new_columns_sm[$change["parent_table"]][$change["table"]] = array();
				array_push($new_columns_sm[$change["parent_table"]][$change["table"]], $change["column"]["name"]);
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
		case "column_spec": break;
			if ($change["parent_table"] == null) {
				if (!isset($rename_columns_root[$change["new_table_name"]])) $rename_columns_root[$change["new_table_name"]] = array();
				$rename_columns_root[$change["new_table_name"]][$change["old_spec"]["name"]] = $change["new_spec"]["name"];
			} else {
				if (!isset($rename_columns_sm[$change["parent_table"]])) $rename_columns_sm[$change["parent_table"]] = array();
				if (!isset($rename_columns_sm[$change["parent_table"]][$change["table"]])) $rename_columns_sm[$change["parent_table"]][$change["table"]] = array();
				$rename_columns_sm[$change["parent_table"]][$change["table"]][$change["old_spec"]["name"]] = $change["new_spec"]["name"];
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
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\".$parent_table.\");\n");
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
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\".$parent_table.\");\n");
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
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\".$parent_table.\");\n");
	fwrite($f, "foreach (\$sm->getExistingInstances() as \$sub_model) {\n");
	foreach ($renames as $from=>$to) {
		fwrite($f, "\t\$db_system->execute(\"RENAME TABLE `".$from."_\".\$sub_model.\"` TO `".$to."_\".\$sub_model.\"`\");\n");
	}
	fwrite($f, "}\n");
}
// then, add new columns
foreach ($new_columns_root as $table_name=>$new_cols) {
	fwrite($f, "\$table = DataModel::get()->internalGetTable(\"".$table_name."\");\n");
	foreach ($new_cols as $col_name)
		fwrite($f, "\$db_system->execute(\"ALTER TABLE `$table_name` ADD COLUMN \".\$table->internalGetColumn(\"$col_name\")->get_sql());\n");
}
foreach ($new_columns_sm as $parent_table->$new_columns) {
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\".$parent_table.\");\n");
	fwrite($f, "foreach (\$sm->getExistingInstances() as \$sub_model) {\n");
	foreach ($new_columns as $table_name=>$new_cols) {
		fwrite($f, "\t\$table = \$sm->internalGetTable(\"".$table_name."\");\n");
		foreach ($new_cols as $col_name)
			fwrite($f, "\t\$db_system->execute(\"ALTER TABLE `".$table_name."_"."\".\$sub_model.\"` ADD COLUMN \".\$table->internalGetColumn(\"$col_name\")->get_sql());\n");
	}
	fwrite($f, "}\n");
}
// then, remove columns
foreach ($removed_columns_root as $table_name=>$cols) {
	$sql = "ALTER TABLE `$table_name`";
	foreach ($cols as $col)
		$sql .= " DROP COLUMN `$col`";
	fwrite($f, "\$db_system->execute(\"$sql\");");
}
foreach ($removed_columns_sm as $parent_table=>$to_remove) {
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\".$parent_table.\");\n");
	fwrite($f, "foreach (\$sm->getExistingInstances() as \$sub_model) {\n");
	foreach ($to_remove as $table_name=>$cols) {
		$sql = "ALTER TABLE `".$table_name."_\".\$sub_model.\"`";
		foreach ($cols as $col)
			$sql .= " DROP COLUMN `$col`";
		fwrite($f, "\t\$db_system->execute(\"$sql\");");
	}
	fwrite($f, "}\n");
}
// then, rename/change columns
foreach ($rename_columns_root as $table_name=>$renames) {
	fwrite($f, "\$table = DataModel::get()->internalGetTable(\"".$table_name."\");\n");
	$sql = "ALTER TABLE `$table_name`";
	foreach ($renames as $old_name=>$new_name) {
		$sql .= " CHANGE COLUMN `$old_name` \".\$table->internalGetColumn(\"$new_name\")->get_sql().\"";
	}
	fwrite($f, "\$db_system->execute(\"$sql\");");
}
foreach ($rename_columns_sm as $parent_table=>$list) {
	fwrite($f, "\$sm = DataModel::get()->getSubModel(\".$parent_table.\");\n");
	fwrite($f, "foreach (\$sm->getExistingInstances() as \$sub_model) {\n");
	foreach ($list as $table_name=>$renames) {
		fwrite($f, "\t\$table = \$sm->internalGetTable(\"".$table_name."\");\n");
		$sql = "ALTER TABLE `".$table_name."_\".\$sub_model.\"`";
		foreach ($renames as $old_name=>$new_name) {
			$sql .= " CHANGE COLUMN `$old_name` \".\$table->internalGetColumn(\"$new_name\")->get_sql().\"";
		}
		fwrite($f, "\t\$db_system->execute(\"$sql\");");
	}
	fwrite($f, "}\n");
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
</form>

</div>
<script type='text/javascript'>
document.forms['deploy'].submit();
</script>
<?php include("footer.inc");?>