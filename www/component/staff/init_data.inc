<?php 
PNApplication::$instance->user_management->create_role("Staff", true, true);
SQLQuery::create()->bypassSecurity()->insertMultiple("PNDepartment", array(
	array("name"=>"Admin - Finance - HR"),
	array("name"=>"Education"),
	array("name"=>"External Relations"),
	array("name"=>"IT Admin"),
	array("name"=>"Management"),
	array("name"=>"Selection"),
	array("name"=>"Training"),
	array("name"=>"Volunteers"),
));
?>