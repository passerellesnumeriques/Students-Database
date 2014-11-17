<?php 
require_once("component/google/lib_api/PNGoogleCalendar.inc");
$gcal = new PNGoogleCalendar();
$gcal->synchCalendarsList();
?>