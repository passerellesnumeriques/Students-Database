<?php 
// create entries in table PublishedTranscriptSubject
$transcripts = SQLQuery::create()->bypassSecurity()->select("PublishedTranscript")->execute();
$to_insert = array();
foreach ($transcripts as $transcript) {
	$subjects_ids = SQLQuery::create()->bypassSecurity()->select("TranscriptSubjects")->whereValue("TranscriptSubjects","period",$transcript["period"])->whereValue("TranscriptSubjects","specialization",$transcript["specialization"])->field("subject")->executeSingleField();
	if (count($subjects_ids) > 0)
		$subjects_info = SQLQuery::create()->bypassSecurity()->select("CurriculumSubjectGrading")->whereIn("CurriculumSubjectGrading","subject",$subjects_ids)->execute();
	foreach ($subjects_ids as $subject_id) {
		$info = null;
		foreach ($subjects_info as $si) if ($si["subject"] == $subject_id) { $info = $si; break; }
		if ($info == null || !isset($info["max_grade"]) || !isset($info["passing_grade"])) continue;
		array_push($to_insert, array(
			"transcript"=>$transcript["id"],
			"subject"=>$subject_id,
			"max_grade"=>$info["max_grade"],
			"passing_grade"=>$info["passing_grade"]
		));
	}
}
if (count($to_insert) > 0)
	SQLQuery::create()->bypassSecurity()->insertMultiple("PublishedTranscriptSubject", $to_insert);
?>