<?php 
require_once("data_screen.inc");
function createDataPage(&$page, $root_table_name, $sub_model, $values, $prefilled_values) {
	$structure_name = "";
	echo newDataScreen($page, $root_table_name, $sub_model, $values, $prefilled_values, $structure_name);
	return $structure_name;
}
?>