<?php 
require_once("component/application/Backup.inc");
Backup::removeOlds(30);
Backup::createBackup();
?>