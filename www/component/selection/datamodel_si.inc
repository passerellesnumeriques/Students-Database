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

$sm->addTable("SIHouse")
	->addPrimaryKey("id")
	->addForeignKey("applicant","People", true, false, true)
	->addEnum("house_status", array("Owned", "Rented", "Lended"), true, false)
	->addInteger("house_cost", 32, 0, null, true, false)
	->addString("house_comment",200,0,true,false)
	->addEnum("lot_status", array("Owned", "Rented", "Lended"), true, false)
	->addInteger("lot_cost", 32, 0, null, true, false)
	->addString("lot_comment",200,0,true,false)
	->addString("roof_type", 200,0, true, false)
	->addEnum("roof_condition", array("1 (Bad)","2","3","4","5 (Good)"), true, false)
	->addString("roof_comment",200,0,true,false)
	->addString("walls_type", 200,0, true, false)
	->addEnum("walls_condition", array("1 (Bad)","2","3","4","5 (Good)"), true, false)
	->addString("walls_comment",200,0,true,false)
	->addString("floor_type", 200,0, true, false)
	->addEnum("floor_condition", array("1 (Bad)","2","3","4","5 (Good)"), true, false)
	->addString("floor_comment",200,0,true,false)
	->addString("general_comment",500,0,true,false)
	->addReadAccess("can_access_selection_data",true)
	->addWriteAccess("edit_social_investigation", true)
	->addInsertAccess("edit_social_investigation", true)
	->addRemoveAccess("edit_social_investigation", true)
	;
	

?>