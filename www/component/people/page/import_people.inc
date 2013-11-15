<?php 
function import_people(&$page, $icon, $title, $data_list, $fixed_data, $prefilled_data, $create_button, $create_function) {
	require_once("component/data_model/Model.inc");
	$people = DataModel::get()->getTable("People");
	$display = $people->getDisplayHandler(null);
	$data_list = array_merge($data_list, $display->getDisplayableData());
	foreach (PNApplication::$instance->components as $c) {
		if (!($c instanceof PeoplePlugin)) continue;
		$data_list = array_merge($data_list, $c->getPeopleImportDataDisplay());
	}
	require_once("component/data_import/page/import_data.inc");
	import_data($page, $icon, $title, $data_list, $fixed_data, $prefilled_data, $create_button, $create_function);
}
?>