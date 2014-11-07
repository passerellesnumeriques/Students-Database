<?php 
$db = SQLQuery::getDataBaseAccessWithoutSecurity();
$migration_path = dirname(__FILE__);
// get list of AcademicClass, and create corresponding StudentsGroup, keeping the same id
$f = fopen($migration_path."/classes.sql","w");
$res = $db->execute("SELECT * FROM `AcademicClass`");
while (($row = $db->nextRow($res)) <> null) {
	fwrite($f, "INSERT INTO `StudentsGroup` (`id`,`type`,`parent`,`period`,`name`,`specialization`) VALUE (".$row["id"].",1,NULL,".$row["period"].",'".SQLQuery::escape($row["name"])."',".($row["specialization"] == null ? "NULL" : $row["specialization"]).")\n");
}
fclose($f);
// for each StudentClass, create a StudentGroup
$f = fopen($migration_path."/student_classes.sql","w");
$res = $db->execute("SELECT * FROM `StudentClass`");
while (($row = $db->nextRow($res)) <> null) {
	fwrite($f, "INSERT INTO `StudentGroup` (`people`,`group`) VALUE (".$row["people"].",".$row["class"].")\n");
}
fclose($f);
// SubjectClassMerge and TeacherAssignment
$sql = "";
$res = $db->execute("SELECT * FROM `SubjectClassMerge`");
$merges = array();
while (($row = $db->nextRow($res)) <> null) array_push($merges, $row);
$subject_teaching = array();
for ($i = 0; $i < count($merges); $i++) {
	$m = $merges[$i];
	$classes = array($m["class1"], $m["class2"]);
	for ($j = $i+1; $j < count($merges); $j++) {
		$m2 = $merges[$j];
		if ($m2["subject"] <> $m["subject"]) continue;
		if (in_array($m2["class1"], $classes)) {
			array_push($classes, $m2["class2"]);
			array_splice($merges, $j, 1);
			$j--;
		} else if (in_array($m2["class2"], $classes)) {
			array_push($classes, $m2["class1"]);
			array_splice($merges, $j, 1);
			$j--;
		}
	}
	array_push($subject_teaching, array("subject"=>$m["subject"],"classes"=>$classes));
	$sql .= "INSERT INTO `SubjectTeaching` (`id`,`subject`) VALUE (".count($subject_teaching).",".$m["subject"].")\n";
	foreach ($classes as $cl)
		$sql .= "INSERT INTO `SubjectTeachingGroups` (`subject_teaching`,`group`) VALUE (".count($subject_teaching).",".$cl.")\n";
}
$res = $db->execute("SELECT * FROM `TeacherAssignment`");
$ta_list = array();
while (($row = $db->nextRow($res)) <> null) array_push($ta_list, $row);
foreach ($ta_list as $ta) {
	$subject_teaching_id = -1;
	for ($i = 0; $i < count($subject_teaching); $i++) {
		$st = $subject_teaching[$i];
		if ($st["subject"] <> $ta["subject"]) continue;
		if (!in_array($ta["class"], $st["classes"])) continue;
		$subject_teaching_id = $i+1;
		break;
	}
	if ($subject_teaching_id == -1) {
		array_push($subject_teaching, array("subject"=>$ta["subject"],"classes"=>array($ta["class"])));
		$subject_teaching_id = count($subject_teaching);
		$sql .= "INSERT INTO `SubjectTeaching` (`id`,`subject`) VALUE (".count($subject_teaching).",".$ta["subject"].")\n";
		$sql .= "INSERT INTO `SubjectTeachingGroups` (`subject_teaching`,`group`) VALUE (".count($subject_teaching).",".$ta["class"].")\n";
	}
	$sql .= "INSERT INTO `TeacherAssignment` (`subject_teaching`,`people`,`hours`,`hours_type`) VALUE (".$subject_teaching_id.",".$ta["people"].",".($ta["hours"] == null ? "NULL" : $ta["hours"]).",".($ta["hours_type"] == null ? "NULL" : "'".$ta["hours_type"]."'").")\n";
}
$f = fopen($migration_path."/subject_teaching.sql","w");
fwrite($f, $sql);
fclose($f);
$db->execute("TRUNCATE TABLE `TeacherAssignment`");
?>