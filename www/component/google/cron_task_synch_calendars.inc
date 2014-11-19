<?php
if (!PNApplication::$instance->google->isInstalled()) {
	PNApplication::error("Google is not configured.");
	return;
} 
require_once("component/google/lib_api/PNGoogleCalendar.inc");
$gcal = new PNGoogleCalendar();
$gcal->synchCalendarsList();
?>