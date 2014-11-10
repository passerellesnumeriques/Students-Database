<?php 
$db = SQLQuery::getDataBaseAccessWithoutSecurity();
$migration_path = dirname(__FILE__);
// create group type 'class'
$db->execute("INSERT INTO `StudentsGroupType` (`id`,`name`,`specialization_dependent`,`builtin`) VALUE (1,'Class',1,1)");
// create StudentsGroup from AcademicClass
$sql_file = file_get_contents($migration_path."/classes.sql");
$lines = explode("\n",$sql_file);
foreach ($lines as $line) if (trim($line) <> "") $db->execute($line);
// create StudentGroup from StudentClass
$sql_file = file_get_contents($migration_path."/student_classes.sql");
$lines = explode("\n",$sql_file);
foreach ($lines as $line) if (trim($line) <> "") $db->execute($line);
// subject teaching
$sql_file = file_get_contents($migration_path."/subject_teaching.sql");
$lines = explode("\n",$sql_file);
foreach ($lines as $line) if (trim($line) <> "") $db->execute($line);
// update news tag 'class'
$res = $db->execute("SELECT `id`,`tags` FROM `News` WHERE `tags` LIKE '%/class%'");
while (($row = $db->nextRow($res)) <> null) {
	$s = $row["tags"];
	$i = strpos($s, "/class");
	if ($i == 0) $s = "/group".substr($s,6);
	else $s = substr($s,0,$i)."/group".substr($s,$i+6);
	$db->execute("UPDATE `News` SET `tags`='".$db->escapeString($s)."' WHERE `id`=".$row["id"]);
}
?>