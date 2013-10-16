<?php 
/* @var $model DataModel */
$model->addTable("PNDepartment")
	->addPrimaryKey("id")
	->addString("name",100,1,false,true)
	->restrictReadAccess("consult_staff_list", true)
	->restrictWriteAccess("manage_staff", true)
	->restrictAddAccess("manage_staff", true)
	->restrictRemoveAccess("manage_staff", true)
	;
$model->addTable("Staff")
	->addForeignKey("people", "People", true, false, false)
	->addForeignKey("department", "PNDepartment", false, false, true)
	->restrictReadAccess("consult_staff_list", true)
	->restrictWriteAccess("manage_staff", true)
	->restrictAddAccess("manage_staff", true)
	->restrictRemoveAccess("manage_staff", true)
	;
$model->addTable("StaffPosition")
	->addForeignKey("people", "People", true, false, false)
	->addForeignKey("department", "PNDepartment", false, false, true)
	->addString("position",100,1,false,false)
	->addDate("start", false, false)
	->addDate("end", true, false)
	->restrictReadAccess("consult_staff_list", true)
	->restrictWriteAccess("manage_staff", true)
	->restrictAddAccess("manage_staff", true)
	->restrictRemoveAccess("manage_staff", true)
	;
?>