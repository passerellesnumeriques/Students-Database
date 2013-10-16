<?php 
global $www_dir, $generated_dir;
$www_dir = dirname(__FILE__)."/../../www";
$generated_dir = dirname(__FILE__)."/../../generated_doc";

require_once("FSUtils.inc");
require_once("Components.inc");

$menu_id = 0;
function generate_menu($menu, $component_name, &$header, &$nav, $nav_parent) {
	global $menu_id;
	foreach ($menu as $item) {
		$header .= "<a href='#".$item[1]."'>".$item[0]."</a>";
		$id = $menu_id++;
		$nav .= "\$menu_item_".$id." = new TreeNode(\"".$item[0]."\",\"component/".$component_name."/index.html#".$item[1]."\",false);";
		$nav .= $nav_parent."->add(\$menu_item_".$id.");";
		if (count($item) > 2 && count($item[2]) > 0) {
			$header .= "<div style='margin-left:10px'>";
			generate_menu($item[2], $component_name, $header, $nav, "\$menu_item_".$id);
			$header .= "</div>";
		} else {
			$header .= "<br/>";
		}
	}
}

FSUtils::write_file("component/index.html", "<img src='dependencies.png'/>");

$components = Components::order_components_by_dependency(Components::list_components());
foreach ($components as $name) {
	$path = $www_dir."/component/".$name;
	
	$header = "<!DOCTYPE html>";
	$header .= "<html>";
	$header .= "<head>";
	$header .= "<link rel='stylesheet' type='text/css' href='../../style.css'/>";
	$header .= "</head>";
	$header .= "<body>";
	
	$header .= "<h1>".$name."</h1>";
	$header .= "<div style='margin-left:10px'>";
	
	$html = "";
	$menu = array();
	
	if (file_exists($path."/doc/intro.html")) {
		$html .= "<a name='intro'><h2>Introduction</h2></a>";
		array_push($menu, array("Introduction", "intro"));
		$html .= "<div style='margin-left:10px'>";
		$html .= file_get_contents($path."/doc/intro.html");
		$html .= "</div>";
	}
	$deps = Components::get_dependencies($name);
	if (count($deps) > 0) {
		$html .= "<a name='dependencies'><h2>Dependencies</h2></a>";
		array_push($menu, array("Dependencies", "dependencies"));
		$html .= "<div style='margin-left:10px'>";
		$html .= "<img src='dependencies.png'/>";
		$html .= "</div>";
	}
	if (file_exists($path."/datamodel.inc")) {
		$html .= "<a name='datamodel'><h2>Data Model</h2></a>";
		array_push($menu, array("Data Model", "datamodel"));
		$html .= "<div style='margin-left:10px'>";
		$html .= "<img src='data_model.png'/>";
		$html .= "</div>";
	}
	
	$nav = "<?php global \$components_nav; \$comp = new TreeNode(\"".$name."\",\"component/".$name."/index.html\",false);";
	generate_menu($menu, $name, $header, $nav, "\$comp");
	$nav .= "\$components_nav->add(\$comp);?>";
	FSUtils::write_file("tmp/navigation/component_".$name, $nav);

	$html = $header.$html;
	$html .= "</div>";
	
	$html .= "</body>";
	$html .= "</html>";
	FSUtils::write_file("component/".$name."/index.html", $html);
}
?>