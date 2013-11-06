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
	$row = SQLQuery::create()->set_sub_models($sub_models)->select($table->getName())->where($table->getPrimaryKey()->name,$key)->execute_single_row();
	if ($row == null) return;
	table_datadisplay_page($page, $table, $row, $sub_models);
}

function table_datadisplay_page(&$page, $table, &$row, $sub_models = null) {
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
		$value = $data->getData($row[$table->getPrimaryKey()->name], $sub_model, $row);
		$id = $page->generate_id();
		echo "<td id='".$id."'>";
		datamodel_datadisplay($page, $data, $row[$table->getPrimaryKey()->name], $value, $sub_model, $id, true);
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}
?>