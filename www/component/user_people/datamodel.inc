<?php
/* @var $model DataModel */
$model->addTable("UserPeople")
	->addForeignKey("user", "Users", true, false, false)
	->addForeignKey("people", "People", true, false, false)
	->addIndex("user")
	->allowReadAccess();

$model->addDataCategoryLink("User","/dynamic/people/page/profile?plugin=user_people&page=user_rights&people=%People.id%", "/static/user_management/access_list.png");
?>