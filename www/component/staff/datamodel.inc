<?php 
/* @var $model DataModel */
$model->addTable("PNDepartment")
	->addPrimaryKey("id")
	->addString("name",100,1,false,true)
	->setRoot()
	->addReadAccess("consult_staff_list", true)
	->addWriteAccess("manage_staff", true)
	->addInsertAccess("manage_staff", true)
	->addRemoveAccess("manage_staff", true)
	->setRowDescriptionProvider(function($row) {
		return "Department ".htmlentities($row["name"]);
	})
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
	->addDate("start", false, false)
	->addDate("end", true, false)
	->addReadAccess("consult_staff_list", true)
	->addWriteAccess("manage_staff", true)
	->addInsertAccess("manage_staff", true)
	->addRemoveAccess("manage_staff", true)
	;
?>