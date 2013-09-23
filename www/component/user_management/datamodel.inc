<?php
/* @var $model DataModel */
$model->addTable("Users")
	->addPrimaryKey("id")
	->addString("domain",100,1,false)
	->addString("username",100,1,false)
	->addIndex("domain","username")
	->setUnique(array("domain","username"))
	->restrictReadAccess("consult_user_list",true)
	->addDisplayableData("domain", "User", "Domain")
	->addDisplayableData("username", "User", "Username")
	;
$model->addDataCategoryLink("User","/dynamic/people/page/profile?plugin=user_management&page=user_rights&user=%Users.id%", "/static/user_management/access_list.png");
$model->addTable("UserRights")
	->addForeignKey("user", "Users", true, false, true)
	->addString("right", 100, 1, false)
	->addString("value")
	->addIndex("user")
	->restrictReadAccess("consult_user_rights",true)
	;
$model->addTable("Role")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->addDisplayableData("name", "User", "Role")
	->allowReadAccess()
	->restrictFieldWriteAccess("name","manage_roles",true)
	->restrictAddAccess("manage_roles", true)
	->restrictRemoveAccess("manage_roles", true)
	;
$model->addTable("UserRole")
	->addForeignKey("user", "Users", true, false, true)
	->addForeignKey("role", "Role", false, false, true)
	->addIndex("user")
	->restrictReadAccess("consult_user_roles",true)
	->restrictAddAccess("assign_role",true)
	->restrictRemoveAccess("assign_role",true)
	;
$model->addTable("RoleRights")
	->addForeignKey("role", "Role", true, false, true)
	->addString("right", 100, 1, false)
	->addString("value")
	->addIndex("role")
	->allowReadAccess()
	->restrictAddAccess("manage_roles",true)
	->restrictRemoveAccess("manage_roles",true)
	;
?>