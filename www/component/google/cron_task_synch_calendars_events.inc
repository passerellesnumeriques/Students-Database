<?php 
$calendars = SQLQuery::create()->bypassSecurity()->select("PNGoogleCalendar")->execute();
$google_ids = array();
foreach ($calendars as $c) array_push($google_ids, $c["google_id"]);
// remove the ones which do not exist anymore
$removed = SQLQuery::create()->bypassSecurity()->select("GoogleCalendarSynchro")->whereNotIn("GoogleCalendarSynchro","google_id",$google_ids)->field("google_id")->executeSingleField();
if (count($removed) > 0)
	SQLQuery::create()->bypassSecurity()->removeKeys("GoogleCalendarSynchro", $removed);
// get missing ones
$already = SQLQuery::create()->bypassSecurity()->select("GoogleCalendarSynchro")->field("google_id")->executeSingleField();
$missing = array();
foreach ($calendars as $c) if (!in_array($c["google_id"], $already)) array_push($missing, $c);
if (count($missing) > 0) {
	require_once("component/google/lib_api/PNGoogleCalendar.inc");
	$gcal = new PNGoogleCalendar();
	foreach ($missing as $c) {
		$time = time();
		if ($gcal->synchCalendar($c["pn_id"], $c["google_id"], 0))
			SQLQuery::create()->bypassSecurity()->insert("GoogleCalendarSynchro", array("google_id"=>$c["google_id"], "timestamp"=>$time));
	}
}
// select the 5 oldest, or 10% but limited to 50
$max = count($calendars) > 50 ? floor(count($calendars)/10) : 5;
if ($max > 50) $max = 50;
// count the missing ones
$max -= count($missing);
if ($max > 0) {
	// get the ones to update (removing the ones already synchronized less than 5 minutes ago)
	$to_synch = SQLQuery::create()->bypassSecurity()->select("GoogleCalendarSynchro")->where("`timestamp` < ".(time()-5*60))->orderBy("GoogleCalendarSynchro","timestamp",true)->limit(0,$max)->execute();
	if (count($to_synch) > 0) {
		if (!isset($gcal)) {
			require_once("component/google/lib_api/PNGoogleCalendar.inc");
			$gcal = new PNGoogleCalendar();
		}
		foreach ($to_synch as $cs) {
			$google_id = $cs["google_id"];
			$pn_id = null;
			foreach ($calendars as $c) if ($c["google_id"] == $google_id) { $pn_id = $c["pn_id"]; break; }
			if ($pn_id == null) continue; // ?
			$time = time();
			if ($gcal->synchCalendar($pn_id, $google_id, intval($cs["timestamp"])))
				SQLQuery::create()->bypassSecurity()->updateByKey("GoogleCalendarSynchro", $google_id, array("timestamp"=>$time));
		}
	}
}
?>