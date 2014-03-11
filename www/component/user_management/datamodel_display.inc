<?php

$category = new datamodel\DataDisplayCategory("User");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("Users", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("domain", "Domain", true));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("username", "Username", true));
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

$display = new datamodel\TableDataDisplay("UserRole", $category);
$display->addDataDisplay(new datamodel\JoiningTableDataDisplay("Roles", "user", "role", "name"));
$display->addJoinFromAll();
$model->addTableDataDisplay($display);

?>