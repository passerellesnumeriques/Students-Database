<?php 
use datamodel\JoiningTableDataDisplay;
use datamodel\DataDisplayHandler;

class RolesDisplayHandler extends DataDisplayHandler {

	public function __construct() {
		parent::__construct("User", "User's roles");
	}

	public function getDisplayableData() {
		return array(new JoiningTableDataDisplay($this, "Roles", "user", "role", "name"));
	}

}

$model->internalGetTable("Users")
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("User",
		array(
			"domain"=>"Domain",
			"username"=>"Username",
		), array(
			"domain"
		)
	));

$model->internalGetTable("UserRole")
	->setDisplayHandler("user", new RolesDisplayHandler());
?>