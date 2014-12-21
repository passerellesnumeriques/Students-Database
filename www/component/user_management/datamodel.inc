<?php


/* @var $model DataModel */
$model->addTable("Users")
	->addPrimaryKey("id")
	->addString("domain",100,1,false)
	->addString("username",100,1,false)
	->addTimestamp("last_connection", true, false)
	->addIndex("domain","username")
	->setRoot()
	->allowReadAccess() // everybody need to be able to know who says what.. so need to access the list of users
#!SELECTION_TRAVEL
	->addInsertAccess("manage_users", true)
	->addRemoveAccess("manage_users", true)
#END
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
	->setKey(array("user","right"))
	->addReadAccess("consult_user_rights",true)
	->addWriteAccess("edit_user_rights", true)
	->addInsertAccess("edit_user_rights", true)
	->addRemoveAccess("edit_user_rights", true)
	;
$model->addTable("Role")
	->addPrimaryKey("id")
	->addString("name", 100, 1, false, true)
	->addBoolean("builtin",false)
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
#!SELECTION_TRAVEL
	->addInsertAccess("assign_role",true)
	->addRemoveFilter(function(&$q,$table_alias,&$locks){
		if (!PNApplication::$instance->user_management->has_right("assign_role")) {
			$q->where("FALSE");
			return;
		}
		$q->field($table_alias,"user","THE_USER_UNASSIGNED");
		$q->field($table_alias,"role","THE_ROLE_UNASSIGNED");
	}, function($rows){
		$res = array();
		$users_ids = array();
		foreach ($rows as $row)
			if ($row["THE_ROLE_UNASSIGNED"] == -1)
				array_push($users_ids, $row["THE_USER_UNASSIGNED"]);
		if (count($users_ids) == 0) return $rows;
		$users = SQLQuery::create()->bypassSecurity()->select("Users")->whereIn("Users","id",$users_ids)->execute();
		foreach ($rows as $row) {
			if ($row["THE_ROLE_UNASSIGNED"] == -1) {
				$found = false;
				foreach ($users as $u)
					if ($u["id"] == $row["THE_USER_UNASSIGNED"]) {
						$found = $u["username"] == "admin";
						if ($found)
							PNApplication::error("Administrator role cannot be removed for user admin.");
						break;
					}
				if ($found) continue; // do not remove Administrator to admin
			}
			array_push($res, $row);
		}
		return $res;
	})
#END
	;
$model->addTable("RoleRights")
	->addForeignKey("role", "Role", true, false, true)
	->addString("right", 100, 1, false)
	->addString("value")
	->addIndex("role")
	->setKey(array("role","right"))
	->allowReadAccess()
	->addInsertAccess("manage_roles",true)
	->addRemoveAccess("manage_roles",true)
	;
$model->addTable("UserPeople")
	->addForeignKey("user", "Users", true, false, false, false, true)
	->addForeignKey("people", "People", true, false, false, false, true)
	->setPrimary("user")
	->addIndex("people")
	->allowReadAccess()
	->addInsertAccess("manage_users", true)
	->addRemoveAccess("manage_users", true)
	->onInsert(function($inserted) {
		// TODO improve
		foreach ($inserted as $row)
			PNApplication::$instance->user_management->assignDefaultRoles($row["fields_values"]["user"], $row["fields_values"]["people"]);
	})
	;
?>