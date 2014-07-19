<?php
/* @var $model DataModel */
$model->addTable("UserPeople")
	->addForeignKey("user", "Users", true, false, false, false, true)
	->addForeignKey("people", "People", true, false, false, false, true)
	->setPrimary("user")
	->addIndex("people")
	->allowReadAccess()
	->onInsert(function($fields_values, $id, $sub_model_instance) {
		PNApplication::$instance->user_people->assignDefaultRoles($id, $fields_values["people"]);
	})
	;
?>