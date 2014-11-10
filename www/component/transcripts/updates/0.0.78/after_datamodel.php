<?php
$db = SQLQuery::getDataBaseAccessWithoutSecurity(); 
$db->execute("UPDATE `TranscriptConfig` SET `class_average`=0, `batch_average`=1 WHERE `class_average`=1");
$db->execute("UPDATE `PublishedTranscript` SET `class_average`=0, `batch_average`=1 WHERE `class_average`=1");
?>