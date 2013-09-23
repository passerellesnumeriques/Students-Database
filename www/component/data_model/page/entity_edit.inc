<?php 
function create_entity_edition_table(&$page, $table, $sub_models, $javascript_object_name) {
	require_once("component/data_model/Model.inc");
	$t = DataModel::get()->getTable($table);
	
	$page->add_javascript("/static/widgets/typed_field/typed_field.js");
?>
<script type='text/javascript'>
function <?php echo $javascript_object_name;?>(container, on_validate) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;

	this.validate = function() {
		var err = null;
<?php
		foreach ($t->getDisplayableData() as $col_name=>$display_name) {
			$col = $t->getColumn($col_name, $sub_models);
			$col->validate_javascript("t.field_".$col_name.".getCurrentData()","err");
			echo "if (err != null) { t.field_".$col_name.".signal_error(true);on_validate(err);return; }";
			echo "t.field_".$col_name.".signal_error(false);"; 
		}
		echo "on_validate(null);";
?>
	};

	this.save = function(handler, predefined_data) {
		var data = {};
		for (var name in predefined_data) data["field_"+name] = predefined_data[name];
<?php
		echo "data.table = ".json_encode($table).";";
		echo "data.sub_model = ".($sub_models <> null && $t->getModel() instanceof SubDataModel && isset($sub_models[$t->getModel()->getParentTable()]) ? json_encode($sub_models[$t->getModel()->getParentTable()]) : "null").";"; 
		foreach ($t->getDisplayableData() as $col_name=>$display_name)
			echo "data.field_".$col_name." = t.field_".$col_name.".getCurrentData();";
?>
		service.json("data_model","save_entity",data,function(result){
			if (!result)
				handler();
			else
				handler(result.key);
		});
	};
	
	this._create_table = function() {
		container.appendChild(this.table = document.createElement("TABLE"));
		var tr, td;
<?php
		$has_mandatory = false;
		foreach ($t->getDisplayableData() as $col_name=>$display_name)
			if (!$t->getColumn($col_name, $sub_models)->can_be_null) { $has_mandatory = true; break; }
		if ($has_mandatory) {
			echo "this.table.appendChild(tr = document.createElement('TR'));";
			echo "tr.appendChild(td = document.createElement('TD'));";
			echo "td.colSpan = 2;";
			echo "td.style.textAlign = 'center';";
			echo "td.innerHTML = 'Fields with <sup style=\'color:red\'>*</sup> are mandatory';";
		}
			
		foreach ($t->getDisplayableData() as $col_name=>$display_name) {
			$col = $t->getColumn($col_name, $sub_models);
			echo "this.table.appendChild(tr = document.createElement('TR'));";
			echo "tr.appendChild(td = document.createElement('TD'));";
			echo "td.innerHTML = ".json_encode($display_name[1]).($t->getColumn($col_name, $sub_models)->can_be_null ? "" : "+\"<sup style='color:red'>*</sup>\"").";";
			echo "tr.appendChild(td = document.createElement('TD'));";
			$typed = PNApplication::$instance->widgets->get_typed_field($col);
			$page->add_javascript("/static/widgets/typed_field/".$typed[0].".js");
			echo "this.field_".$col_name." = new ".$typed[0]."('',true,function(){t.validate();},function(){t.validate();},".$typed[1].");";
			echo "td.appendChild(this.field_".$col_name.".getHTMLElement());";
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