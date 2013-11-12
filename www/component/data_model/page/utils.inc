<?php 
function datamodel_cell(&$page, $container_id, $editable, $table, $column, $key, $sub_models, $value, $onchange = null) {
	$page->add_javascript("/static/widgets/typed_field/typed_field.js");
	require_once("component/data_model/Model.inc");
	$t = DataModel::get()->getTable($table);
	$col = $t->getColumn($column, $sub_models);
	$typed_field = PNApplication::$instance->widgets->get_typed_field($col);
	$page->add_javascript("/static/widgets/typed_field/".$typed_field[0].".js");
	if ($editable) {
		$page->add_javascript("/static/data_model/editable_cell.js");
		$page->onload("new editable_cell(".json_encode($container_id).",".json_encode($table).",".json_encode($column).",".json_encode($key).",".json_encode($typed_field[0]).",".$typed_field[1].",".json_encode($value).",null,".($onchange <> null ? $onchange : "null").");");
	} else {
		$page->onload("{var f=new ".$typed_field[0]."(".json_encode($value).",false,".$typed_field[1].");f.register_datamodel_cell(".json_encode($table).",".json_encode($column).",".json_encode($key).");document.getElementById(".json_encode($container_id).").appendChild(f.getHTMLElement());".($onchange <> null ? "f.onchange.add_listener(".$onchange.");" : "")."};");
	}
}

function datamodel_datadisplay(&$page, $data, $data_key, $value, $sub_model, $container_id, $can_edit, $onchange = null) {
	$typed_field = $data->getTypedField($sub_model);
	$page->add_javascript("/static/widgets/typed_field/typed_field.js");
	$page->add_javascript("/static/widgets/typed_field/".$typed_field[0].".js");
	$page->add_javascript("/static/data_model/DataDisplay.js");
	$table = $data->handler->table;
	if ($data->isEditable() && $can_edit) {
		$page->add_javascript("/static/data_model/editable_datadisplay.js");
		$page->onload("new editable_datadisplay('".$container_id."',".$data->javascriptDataDisplay($sub_model).",null,".json_encode($data_key).",".json_encode($value).",".($onchange <> null ? $onchange : "null").");");
	} else {
		$page->onload("{var f=new ".$typed_field[0]."(".json_encode($value).",false,null,null,".$typed_field[1].");f.register_datamodel_datadisplay(".$data->javascriptDataDisplay($sub_model).",".json_encode($data_key).");document.getElementById('$container_id').appendChild(f.getHTMLElement());".($onchange <> null ? "f.onchange.add_listener(".$onchange.");" : "")."};");
	}
	
}
?>