<?php 
class service_datamodel extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Generate the image of the required datamodel"; }
	public function inputDocumentation() { echo "<code>component</code>: component name, or <i>all</i> for the global data model"; }
	public function outputDocumentation() { echo "The image of the data model"; }
	
	public function getOutputFormat($input) { return "image/png"; }
	
	public function execute(&$component, $input) {
		$name = $_GET["component"];

		$model = $this->load_model();
		
		$uml = "@startuml\n";
		if ($name == "all") {
			foreach (PNApplication::$instance->components as $c) {
				if (!file_exists("component/".$c->name."/datamodel.inc")) continue;
				$uml .= "package ".$c->name." {\n";
				$this->generate_uml($model, $c->name, $uml, false);
				$uml .= "}\n";
			}
		} else {
			$this->generate_uml($model, $name, $uml, true);
		}
		$uml .= "hide methods\n";
		$uml .= "hide class circle\n";
		$uml .= "@enduml\n";
		$base_filename = tempnam(sys_get_temp_dir(), "pn");
		unlink($base_filename);
		$filename = $base_filename.".uml";
		$f = fopen($filename, "w");
		fwrite($f, $uml);
		fclose($f);
		set_time_limit(120);
		$tools_path = realpath("component/documentation/tools");
		session_write_close();
		exec("java.exe -jar \"$tools_path/plantuml.jar\" -graphvizdot \"$tools_path/graphviz_2.28/bin/dot.exe\" \"$filename\"");
		unlink($filename);
		$filename = $base_filename.".png";
		readfile($filename);
		unlink($filename);
	}
	
	private function load_model() {
		require_once("component/data_model/Model.inc");
		$model = new DataModel();
		$done = array();
		foreach (PNApplication::$instance->components as $c)
			$this->load_component_datamodel($c, $done, $model);
		return $model;
	}
	private function load_component_datamodel($c, &$done, &$model) {
		if (in_array($c->name, $done)) return;
		array_push($done, $c->name);
		foreach ($c->dependencies() as $dep)
			$this->load_component_datamodel(PNApplication::$instance->components[$dep], $done, $model);
		$file = "component/".$c->name."/datamodel.inc";
		if (file_exists($file))
			include $file;
		$this->put_component_in_tables($model, $c);
	}
	private function put_component_in_tables(&$model, $c) {
		foreach ($model->internalGetTables() as $table)
			if (!isset($table->{"owner_component"}))
				$table->{"owner_component"} = $c;
		foreach ($model->getSubModels() as $sm)
			$this->put_component_in_tables($sm, $c);
	}
	
	private function generate_uml($model, $cname, &$uml, $add_dependencies) {
		$pseudo_count = 1;
		$links = array();
		$dependencies = array();
		foreach ($model->internalGetTables() as $table) {
			if ($cname <> null && $table->owner_component->name <> $cname) continue;
			$cols = $table->internalGetColumns();
			// check if this is a joining table
			$is_join = false;
			if (count($cols) == 2 && 
				$cols[0] instanceof datamodel\ForeignKey &&
				$cols[1] instanceof datamodel\ForeignKey) {
				$is_join = true;
			} else {
				$pseudo_classes = array();
				$uml .= "class ".$table->getName()." {\n";
				foreach ($cols as $col) {
					$type = "";
					$visibility = "-";
					if ($col instanceof datamodel\ColumnBoolean)
						$type = "boolean";
					else if ($col instanceof datamodel\ForeignKey) {
						$visibility = "~";
						$type = $col->foreign_table;
						if ($add_dependencies) {
							$ft = $model->internalGetTable($col->foreign_table);
							if ($ft->owner_component->name <> $cname) {
								if (!isset($dependencies[$ft->owner_component->name]))
									$dependencies[$ft->owner_component->name] = array();
								if (!in_array($col->foreign_table, $dependencies[$ft->owner_component->name]))
									array_push($dependencies[$ft->owner_component->name], $col->foreign_table);							
							}
						}
					} else if ($col instanceof datamodel\PrimaryKey) {
						$type = "key";
						$visibility = "+";
					} else if ($col instanceof datamodel\ColumnDate)
						$type = "date";
					else if ($col instanceof datamodel\ColumnDecimal)
						$type = "decimal";
					else if ($col instanceof datamodel\ColumnEnum) {
						$type = "enum";
						$pseudo = "enum \"".$table->getName().".".$col->name."\" as pseudo".$pseudo_count." {\n";
						foreach ($col->values as $val)
							$pseudo .= "\t\"".$val."\"\n";
						$pseudo .= "}\n";
						$pseudo_classes["pseudo".$pseudo_count] = $pseudo;
						$pseudo_count++;
					} else if ($col instanceof datamodel\ColumnInteger) {
						$type = "integer";
						if ($col->min <> null || $col->max <> null){
							$type .= " [";
							if ($col->min <> null)
								$type .= $col->min;
							else $type .= "*";
							$type .= "..";
							if ($col->max <> null)
								$type .= $col->max;
							else $type .= "*";
							$type .= "]";
						}
					} else if ($col instanceof datamodel\ColumnString) {
						$type = "string [".$col->min_length."..".$col->max_length."]";
					} else if ($col instanceof datamodel\ColumnTime)
						$type = "time";
					
					$uml .= $visibility;
					
					$pk = $table->getPrimaryKey();
					if ($pk <> null) {
						if ($pk == $col) $uml .= "{static} ";
					} else {
						$key_cols = $table->getKey();
						if ($key_cols <> null && in_array($col->name, $key_cols))
							$uml .= "{static} ";
					}
					
					if ($col->can_be_null) $uml .= "{abstract} ";
					
					$uml .= $col->name." : ".$type."\n";
				}
				$uml .= "}\n";
				foreach ($pseudo_classes as $pn=>$pc) {
					$uml .= $pc;
					$uml .= $table->getName()." .. ".$pn."\n";
				}
				foreach ($cols as $col) {
					if (!($col instanceof datamodel\ForeignKey)) continue;
					array_push($links, $table->getName()." --> ".$col->foreign_table);
				}
			}
			if ($add_dependencies && $is_join) {
				$ft = $model->internalGetTable($cols[0]->foreign_table);
				if ($ft->owner_component->name <> $cname) {
					if (!isset($dependencies[$ft->owner_component->name]))
						$dependencies[$ft->owner_component->name] = array();
					if (!in_array($cols[0]->foreign_table, $dependencies[$ft->owner_component->name]))
						array_push($dependencies[$ft->owner_component->name], $cols[0]->foreign_table);
				}
				$ft = $model->internalGetTable($cols[1]->foreign_table);
				if ($ft->owner_component->name <> $cname) {
					if (!isset($dependencies[$ft->owner_component->name]))
						$dependencies[$ft->owner_component->name] = array();
					if (!in_array($cols[1]->foreign_table, $dependencies[$ft->owner_component->name]))
						array_push($dependencies[$ft->owner_component->name], $cols[1]->foreign_table);
				}
			}
			if ($is_join) {
				if ($cols[0]->multiple) {
					if ($cols[1]->multiple)
						$rel = "\"*\" - \"*\"";
					else
						$rel = "\"1\" - \"*\"";
				} else {
					if ($cols[1]->multiple)
						$rel = "\"*\" - \"1\"";
					else
						$rel = "\"1\" - \"1\"";
				}
				array_push($links, $cols[0]->foreign_table." ".$rel." ".$cols[1]->foreign_table);
				array_push($links, "(".$cols[0]->foreign_table.", ".$cols[1]->foreign_table.") .. ".$table->getName());
				$uml .= "class ".$table->getName()." {\n";
				$uml .= "~".$cols[0]->name." : ".$cols[0]->foreign_table."\n";
				$uml .= "~".$cols[1]->name." : ".$cols[1]->foreign_table."\n";
				$uml .= "}\n";
			}
		}
		if (count($dependencies) > 0) {
			foreach ($dependencies as $comp=>$list) {
				$uml .= "package ".$comp."{\n";
				foreach ($list as $cl) {
					$uml .= "class ".$cl." {\n}\n";
					$uml .= "hide ".$cl." members\n";
				}
				$uml .= "}\n";
			}
		}
		foreach ($links as $link)
			$uml .= $link."\n";
	}
		
}
?>