<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$sm->addTable("InformationSession")
	->addPrimaryKey("id")
	->addForeignKey("date","CalendarEvent",false,true,false,true)
	->addForeignKey("geographic_area", "GeographicArea", false, false, true)
	->addInteger("number_boys_expected",10)
	->addInteger("number_girls_expected",10)
	->addInteger("number_boys_real",10)
	->addInteger("number_girls_real",10)
	->addString("name")
	->addReadAccess("see_information_session",true)
	->addWriteAccess("manage_information_session",true)
	->addInsertAccess("manage_information_session",true)
	->addRemoveAccess("manage_information_session",true)
	->onInsert(function($inserted) {
		foreach ($inserted as $row)
			try {
				PNApplication::$instance->news->post("selection", null, array("campaign".$row["sub_model_instance"]), "activity", "New Information Session: <i>".$row["fields_values"]["name"]."</i>");
			} catch (Exception $e) { PNApplication::error($e); }
	})
	;
$model->internalGetTable("CalendarEvent")
	->addReadAccessFromForeignTable("InformationSession")
	->addRemoveAccessFromForeignTable("InformationSession")
	;
		

$sm->addTable("InformationSessionPartner")
	->addForeignKey("information_session","InformationSession",true,false,true,false)
	->addForeignKey("organization","Organization",true,false,true,false)
	->addBoolean("host") //this attributes means that this partner is the one where the IS takes place
	->addForeignKey("host_address","PostalAddress",false,false,true,true) //this attributes is set only when host attribute == true: can select a praticular address in case the organization has several ones
	->setKey(array("information_session","organization"))
	->addReadAccess("see_information_session",true)
	->addWriteAccess("manage_information_session",true)
	->addInsertAccess("manage_information_session",true)
	->addRemoveAccess("manage_information_session",true);
$model->internalGetTable("Organization")
	->addReadAccessFromForeignTable("InformationSessionPartner");
$model->internalGetTable("PostalAddress")
	->addReadAccessFromForeignTable("InformationSessionPartner");

$sm->addTable("InformationSessionContactPoint")
	->addForeignKey("information_session","InformationSession",true,false,true,false)
	->addForeignKey("organization","Organization",true,false,true,false)
	//This two attributes are a foreignKey for IS partner table
	->addForeignKey("people","People",true,false,true,false)
	//People + orga = foreignKey for ContactPoint table
	->setKey(array("information_session","organization","people"))
	->addReadAccess("see_information_session",true)
	->addWriteAccess("manage_information_session",true)
	->addInsertAccess("manage_information_session",true)
	->addRemoveAccess("manage_information_session",true)
	;

?>