<?php


/* @var $model DataModel */
$model->addTable("Users")
	->addPrimaryKey("id")
	->addString("domain",100,1,false)
	->addString("username",100,1,false)
	->addIndex("domain","username")
	->setRoot()
	->addReadAccess("consult_user_list",true)
	;
$model->addTable("InternalUser")
	->addString("username", 100,1, false, true)
	->addString("password", 40, 40, false)
	->setPrimary("username")
	;
$model->addTable("UserRights")
	->addForeignKey("user", "Users", true, false, true)
	->addString("right", 100, 1, false)
	->addString("value")
	->addIndex("user")
	->addReadAccess("consult_user_rights",true)
	->addWriteAccess("edit_user_rights", true)
	->addInsertAccess("edit_user_rights", true)
	->addRemoveAccess("edit_user_rights", true)
	;
$model->addTable("Role")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->setRoot()
	->allowReadAccess()
	->addWriteAccess("manage_roles",true)
	->addInsertAccess("manage_roles", true)
	->addRemoveAccess("manage_roles", true)
	;
$model->addTable("UserRole")
	->addForeignKey("user", "Users", true, false, true)
	->addForeignKey("role", "Role", true, false, true)
	->setKey(array("user","role"))
	->addIndex("user")
	->addReadAccess("consult_user_roles",true)
	->addInsertAccess("assign_role",true)
	->addRemoveAccess("assign_role",true)
	;
$model->addTable("RoleRights")
	->addForeignKey("role", "Role", true, false, true)
	->addString("right", 100, 1, false)
	->addString("value")
	->addIndex("role")
	->allowReadAccess()
	->addInsertAccess("manage_roles",true)
	->addRemoveAccess("manage_roles",true)
	;
?>