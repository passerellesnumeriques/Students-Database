<?php 
use datamodel\MultipleDisplayHandler;

/* @var $model DataModel */
$model->addTable("PNDepartment")
	->addPrimaryKey("id")
	->addString("name",100,1,false,true)
	->setRoot()
	->addReadAccess("consult_staff_list", true)
	->addWriteAccess("manage_staff", true)
	->addInsertAccess("manage_staff", true)
	->addRemoveAccess("manage_staff", true)
	;
$model->addTable("Staff")
	->addForeignKey("people", "People", true, false, false, false, true)
	->setPrimary("people")
	->setRoot()
	->addReadAccess("consult_staff_list", true)
	->addWriteAccess("manage_staff", true)
	->addInsertAccess("manage_staff", true)
	->addRemoveAccess("manage_staff", true)
	;
$model->addTable("StaffPosition")
	->addPrimaryKey("id")
	->addForeignKey("people", "People", true, false, false)
	->addForeignKey("department", "PNDepartment", false, false, true, true)
	->addString("position",100,1,false,false)
	->addDate("start", true, false)
	->addDate("end", true, false)
	->addReadAccess("consult_staff_list", true)
	->addWriteAccess("manage_staff", true)
	->addInsertAccess("manage_staff", true)
	->addRemoveAccess("manage_staff", true)
	->setDisplayHandler(null, new datamodel\MultipleDisplayHandler("Staff", "Positions", array(
		new datamodel\FieldsDisplayHandler("Staff", "Position", array(
			"position" => "Position",
			"start" => "Position Start",
			"end" => "Position End"
		)),
		new datamodel\ChoiceFieldDisplayHandler("Staff", "Department", "department", "name")
	)))
	;
?>