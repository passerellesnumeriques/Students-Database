<?php
$res = SQLQuery::getDataBaseAccessWithoutSecurity()->execute("SELECT `id` FROM `SelectionCampaign`");
while (($row = SQLQuery::getDataBaseAccessWithoutSecurity()->nextRow($res)) <> null) {
	$cid = $row["id"];
	require_once 'component/data_model/DataBaseUtilities.inc';
	$table = DataModel::get()->internalGetTable("SelectionProgram");
	DataBaseUtilities::createTable(SQLQuery::getDataBaseAccessWithoutSecurity(), $table, "_".$cid);
} 
?>