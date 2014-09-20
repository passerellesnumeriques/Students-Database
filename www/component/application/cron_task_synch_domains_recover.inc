<?php 
global $pn_app_version;
$start = time();
foreach (PNApplication::$instance->getDomains() as $domain=>$descr) {
	if (!file_exists("data/domains_synch/$domain/latest_download")) continue; // nothing downloaded
	if (time() - $start > 60*60) break; // we already spend more than 1 hour !
	$latest_download = include("data/domains_synch/$domain/latest_download");
	if ($latest_download["version"] <> $pn_app_version) continue; // backup is not from the same version
	if (file_exists("data/domains_synch/$domain/latest_recover")) {
		$latest_recover = include("data/domains_synch/$domain/latest_recover");
		if ($latest_recover["version"] == $latest_download["version"] && $latest_recover["time"] == $latest_download["time"])
			continue; // we already synchronized this backup
	}
	// we can proceed
	require_once("component/application/Backup.inc");
	Backup::synchronizeDatabase("data/domains_synch/$domain", $domain);
	if (!PNApplication::hasErrors()) {
		copy("data/domains_synch/$domain/latest_download", "data/domains_synch/$domain/latest_recover");
		touch("data/domains_synch/$domain/latest_recover");
	}
}
?>