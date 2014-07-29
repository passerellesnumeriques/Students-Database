<?php 
class service_save_custom_table extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Customize a table"; }
	public function inputDocumentation() { echo "table, sub_model, columns and lock_id"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		$table_name = $input["table"];
		$table = DataModel::get()->getTable($table_name);
		$sub_model = null;
		if ($table->getModel() instanceof SubDataModel) {
			if (isset($input["sub_model"])) $sub_model = $input["sub_model"];
			else $sub_model = SQLQuery::getPreselectedSubModel($table->getModel()->getParentTable());
			if ($sub_model == null) {
				PNApplication::error("No sub model selected");
				return;
			}
		}
		if (!PNApplication::$instance->user_management->has_right($table->getCustomizationRight())) {
			PNApplication::error("You are not allowed to edit those information");
			return;
		}
		
		require_once("component/data_model/DataBaseLock.inc");
		$err = DataBaseLock::checkLock($input["lock_id"], $table->getSQLNameFor($sub_model), null, null);
		if ($err <> null) {
			PNApplication::error($err);
			return;
		}
		DataBaseLock::update($input["lock_id"]);
		
		// TODO check what exists, check no data entered yet
		
		$sql_name = $table->getSQLNameFor($sub_model);
		
		$data_path = realpath(dirname($_SERVER["SCRIPT_FILENAME"])."/data/".\PNApplication::$instance->current_domain);
		if (!file_exists($data_path."/custom_tables"))
			if (!mkdir($data_path."/custom_tables")) {
				PNApplication::error("Unable to create directory for custom tables in ".$data_path);
				return;
			}
		if (file_exists($data_path."/custom_tables/$sql_name.tmp")) unlink($data_path."/custom_tables/$sql_name.tmp");

		$f = fopen($data_path."/custom_tables/$sql_name.tmp","w");
		fwrite($f, "<?php\n");
		fwrite($f, "\$display = \$this->model->getTableDataDisplay(\"".$table->getName()."\");\n");
		fwrite($f, "\$columns = array();\n");
		$col_name_counter = 1;
		foreach ($input["columns"] as $col) {
			$col_name = "c".($col_name_counter++);
			switch ($col["type"]) {
				case "boolean":
					fwrite($f, "array_push(\$columns, new \datamodel\ColumnBoolean(\$this, \"$col_name\", ".($col["spec"]["can_be_null"] ? "true" : "false")."));\n");
					break;
			}
			fwrite($f, "\$display->addDataDisplay(new \datamodel\SimpleDataDisplay(\"$col_name\",\"".str_replace("\"","\\\"",$col["description"])."\"),null,".($sub_model <> null ? "\"$sub_model\"" : "null").");\n");
		}
		fwrite($f, "return \$columns;\n");
		fwrite($f, "?>");
		fclose($f);

		if (file_exists($data_path."/custom_tables/$sql_name"))
			unlink($data_path."/custom_tables/$sql_name");
		rename($data_path."/custom_tables/$sql_name.tmp", $data_path."/custom_tables/$sql_name");
		
		echo "true";
	}
	
}
?>