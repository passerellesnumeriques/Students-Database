<?php
require_once("component/data_model/Model.inc");
require_once("component/data_model/page/utils.inc");

function table_datadisplay_page_from_key(&$page, $table, $key, $sub_models = null) {
	if (is_string($table)) {
		try { $table = DataModel::get()->getTable($table); }
		catch (Exception $e) {
			PNApplication::error("Access denied to table ".$table.": ".$e->getMessage());
			return;
		}
	}
	$q = SQLQuery::create()->setSubModels($sub_models);
	$table_alias = $q->generateTableAlias();
	$q->select(array($table->getName() => $table_alias));
	$q->where($table->getPrimaryKey()->name,$key);
	foreach ($table->getColumns($sub_models) as $col)
		$q->field($table_alias, $col->name, "DATA_COL_".$col->name);
	$row = $q->executeSingleRow();
	if ($row == null) return;
	table_datadisplay_page($page, $table, $row, $q, $sub_models);
}

function table_datadisplay_page(&$page, $table, &$row, $row_query, $sub_models = null) {
	if (is_string($table)) {
		try { $table = DataModel::get()->getTable($table); }
		catch (Exception $e) {
			PNApplication::error("Access denied to table ".$table.": ".$e->getMessage());
			return;
		}
	}
	echo "<table>";
	echo "<tr>";
	$display = $table->getDisplayHandler(null);
	$sub_model = $sub_models <> null && $table->getModel() instanceof SubDataModel && isset($sub_models[$table->getModel()->getParentTable()]) ? $sub_models[$table->getModel()->getParentTable()] : null; 
	foreach ($display->getDisplayableData() as $data) {
		echo "<td>";
		echo $data->getDisplayName();
		echo "</td>";
		$key_alias = $row_query->getFieldAlias($table->getName(), $table->getPrimaryKey()->name);
		if ($key_alias == null) $key_alias = $table->getPrimaryKey()->name;
		$value = $data->getData($row[$key_alias], $sub_model, $row, $row_query);
		$id = $page->generateID();
		echo "<td id='".$id."'>";
		datamodel_datadisplay($page, $data, $row[$key_alias], $value, $sub_model, $id, true);
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}
?>