<?php 
class service_save_custom_table extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Customize a table"; }
	public function inputDocumentation() { echo "table, sub_model, columns and lock_id"; }
	public function outputDocumentation() { echo "list of columns' names"; }
	
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
		
		require_once("component/data_model/DataModelCustomizationPlugin.inc");
		/* @var $plugins DataModelCustomizationPlugin[] */
		$plugins = array();
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi)
				if ($pi instanceof DataModelCustomizationPlugin)
					array_push($plugins, $pi);

		$col_name_counter = 1;
		// get the current list of columns in the table
		$columns = $table->internalGetColumnsFor($sub_model);
		// filter non-custom columns
		for ($i = 0; $i < count($columns); $i++) {
			$name = $columns[$i]->name;
			if (substr($name,0,1) <> "c" || intval(substr($name,1) <= 0)) {
				array_splice($columns, $i, 1);
				$i--;
			} else {
				$id = intval(substr($name,1));
				if ($col_name_counter <= $id) $col_name_counter = $id+1;
			}
		}
		
		$sql_name = $table->getSQLNameFor($sub_model);
		
		$data_path = realpath(dirname(__FILE__)."/../../../data/".\PNApplication::$instance->current_domain);
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
		foreach ($input["columns"] as &$col) {
			if ($col["id"] == null) {
				$col["id"] = $col_name = "c".($col_name_counter++);
				$col["is_new"] = true;
			} else {
				$col_name = $col["id"];
				$col["is_new"] = false;
			}
			$custom = null;
			$custom_include = null;
			switch ($col["type"]) {
				case "boolean":
					fwrite($f, "array_push(\$columns, new \datamodel\ColumnBoolean(\$this, \"$col_name\", ".($col["spec"]["can_be_null"] ? "true" : "false")."));\n");
					break;
				case "string":
					fwrite($f, "array_push(\$columns, new \datamodel\ColumnString(\$this, \"$col_name\", ".($col["spec"]["max_length"] == null ? "null" : $col["spec"]["max_length"]).", null, ".($col["spec"]["can_be_null"] ? "true" : "false")."));\n");
					break;
				case "integer":
					$min = $col["spec"]["min"] == null ? "null" : intval($col["spec"]["min"]);
					$max = $col["spec"]["max"] == null ? "null" : intval($col["spec"]["max"]);
					$size = 32; // default size
					// TODO calculate the best size based on min and max
					fwrite($f, "array_push(\$columns, new \datamodel\ColumnInteger(\$this, \"$col_name\", $size, $min, $max, ".($col["spec"]["can_be_null"] ? "true" : "false")."));\n");
					break;
				case "decimal":
					$decimal_digits = intval($col["spec"]["decimal_digits"]);
					$min = $col["spec"]["min"] == null ? "null" : intval($col["spec"]["min"]);
					$max = $col["spec"]["max"] == null ? "null" : intval($col["spec"]["max"]);
					$integer_digits = 10; // default
					// TODO calculate integer digits based on min and max 
					fwrite($f, "array_push(\$columns, new \datamodel\ColumnDecimal(\$this, \"$col_name\", $integer_digits, $decimal_digits, $min, $max, ".($col["spec"]["can_be_null"] ? "true" : "false")."));\n");
					break;
				case "date":
					$min = $col["spec"]["min"] == null ? "null" : "\"".$col["spec"]["min"]."\"";
					$max = $col["spec"]["max"] == null ? "null" : "\"".$col["spec"]["max"]."\"";
					fwrite($f, "array_push(\$columns, new \datamodel\ColumnDate(\$this, \"$col_name\", ".($col["spec"]["can_be_null"] ? "true" : "false").", false, $min, $max));\n");
					break;
				case "enum":
					$values = $col["spec"]["values"];
					fwrite($f, "array_push(\$columns, new \datamodel\ColumnEnum(\$this, \"$col_name\", array(");
					for ($i = 0; $i < count($values); $i++) {
						if ($i > 0) fwrite($f, ",");
						fwrite($f, "\"".str_replace('"', '\\"', str_replace("\\","\\\\", $values[$i]))."\"");
					}
					fwrite($f, "), ".($col["spec"]["can_be_null"] ? "true" : "false").",false));\n");
					break;
				default:
					$pi = null;
					foreach ($plugins as $p) if ($p->getId() == $col["type"]) { $pi = $p; break; }
					if ($pi <> null) {
						fwrite($f, "array_push(\$columns, new \datamodel\ForeignKey(\$this, \"$col_name\", \"".$pi->getForeignTable()."\", true, false, true, ".($col["spec"]["can_be_null"] ? "true" : "false").", false));\n");
						$custom = $pi->getDataDisplay($col_name, $col["description"], $sub_model, $col["spec"]["can_be_null"]);
						$custom_include = $pi->getDataDisplayFileToInclude();
					}
					break;
			}
			if ($custom == null)
				fwrite($f, "\$display->addDataDisplay(new \datamodel\SimpleDataDisplay(\"$col_name\",\"".str_replace("\"","\\\"",$col["description"])."\"),null,".($sub_model <> null ? "\"$sub_model\"" : "null").");\n");
			else {
				if ($custom_include <> null) fwrite($f, "require_once(".json_encode($custom_include).");\n");
				fwrite($f, "\$display->addDataDisplay($custom);\n");
			}
		}
		fwrite($f, "return \$columns;\n");
		fwrite($f, "?>");
		fclose($f);

		if (file_exists($data_path."/custom_tables/$sql_name"))
			unlink($data_path."/custom_tables/$sql_name");
		rename($data_path."/custom_tables/$sql_name.tmp", $data_path."/custom_tables/$sql_name");
		
		// read the file
		$table->forceReloadCustomization($sub_model);
		
		// update database structure
		foreach ($input["columns"] as &$col) {
			$updated_col = $table->internalGetColumnFor($col["id"], $sub_model);
			if ($updated_col == null) throw new Exception("Internal error: Column ".$col["id"]." missing after loading customization");
			if ($col["is_new"]) {
				// this is a new column
				SQLQuery::getDataBaseAccessWithoutSecurity()->execute("ALTER TABLE `$sql_name` ADD COLUMN ".$updated_col->get_sql(SQLQuery::getDataBaseAccessWithoutSecurity(), $sql_name));
			} else {
				// this is an update
				SQLQuery::getDataBaseAccessWithoutSecurity()->execute("ALTER TABLE `$sql_name` MODIFY COLUMN ".$updated_col->get_sql(SQLQuery::getDataBaseAccessWithoutSecurity(), $sql_name));
				// TODO check data with new constraints
			}
			// remove it from the list of previous columns, so it won't be removed at the end
			for ($i = 0; $i < count($columns); $i++)
				if ($columns[$i]->name == $col["id"]) {
					array_splice($columns, $i, 1);
					$i--;
				}
		}
		unset($col);
		// remove remaining columns
		foreach ($columns as $col) {
			SQLQuery::getDataBaseAccessWithoutSecurity()->execute("ALTER TABLE `$sql_name` DROP COLUMN `".$col->name."`");
		}
		
		echo "[";
		$first = true;
		foreach ($input["columns"] as &$col) {
			if ($first) $first = false; else echo ",";
			echo json_encode($col["id"]);
		}
		echo "]";
	}
	
}
?>