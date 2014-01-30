<?php 
function table_datadisplay_edit(&$page, $table, $sub_models, $key, $javascript_object_name, $additional_config = array()) {
	require_once("component/data_model/Model.inc");
	require_once("component/data_model/page/utils.inc");
	$t = DataModel::get()->getTable($table);
	$display = $t->getDisplayHandler(null);
	if ($display == null) {
		PNApplication::error("No DisplayHandler on table ".$table.": used in table_datadisplay_edit");
		return;
	}
	$sub_model = $sub_models <> null && $t->getModel() instanceof SubDataModel && isset($sub_models[$t->getModel()->getParentTable()]) ? $sub_models[$t->getModel()->getParentTable()] : null;
	$row = $key <> null ? SQLQuery::create()->select($table)->setSubModels($sub_models)->where($t->getPrimaryKey()->name, $key)->executeSingleRow() : null;
	
	$page->add_javascript("/static/data_model/DataDisplay.js");
	$page->add_javascript("/static/widgets/typed_field/typed_field.js");
?>
<script type='text/javascript'>
function <?php echo $javascript_object_name;?>(container, on_validate, additional_config) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	this.fields = [];

	this.validate = function() {
		var err = null;
		for (var i = 0; i < this.fields.length; ++i) {
			err = this.fields[i].field.getError();
			if (err != null) {
				err = this.fields[i].field.data_name + ": " + err;
				break;
			}
		}
		on_validate(err);
	};

	this.save = function(handler, predefined_data) {
		var data = { data: [] };
		if (predefined_data)
			data.preset_fields = predefined_data;
		data.table = <?php echo json_encode($table); ?>;
		data.sub_model = <?php echo json_encode($sub_model); ?>;
		data.come_from = null;
		data.key = <?php echo json_encode($key); ?>;
		for (var i = 0; i < this.fields.length; ++i)
			data.data.push({name:this.fields[i].name,value:this.fields[i].field.getCurrentData()});
		service.json("data_model","save_datadisplay",data,function(result){
			if (!result)
				handler();
			else
				handler(result.key);
		});
	};

	this.get_data = function(name) {
		for (var i = 0; i < this.fields.length; ++i)
			if (this.fields[i].name == name)
				return this.fields[i].field.getCurrentData();
		return null;
	};
	
	this._create_table = function() {
		container.appendChild(this.table = document.createElement("TABLE"));
		var tr, td;
<?php
		$has_mandatory = false;
		foreach ($display->getDisplayableData() as $data)
			if ($data->isMandatory($sub_model)) { $has_mandatory = true; break; }
		if ($has_mandatory) {
			echo "this.table.appendChild(tr = document.createElement('TR'));";
			echo "tr.appendChild(td = document.createElement('TD'));";
			echo "td.colSpan = 2;";
			echo "td.style.textAlign = 'center';";
			echo "td.innerHTML = 'Fields with <sup style=\'color:red\'>*</sup> are mandatory';";
		}
			
		$data_key = $key <> null ? $key : $page->generateID();
		foreach ($display->getDisplayableData() as $data) {
			echo "this.table.appendChild(tr = document.createElement('TR'));";
			echo "tr.appendChild(td = document.createElement('TD'));";
			echo "td.innerHTML = ".json_encode($data->getDisplayName()).($data->isMandatory($sub_model) ? "+\"<sup style='color:red'>*</sup>\"" : "").";";
			echo "tr.appendChild(td = document.createElement('TD'));";
			$typed_field = $data->getTypedField($sub_model);
			$page->add_javascript("/static/widgets/typed_field/".$typed_field[0].".js");
			echo "var cfg = ".$typed_field[1].";";
			if ($typed_field[0] == "field_text") {
				echo "cfg.fixed_size = 20;";
			}
			if (isset($additional_config[$data->getDisplayName()]))
				foreach ($additional_config[$data->getDisplayName()] as $n=>$v)
					echo "cfg.".$n." = ".json_encode($v);
			echo "if (additional_config) for (var i = 0; i < additional_config.length; ++i) if (additional_config[i].data == ".json_encode($data->getDisplayName()).") for (var n in additional_config[i].config) cfg[n] = additional_config[i].config[n];";
			$value = $row <> null ? $data->getData($key, $sub_model, $row) : null;
			echo "var field = new ".$typed_field[0]."(".json_encode($value).",true,cfg);";
			echo "field.data_name = ".json_encode($data->getDisplayName()).";";
			echo "field.onchange.add_listener(function(){t.validate();});";
			echo "field.register_datamodel_datadisplay(".$data->javascriptDataDisplay($sub_model).", ".json_encode($data_key).");";
			echo "this.fields.push({name:".json_encode($data->getDisplayName()).",field:field});";
			echo "td.appendChild(field.getHTMLElement());";
		} 
?>
		this.validate();
	};
	this._create_table();
}
</script>
<?php
}
?>