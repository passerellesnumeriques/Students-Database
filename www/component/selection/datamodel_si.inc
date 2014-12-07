<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$sm->addTable("SocialInvestigation")
	->addForeignKey("event", "CalendarEvent", true, true, false, false, true)
	->setPrimary("event")
	->addForeignKey("applicant", "People", true, false, true)
	->addReadAccess("can_access_selection_data",true)
	;
$model->internalGetTable("CalendarEvent")
	->addReadAccessFromForeignTable("SocialInvestigation");

?>