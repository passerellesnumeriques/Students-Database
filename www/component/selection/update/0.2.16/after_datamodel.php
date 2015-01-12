<?php
$campaigns = SQLQuery::create()->select("SelectionCampaign")->field("id")->executeSingleField();
foreach ($campaigns as $cid) {
	require_once 'component/data_model/DataBaseUtilities.inc';
	$table = DataModel::get()->internalGetTable("SelectionProgram");
	DataBaseUtilities::createTable(SQLQuery::getDataBaseAccessWithoutSecurity(), $table, "_".$cid);
} 
?>