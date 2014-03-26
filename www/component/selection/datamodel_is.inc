<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$sm->addTable("InformationSession")
	->addPrimaryKey("id")
	->addForeignKey("date","CalendarEvent",false,true,true,true)
	->addForeignKey("geographic_area", "GeographicArea", false, false, true)
	->addInteger("number_boys_expected",10)
	->addInteger("number_girls_expected",10)
	->addInteger("number_boys_real",10)
	->addInteger("number_girls_real",10)
	->addString("name")
	->addWriteAccess("edit_information_session",true)
	->addReadAccess("see_information_session_details",true)
	->addInsertAccess("manage_information_session",true)
	->addRemoveAccess("manage_information_session",true)
	->onInsert(function($fields,$id,$sub_model_instance) {
		try {
			PNApplication::$instance->news->post("selection", null, array("campaign".$sub_model_instance), "New Information Session: <i>".$fields["name"]."</i>");
		} catch (Exception $e) { PNApplication::error($e); }
	})
	;

$sm->addTable("InformationSessionPartner")
	->addForeignKey("information_session","InformationSession",true,false,true,false)
	->addForeignKey("organization","Organization",true,false,true,false)
	->addBoolean("host") //this attributes means that this partner is the one where the IS takes place
	->addForeignKey("host_address","PostalAddress",false,false,true,true) //this attributes is set only when host attribute == true: can select a praticular address in case the organization has several ones
	->setKey(array("information_session","organization"))
	->addWriteAccess("edit_information_session",true)
	->addReadAccess("see_information_session_details",true)
	->addInsertAccess("manage_information_session",true)
	->addRemoveAccess("manage_information_session",true);

$sm->addTable("InformationSessionContactPoint")
	->addForeignKey("information_session","InformationSession",true,false,true,false)
	->addForeignKey("organization","Organization",true,false,true,false)
	//This two attributes are a foreignKey for IS partner table
	->addForeignKey("people","People",true,false,true,false)
	//People + orga = foreignKey for ContactPoint table
	->setKey(array("information_session","organization","people"))
	->addWriteAccess("edit_information_session",true)
	->addReadAccess("see_information_session_details",true)
	->addInsertAccess("manage_information_session",true)
	->addRemoveAccess("manage_information_session",true)
	;
?>