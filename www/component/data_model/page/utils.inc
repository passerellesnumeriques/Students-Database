<?php 
function datamodel_cell(&$page, $container_id, $editable, $table, $column, $key, $sub_models, $value) {
	$page->add_javascript("/static/widgets/typed_field/typed_field.js");
	require_once("component/data_model/Model.inc");
	$t = DataModel::get()->getTable($table);
	$col = $t->getColumn($column, $sub_models);
	$typed_field = PNApplication::$instance->widgets->get_typed_field($col);
	$page->add_javascript("/static/widgets/typed_field/".$typed_field[0].".js");
	if ($editable) {
		$page->add_javascript("/static/data_model/editable_cell.js");
		$page->onload("new editable_cell(".json_encode($container_id).",".json_encode($table).",".json_encode($column).",".json_encode($key).",".json_encode($typed_field[0]).",".$typed_field[1].",".json_encode($value).");");
	} else {
		$page->onload("document.getElementById(".json_encode($container_id).").appendChild(new ".$typed_field[0]."(".json_encode($value).",false,".$typed_field[1].").getHTMLElement());");
	}
}
?>