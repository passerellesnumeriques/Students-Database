<?php
set_include_path(dirname(__FILE__));
date_default_timezone_set("GMT");
global $pn_app_version;
$pn_app_version = file_get_contents(dirname(__FILE__)."/version");
global $in_cron;
$in_cron = true;
global $cron_tasks;
$cron_tasks = array();
include("install_config.inc");
require_once("component/PNApplication.inc");
require_once("SQLQuery.inc");
PNApplication::$instance = new PNApplication();
PNApplication::$instance->init();
PNApplication::$instance->cron->executeTasks();
?>
