<?php
require_once("component/data_model/Model.inc");

function data_entity_page_from_key(&$page, $table, $key, $sub_models = null) {
	if (is_string($table)) {
		try { $table = DataModel::get()->getTable($table); }
		catch (Exception $e) {
			PNApplication::error("Access denied to table ".$table.": ".$e->getMessage());
			return;
		}
	}
	$entity = SQLQuery::create()->set_sub_models($sub_models)->select($table->getName())->where($table->getPrimaryKey()->name,$key)->execute_single_row();
	if ($entity == null) return;
	data_entity_page($page, $table, $entity, $sub_models);
}

function data_entity_page(&$page, $table, &$entity, $sub_models = null) {
	if (is_string($table)) {
		try { $table = DataModel::get()->getTable($table); }
		catch (Exception $e) {
			PNApplication::error("Access denied to table ".$table.": ".$e->getMessage());
			return;
		}
	}
	echo "<table>";
	echo "<tr>";
	foreach ($table->getDisplayableData() as $field=>$name) {
		$col = $table->getColumn($field, $sub_models);
		echo "<td>";
		echo $table->getDisplayableDataName($field);
		echo "</td>";
		if ($table->canModifyField($field)) {
			$id = $page->generate_id();
			echo "<td id='".$id."'>";
			$typed_field = PNApplication::$instance->widgets->get_typed_field($col);
			$page->add_javascript("/static/widgets/typed_field/typed_field.js");
			$page->add_javascript("/static/widgets/typed_field/"+$typed_field[0]+".js");
			$page->add_javascript("/static/data_model/editable_cell.js");
			$page->onload("new editable_cell('".$id."',".json_encode($table->getSQLName($sub_models)).",".json_encode($col->name).",".json_encode($entity[$table->getPrimaryKey()->name]).",'".$typed_field[0]."',".$typed_field[1].",".json_encode($entity[$col->name]).");");
		} else
			echo $entity[$col->name];
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}
?>