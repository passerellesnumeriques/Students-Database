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
		echo "<td>";
		echo $table->getDisplayableDataName($field);
		echo "</td>";
		echo "<td>";
		echo $entity[$field];
		echo "</td>";
		echo "<td>";
		if ($table->canModifyField($field)) {
			$typed_field = PNApplication::$instance->widgets->get_typed_field($table->getColumn($field));
			echo "<img src='".theme::$icons_16["edit"]."' onclick=\"data_entity_edit(event,this,this.parentNode.previousSibling,'".$table->getName()."','".$field."','".$typed_field[0]."','".htmlentities($typed_field[1],ENT_QUOTES,"UTF-8")."','".$entity[$table->getPrimaryKey()->name]."');return false;\" style='cursor:pointer;vertical-align:bottom'/>";
		}
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
	$page->add_javascript("/static/data_model/entity.js");
}
?>