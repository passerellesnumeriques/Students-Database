<?php 
function datamodel_cell(&$page, $container_id, $editable, $table, $column, $key, $sub_models, $value, $onchange = null) {
	$page->addJavascript("/static/widgets/typed_field/typed_field.js");
	require_once("component/data_model/Model.inc");
	$t = DataModel::get()->getTable($table);
	$col = $t->getColumn($column, $sub_models);
	$typed_field = PNApplication::$instance->widgets->get_typed_field($col);
	$page->addJavascript("/static/widgets/typed_field/".$typed_field[0].".js");
	if ($editable) {
		$page->addJavascript("/static/data_model/editable_cell.js");
		$page->onload("new editable_cell(".json_encode($container_id).",".json_encode($table).",".json_encode($column).",".json_encode($key).",".json_encode($typed_field[0]).",".$typed_field[1].",".json_encode($value).",null,".($onchange <> null ? $onchange : "null").");");
	} else {
		$page->onload("{var f=new ".$typed_field[0]."(".json_encode($value).",false,".$typed_field[1].");f.register_datamodel_cell(".json_encode($table).",".json_encode($column).",".json_encode($key).");document.getElementById(".json_encode($container_id).").appendChild(f.getHTMLElement());".($onchange <> null ? "f.onchange.addListener(".$onchange.");" : "")."};");
	}
}

function datamodel_cell_here(&$page, $editable, $table, $column, $key, $value, $sub_model) {
	if ($editable) {
		$page->addJavascript("/static/data_model/editable_cell.js");
		$page->addJavascript("/static/widgets/typed_field/typed_field.js");
		require_once("component/data_model/Model.inc");
		$t = DataModel::get()->getTable($table);
		$col = $t->getColumnFor($column, $sub_model);
		$typed_field = PNApplication::$instance->widgets->get_typed_field($col);
		$page->addJavascript("/static/widgets/typed_field/".$typed_field[0].".js");
		$id = $page->generateID();
		echo "<span id='$id'></span>";
		echo "<script type='text/javascript'>";
		echo "new editable_cell('$id',".json_encode($table).",".json_encode($column).",".json_encode($key).",".json_encode($typed_field[0]).",".$typed_field[1].",".json_encode($value).");";
		echo "</script>";
	} else {
		echo toHTML($value);
	}
}

function datamodel_cell_inline(&$page, $variable_name, $container_variable, $editable, $table, $column, $key_variable, $sub_models, $value_variable, $onchange = null) {
	$page->addJavascript("/static/widgets/typed_field/typed_field.js");
	require_once("component/data_model/Model.inc");
	$t = DataModel::get()->getTable($table);
	$col = $t->getColumn($column, $sub_models);
	$typed_field = PNApplication::$instance->widgets->get_typed_field($col);
	$page->addJavascript("/static/widgets/typed_field/".$typed_field[0].".js");
	if ($editable) {
		$page->addJavascript("/static/data_model/editable_cell.js");
		echo "$variable_name = new editable_cell($container_variable,".json_encode($table).",".json_encode($column).",$key_variable,".json_encode($typed_field[0]).",".$typed_field[1].",$value_variable,null,".($onchange <> null ? $onchange : "null").");";
	} else {
		echo "$variable_name = new ".$typed_field[0]."($value_variable,false,".$typed_field[1].");$variable_name.register_datamodel_cell(".json_encode($table).",".json_encode($column).",$key_variable);$container_variable.appendChild($variable_name.getHTMLElement());".($onchange <> null ? "$variable_name.onchange.addListener($onchange);" : "");
	}
}

function datamodel_cell_new(&$page, $variable_name, $table, $column, $fake_key_variable, $sub_models = null) {
	$page->addJavascript("/static/widgets/typed_field/typed_field.js");
	require_once("component/data_model/Model.inc");
	$t = DataModel::get()->getTable($table);
	$col = $t->getColumn($column, $sub_models);
	$typed_field = PNApplication::$instance->widgets->get_typed_field($col);
	$page->addJavascript("/static/widgets/typed_field/".$typed_field[0].".js");
	echo "$variable_name=new ".$typed_field[0]."(null,true,".$typed_field[1].");";
	echo "$variable_name.register_datamodel_cell(".json_encode($table).",".json_encode($column).",".$fake_key_variable.");";
}

function datamodel_datadisplay(&$page, $data, $data_key, $value, $sub_model, $container_id, $can_edit, $onchange = null) {
	$typed_field = $data->getTypedField($sub_model);
	$page->addJavascript("/static/widgets/typed_field/typed_field.js");
	$page->addJavascript("/static/widgets/typed_field/".$typed_field[0].".js");
	$page->addJavascript("/static/data_model/datadisplay.js");
	$table = $data->handler->table;
	if ($data->isEditable() && $can_edit) {
		$page->addJavascript("/static/data_model/editable_datadisplay.js");
		$page->onload("new editable_datadisplay('".$container_id."',".$data->javascriptDataDisplay($sub_model).",null,".json_encode($data_key).",".json_encode($sub_model).",".json_encode($value).",".($onchange <> null ? $onchange : "null").");");
	} else {
		$page->onload("{var f=new ".$typed_field[0]."(".json_encode($value).",false,".$typed_field[1].");f.register_datamodel_datadisplay(".$data->javascriptDataDisplay($sub_model).",".json_encode($data_key).");document.getElementById('$container_id').appendChild(f.getHTMLElement());".($onchange <> null ? "f.onchange.addListener(".$onchange.");" : "")."};");
	}
	
}

function datamodel_datadisplay_new(&$page, $data, $sub_model, $variable_name, $fake_key_variable) {
	$typed_field = $data->getTypedField($sub_model);
	$page->addJavascript("/static/widgets/typed_field/typed_field.js");
	$page->addJavascript("/static/widgets/typed_field/".$typed_field[0].".js");
	$page->addJavascript("/static/data_model/datadisplay.js");
	$table = $data->getTable();
	echo "$variable_name=new ".$typed_field[0]."(".json_encode($data->getNewData()).",true,".$typed_field[1].");";
	echo "$variable_name.register_datamodel_datadisplay(".$data->javascriptDataDisplay($sub_model).",$fake_key_variable);";
}
?>