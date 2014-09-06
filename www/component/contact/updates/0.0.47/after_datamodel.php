<?php
$rows = SQLQuery::create()->bypassSecurity()->select("RoleRights")->whereValue("RoleRights","right","see_other_people_details")->execute();
SQLQuery::create()->bypassSecurity()->removeRows("RoleRights", $rows); 
$rows = SQLQuery::create()->bypassSecurity()->select("UserRights")->whereValue("UserRights","right","see_other_people_details")->execute();
SQLQuery::create()->bypassSecurity()->removeRows("UserRights", $rows); 

$rows = SQLQuery::create()->bypassSecurity()->select("RoleRights")->whereValue("RoleRights","right","edit_people_details")->execute();
SQLQuery::create()->bypassSecurity()->removeRows("RoleRights", $rows); 
$rows = SQLQuery::create()->bypassSecurity()->select("UserRights")->whereValue("UserRights","right","edit_people_details")->execute();
SQLQuery::create()->bypassSecurity()->removeRows("UserRights", $rows); 

$rows = SQLQuery::create()->bypassSecurity()->select("RoleRights")->whereValue("RoleRights","right","edit_organization_type")->execute();
SQLQuery::create()->bypassSecurity()->removeRows("RoleRights", $rows); 
$rows = SQLQuery::create()->bypassSecurity()->select("UserRights")->whereValue("UserRights","right","edit_organization_type")->execute();
SQLQuery::create()->bypassSecurity()->removeRows("UserRights", $rows); 
?>