<?php
global $in_cron;
$in_cron = true;
global $cron_tasks;
$cron_tasks = array();
require_once("component/PNApplication.inc");
require_once("SQLQuery.inc");
PNApplication::$instance = new PNApplication();
PNApplication::$instance->init();
PNApplication::$instance->cron->executeTasks();
?>