<?php 
/* @var $model DataModel */
$model->addTable("GoogleUser")
	->addForeignKey("user", "Users", true, false, false, false, true)
	->addString("google_id",100,1,true,false)
	->addString("google_login", 255, 10,true,false)
	->setPrimary("user")
	;

$model->addTable("PNGoogleCalendar")
	->addForeignKey("pn_id", "Calendar", false, false, true, true, false)
	->addString("google_id", 255, 0, false, true)
	->setPrimary("google_id")
	;

$model->addTable("GoogleCalendarSynchro")
	->addString("google_id", 255, 0, false, true)
	->addInteger("timestamp", 32, null, null, false, false)
	->setPrimary("google_id")
	;

$model->addTable("GoogleCalendarShared")
	->addString("google_calendar_id", 255, 0, false, false)
	->addString("email", 255, 0, false, false)
	->setKey(array("google_calendar_id","email"))
	;

?>