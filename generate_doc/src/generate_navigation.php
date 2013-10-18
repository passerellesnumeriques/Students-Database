<?php 
global $www_dir, $generated_dir;
$www_dir = dirname(__FILE__)."/../../www";
$generated_dir = dirname(__FILE__)."/../../generated_doc";

require_once("Components.inc");
require_once("FSUtils.inc");

class TreeNode {
	
	public $title;
	public $link;
	public $expanded;
	public $children = array();
	
	public function __construct($title, $link, $expanded) {
		$this->title = $title;
		$this->link = $link;
		$this->expanded = $expanded;
	}
	
	public function add($child) {
		array_push($this->children, $child);
	}
	
}

$menu = array();
global $general;
include $generated_dir."/tmp/navigation/general";
array_push($menu, $general);
global $components_nav;
$components_nav = new TreeNode("Components", "component/index.html", true);
array_push($menu, $components_nav);
$components = Components::list_components();
foreach ($components as $name) {
	include $generated_dir."/tmp/navigation/component_".$name;
}

global $nav_id;
$nav_id = 0;
function generate_nav($items, &$html) {
	global $nav_id;
	foreach ($items as $node) {
		$id = $nav_id++;
		$html .= "<div>";
		if (count($node->children) > 0)
			$html .= "<img src='tree_".($node->expanded ? "collapse" : "expand").".png' style='vertical-align:bottom;cursor:pointer' onclick='nav_tree(this,$id)'>";
		else
			$html .= "<img src='list_circle.gif' style='vertical-align:bottom'>";
		if ($node->link <> null)
			$html .= "<a href='".$node->link."' target='content'>";
		$html .= $node->title;
		if ($node->link <> null)
			$html .= "</a>";
		$html .= "</div>";
		if (count($node->children) > 0) {
			$html .= "<div id='nav_$id' style='padding-left:15px;".($node->expanded ? "visibility:visible" : "visibility:hidden;position:absolute;top:-10000px")."'>";
			generate_nav($node->children, $html);
			$html .= "</div>";
		}
	}	
}

$html = "<!DOCTYPE html><html><head><link rel='stylesheet' type='text/css' href='style.css'/></head><body>";
$html .= "<div class='navigation'>";
generate_nav($menu, $html);
$html .= "</div>";
$html .= "<script type='text/javascript'>";
$html .= "function nav_tree(img,id) {";
$html .= "	var div = document.all ? document.all['nav_'+id] : document.getElementById('nav_'+id);";
$html .= "	if (div.style.visibility == 'visible') {";
$html .= "		div.style.visibility = 'hidden';";
$html .= "		div.style.position = 'absolute';";
$html .= "		div.style.top = '-10000px';";
$html .= "		img.src = 'tree_expand.png';";
$html .= "	} else {";
$html .= "		div.style.visibility = 'visible';";
$html .= "		div.style.position = 'static';";
$html .= "		img.src = 'tree_collapse.png';";
$html .= "	}";
$html .= "}";
$html .= "</script>";
$html .= "</body>";
$html .= "</html>";
FSUtils::write_file("navigation.html", $html);
?>