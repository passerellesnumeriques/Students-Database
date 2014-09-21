<?php
set_include_path(dirname(__FILE__));
date_default_timezone_set("GMT");
if (file_exists("data/cron/maintenance_in_progress"))
	return;
if (!file_exists("data/cron")) @mkdir("data/cron");
@touch("data/cron/in_progress");
if (file_exists("data/cron/maintenance_in_progress")) {
	@unlink("data/cron/in_progress");
	return;
}
global $pn_app_version;
$pn_app_version = file_get_contents(dirname(__FILE__)."/version");
global $in_cron;
$in_cron = true;
include("install_config.inc");
require_once("component/PNApplication.inc");
require_once("SQLQuery.inc");
PNApplication::$instance = new PNApplication();
PNApplication::$instance->init();
PNApplication::$instance->cron->executeTasks();
@unlink("data/cron/in_progress");
?>