<?php 
class service_save_template_multiple extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Save an import template, to import multiple data (one by row of the file)"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>type</code>: the template plugin</li>";
		echo "<li><code>id</code>: id of the template. This should not be provided for a new template.</li>";
		echo "<li><code>name</code>: name to give to the template</li>";
		echo "<li><code>root_table</code>: the table from which to start</li>";
		echo "<li><code>sub_model</code>: optional, the sub model of <code>root</code></li>";
		echo "<li><code>to_import</code>: the content of the template, an array of data,path[,sheet_name][,column][,row_start][,value]</li>";
		echo "</ul>";
	}
	public function outputDocumentation() {
		echo "id: on sucess the id of the template (useful when creating a new one)";
	}
	
	public function execute(&$component, $input) {
		$type = $input["type"];
		$type = $component->getTemplatePlugin($type);
		if ($type == null) { PNApplication::error("Invalid template plugin: ".$input["type"]); return; }
		if (!$type->canWrite()) { PNApplication::error("Access denied: you are not allowed to modify this template or to create a new one"); return; }
		$id = @$input["id"];
		if ($id <> null && intval($id) <= 0) $id = null;
		$name = trim($input["name"]);
		if ($name == "") { PNApplication::error("No name provided for the template"); return; }
		$root_table = $input["root_table"];
		require_once("component/data_model/Model.inc");
		$root_table = DataModel::get()->getTable($root_table);
		if ($root_table == null) { PNApplication::error("Invalid root table"); return; }
		$sub_model = @$input["sub_model"];
		if ($sub_model == null && ($root_table->getModel() instanceof SubDataModel)) { PNApplication::error("Table ".$root_table->getName()." is in a sub-model, but no sub-model is provided in input"); return; }
		$to_import = $input["to_import"];
		
		SQLQuery::startTransaction();
		$cols = array(
			"type"=>$type->getId(),
			"name"=>$name,
			"root_table"=>$root_table->getName(),
			"sub_model"=>$sub_model,
			"template_type"=>"multiple"
		);
		if ($id <> null) {
			$current = SQLQuery::create()->bypassSecurity()->select("DataImportTemplate")->whereValue("DataImportTemplate","id",$id)->executeSingleRow();
			if ($current == null) { PNApplication::error("Invalid template id"); return; }
			if ($current["template_type"] <> "multiple") { PNApplication::error("Invalid template"); return; }
			if ($current["root_table"] <> $root_table->getName())  { PNApplication::error("Invalid template"); return; }
			if ($current["sub_model"] <> $sub_model)  { PNApplication::error("Invalid template"); return; }
			SQLQuery::create()->bypassSecurity()->updateByKey("DataImportTemplate", $id, $cols);
		} else
			$id = SQLQuery::create()->bypassSecurity()->insert("DataImportTemplate", $cols);
		
		$rows = SQLQuery::create()->bypassSecurity()->select("DataImportTemplateMultiple")->whereValue("DataImportTemplateMultiple","template",$id)->execute();
		if (count($rows) > 0)
			SQLQuery::create()->bypassSecurity()->removeRows("DataImportTemplateMultiple",$rows);
		
		$rows = array();
		foreach ($to_import as $i) {
			if (!isset($i["path"])) { PNApplication::error("Invalid data to import"); return; }
			if (!isset($i["data"])) { PNApplication::error("Invalid data to import"); return; }
			if (!key_exists("value", $i))
				if (!key_exists("sheet_name",$i) || !key_exists("column",$i) || !key_exists("row_start",$i)) { PNApplication::error("Invalid data to import"); return; }
			array_push($rows, array(
				"template"=>$id,
				"data_path"=>$i["path"],
				"data_name"=>$i["data"],
				"sub_index"=>@$i["sub_index"],
				"index"=>@$i["index"],
				"sheet"=>@$i["sheet_name"],
				"column"=>@$i["column"],
				"row_start"=>@$i["row_start"],
				"value"=>key_exists("value",$i) ? json_encode($i["value"]) : null
			));
		}
		if (count($rows) > 0)
			SQLQuery::create()->bypassSecurity()->insertMultiple("DataImportTemplateMultiple",$rows);
		
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "{id:$id}";
		}
	}
	
}
?>