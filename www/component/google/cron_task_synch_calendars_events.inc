<?php 
$google_ids = SQLQuery::create()->bypassSecurity()->select("PNGoogleCalendar")->field("google_id")->executeSingleField();
$google_ids2 = SQLQuery::create()->bypassSecurity()->select("PNCustomGoogleCalendar")->field("google_id")->executeSingleField();
$all_google_ids = array_merge($google_ids, $google_ids2);
// remove the ones which do not exist anymore
$removed = SQLQuery::create()->bypassSecurity()->select("GoogleCalendarSynchro")->whereNotIn("GoogleCalendarSynchro","google_id",$all_google_ids)->field("google_id")->executeSingleField();
if (count($removed) > 0)
	SQLQuery::create()->bypassSecurity()->removeKeys("GoogleCalendarSynchro", $removed);
// get missing ones
$already = SQLQuery::create()->bypassSecurity()->select("GoogleCalendarSynchro")->field("google_id")->executeSingleField();
$missing = array();
foreach ($all_google_ids as $id) if (!in_array($id, $already)) array_push($missing, $id);
if (count($missing) > 0) {
	// TODO
}
// select the 5 oldest, or 10% but limited to 50
$max = count($all_google_ids) > 50 ? floor(count($all_google_ids)/10) : 5;
if ($max > 50) $max = 50;
// count the missing ones
$max -= count($missing);
if ($max > 0) {
	// get the ones to update (removing the ones already synchronized less than 5 minutes ago)
	$ids = SQLQuery::create()->bypassSecurity()->select("GoogleCalendarSynchro")->where("`timestamp` < ".(time()-5*60))->orderBy("GoogleCalendarSynchro","timestamp",true)->limit(0,$max)->field("google_id")->executeSingleField();
	if (count($ids) > 0) {
		// TODO
	}
}
?>