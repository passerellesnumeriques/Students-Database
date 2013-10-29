<?php
require_once("component/data_model/Model.inc");

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
		$typed_field = $data->getTypedField($sub_model);
		$page->add_javascript("/static/widgets/typed_field/typed_field.js");
		$page->add_javascript("/static/widgets/typed_field/".$typed_field[0].".js");
		if ($data->isEditable()) {
			$page->add_javascript("/static/data_model/editable_datadisplay.js");
			$page->add_javascript("/static/data_model/DataDisplay.js");
			$page->onload("new editable_datadisplay('".$id."',".$data->javascriptDataDisplay($sub_model).",null,".json_encode($row[$table->getPrimaryKey()->name]).",".json_encode($value).");");
		} else {
			$page->onload("new ".$typed_field[0]."(".json_encode($value).",false,null,null,".$typed_field[1]);
		}
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}
?>