<?php 
require_once("data_screen.inc");
function createDataPage(&$page, $root_table_name, $sub_model, $sub_models, $values, $prefilled_values) {
	$structure_name = "";
	$container_id = $page->generateID();
	echo "<div id='$container_id' style='width:100%;height:100%'>";
	echo newDataScreen($page, $root_table_name, $sub_model, $sub_models, $values, $prefilled_values, $structure_name);
	echo "</div>";
	$page->requireJavascript("boxes_layout.js");
	echo "<script type='text/javascript'>boxes_layout('$container_id',5);</script>";
	return $structure_name;
}
?>