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
		$uml .= "hide circle\n";
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
		$dependencies = array();
		foreach ($model->internalGetTables() as $table) {
			if ($cname <> null && $table->owner_component->name <> $cname) continue;
			$uml .= "class ".$table->getName()." {\n";
			foreach ($table->internalGetColumns() as $col) {
				$type = "";
				if ($col instanceof datamodel\ColumnBoolean)
					$type = "boolean";
				else if ($col instanceof datamodel\ForeignKey) {
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
				} else if ($col instanceof datamodel\PrimaryKey)
					$type = "key";
				else if ($col instanceof datamodel\ColumnDate)
					$type = "date";
				else if ($col instanceof datamodel\ColumnDecimal)
					$type = "decimal";
				else if ($col instanceof datamodel\ColumnEnum)
					$type = "enum";
				else if ($col instanceof datamodel\ColumnInteger)
					$type = "integer";
				else if ($col instanceof datamodel\ColumnString)
					$type = "string";
				else if ($col instanceof datamodel\ColumnTime)
					$type = "time";
				$uml .= "-".$col->name." : ".$type."\n";
			}
			$uml .= "}\n";
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
			foreach ($table->internalGetColumns() as $col) {
				if (!($col instanceof datamodel\ForeignKey)) continue;
				$uml .= $table->getName()." --> ".$col->foreign_table."\n";
			}
		}
	}
		
}
?>