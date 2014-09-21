<?php
set_include_path(dirname(__FILE__));
date_default_timezone_set("GMT");
if (!file_exists("data/cron")) @mkdir("data/cron");
@touch("data/cron/maintenance_in_progress");
for ($i = 0; $i < 60; $i++) {
	if (!file_exists("data/cron/in_progress")) break;
	sleep(10);
}
global $pn_app_version;
$pn_app_version = file_get_contents(dirname(__FILE__)."/version");
global $in_cron_maintenance;
$in_cron_maintenance = true;
$f = fopen("maintenance_in_progress","w");
fclose($f);
$f = fopen("maintenance/password","w");
fclose($f);
$f = fopen("maintenance/origin","w");
fwrite($f, "Automatic Maintenance");
fclose($f);
@unlink("maintenance_time");
@unlink("maintenance/ask_cancel");
include("install_config.inc");
require_once("component/PNApplication.inc");
require_once("SQLQuery.inc");
PNApplication::$instance = new PNApplication();
PNApplication::$instance->init();
PNApplication::$instance->cron->executeMaintenanceTasks();
@unlink("maintenance/password");
@unlink("maintenance/origin");
@unlink("maintenance/ask_cancel");
@unlink("maintenance_in_progress");
@unlink("maintenance_time");
@unlink("data/cron/maintenance_in_progress");
?>