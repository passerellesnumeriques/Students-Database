<?php 
// replace the users' id by their people id

// 1-get all DocumentVersion with their people id
$res = SQLQuery::getDataBaseAccessWithoutSecurity()->execute("SELECT DocumentVersion.id AS doc_id, UserPeople.people AS people_id FROM DocumentVersion LEFT JOIN UserPeople ON UserPeople.user = DocumentVersion.user");
$docs = SQLQuery::getDataBaseAccessWithoutSecurity()->fetchRows($res);

// 2-replace user id by people id
foreach ($docs as $doc) {
	$people_id = $doc["people_id"];
	// handle problem for PNP
	if ($people_id == null && PNApplication::$instance->local_domain == "PNP")
		$people_id = 2; // the only one which has been removed
	// replace user id by people id
	SQLQuery::getDataBaseAccessWithoutSecurity()->execute("UPDATE DocumentVersion SET user = ".($people_id <> null ? $people_id : "NULL")." WHERE id = ".$doc["doc_id"]);
}
?>