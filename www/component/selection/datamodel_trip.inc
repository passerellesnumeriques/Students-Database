<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$sm->addTable("Trip")
	->addPrimaryKey("id")
	->addString("name", 200, 1, false, false)
	->addReadAccess("can_access_selection_data",true)
	;

$sm->addTable("TripNode")
	->addPrimaryKey("id")
	->addForeignKey("trip", "Trip", true, false, true, false, false)
	->addString("title",200,0,false,false)
	->addString("comment",2000,0,true,false)
	->addForeignKey("host", "Organization", true, false, true, true, false)
	->addForeignKey("address", "PostalAddress", true, false, true, true, false)
	->addReadAccess("can_access_selection_data",true)
	;

$sm->addTable("TripNodePeople")
	->addForeignKey("node", "TripNode", true, false, true, false, false)
	->addForeignKey("people", "People", true, false, true, false, false)
	->setKey(array("node","people"))
	->addReadAccess("can_access_selection_data",true)
	;

$sm->addTable("TripNodeActivity")
	->addPrimaryKey("id")
	->addForeignKey("node", "TripNode", true, false, true, false, false)
	->addForeignKey("event", "CalendarEvent", true, false, true, false, false)
	->addDecimal("eating_cost")
	->addDecimal("sleeping_cost")
	->addString("comment",2000,0,true,false)
	->addReadAccess("can_access_selection_data",true)
	;

$sm->addTable("TripConnection")
	->addForeignKey("from","TripNode",true,false,true,true,false)
	->addForeignKey("to","TripNode",true,false,true,true,false)
	->addTimestamp("departure",false,false)
	->addTimestamp("arrival",false,false)
	->addDecimal("cost")
	->addString("comment",2000,0,true,false)
	->addReadAccess("can_access_selection_data",true)
	;
?>