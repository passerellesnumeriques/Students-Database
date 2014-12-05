<?php
set_include_path(dirname(__FILE__));
date_default_timezone_set("GMT");
if (file_exists("data/cron/maintenance_in_progress"))
	return;
if (!file_exists("data/cron")) @mkdir("data/cron");
if (file_exists("data/cron/in_progress")) {
	$time = filectime("data/cron/in_progress");
	if (time() - $time > 10*60) {
		// more than 10 minutes ago !
		@unlink("data/cron/in_progress");
	} else
		return;
}
@touch("data/cron/in_progress");
if (file_exists("data/cron/maintenance_in_progress")) {
	@unlink("data/cron/in_progress");
	return;
}
@unlink("data/cron/cron_errors");
global $pn_app_version;
$pn_app_version = file_get_contents(dirname(__FILE__)."/version");
global $in_cron;
$in_cron = true;
include("install_config.inc");

function end() {
	if (PNApplication::hasErrors()) {
		$f = fopen("data/cron/cron_errors","w");
		fwrite($f,json_encode(PNApplication::$errors));
		fclose($f);
	}
	@unlink("data/cron/in_progress");
	die();
}

function cron_shutdown_catch() {
	$msg = "Cron didn't finish correctly.";
	$error = error_get_last();
	if ($error <> null)
		$msg.= " Last error was in ".$error["file"]." line ".$error["line"].": ".$error["message"];
	$content = ob_get_clean();
	if ($content <> "")
		$msg .= "<br/>Output generated at failing time:<br/>".str_replace("\n", "<br/>", toHTML($content));
	PNApplication::errorHTML($msg);
	end();
}

register_shutdown_function("cron_shutdown_catch");
set_error_handler(function($severity, $message, $filename, $lineno) {
	if (error_reporting() == 0) return true;
	PNApplication::error("PHP Error: ".$message." in ".$filename.":".$lineno);
	return true;
});

try {
	require_once("component/PNApplication.inc");
	require_once("SQLQuery.inc");
	PNApplication::$instance = new PNApplication();
	PNApplication::$instance->init();
	PNApplication::$instance->cron->executeTasks();
} catch (Exception $e) {
	PNApplication::error($e);
}
restore_error_handler();
end();
?>