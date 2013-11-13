<?php 
function import_people(&$page, $icon, $title, $data_list) {
	require_once("component/data_model/Model.inc");
	$people = DataModel::get()->getTable("People");
	$display = $people->getDisplayHandler(null);
	foreach ($display->getDisplayableData() as $data)
		array_push($data_list, $data);
	require_once("component/data_import/page/import_data.inc");
	import_data($page, $icon, $title, $data_list);
}
?>