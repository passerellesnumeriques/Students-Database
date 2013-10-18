<?php 
global $www_dir, $generated_dir;
$www_dir = dirname(__FILE__)."/../../www";
$generated_dir = dirname(__FILE__)."/../../generated_doc";

require_once("CommandsExecution.inc");
global $cmds;
$cmds = new CommandsExecution();

function generate_uml($uml, $filename) {
	global $generated_dir, $cmds;
	FSUtils::write_file($filename.".uml", "@startuml\n".$uml."@enduml\n");
	$dir = dirname(__FILE__)."/../tools";
	$cmds->add_command("java.exe -jar ".$dir."/plantuml.jar -graphvizdot ".$dir."/graphviz_2.28/bin/dot.exe"." ".$generated_dir."/".$filename.".uml");
}

require_once("FSUtils.inc");
require_once("Components.inc");
$components = Components::order_components_by_dependency(Components::list_components());
$datamodel_uml = "";
$dependencies_uml = "";
$dependencies_uml .= "hide members\n";
$dependencies_uml .= "hide circle\n";
foreach ($components as $name) {
	$path = $www_dir."/component/".$name;
	$dependencies_uml .= "class ".$name."\n";
	$deps = Components::get_dependencies($name);
	if (count($deps) > 0) {
		$uml = "class ".$name."\n";
		foreach ($deps as $dep_name=>$dep_doc) {
			$uml .= $dep_name." <-- ".$name;
			$dependencies_uml .= $dep_name." <-- ".$name;
			$dep_doc = trim($dep_doc);
			if (strlen($dep_doc) > 0) {
				$uml .= " : ".$dep_doc;
				$dependencies_uml .= " : ".$dep_doc;
			}
			$uml .= "\n";
			$dependencies_uml .= "\n";
		}
		$uml .= "hide members\n";
		$uml .= "hide circle\n";
		generate_uml($uml, "component/".$name."/dependencies");
	}
	if (file_exists($path."/datamodel.inc")) {
		require_once("DataModel.inc");
		$model = new DataModel();
		include $path."/datamodel.inc";
	
		$uml = "";
		foreach ($model->tables as $table) {
			$table_uml = "class ".$table->name." {\n";
			foreach ($table->columns as $col) {
				if (isset($table->displayable_data[$col->name]))
					$table_uml .= "+"; // public
				else
					$table_uml .= "-"; // private
				$table_uml .= $col->name." : ".$col->get_type()."\n";
			}
			$table_uml .= "}\n";
			foreach ($table->columns as $col) {
				if ($col instanceof ForeignKey) {
					$table_uml .= $table->name." --> ".$col->foreign_table."\n";
				}
			}
			$uml .= $table_uml;
			$datamodel_uml .= "package ".$name."\n";
			$datamodel_uml .= $table_uml;
			$datamodel_uml .= "end package\n";
		}
		$uml .= "hide methods\n";
	
		generate_uml($uml, "component/".$name."/data_model");
	}
}
$datamodel_uml .= "hide methods\n";
generate_uml($datamodel_uml, "general/data_model");
generate_uml($dependencies_uml, "component/dependencies");

$cmds->launch_execution("uml");
?>