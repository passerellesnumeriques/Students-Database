<?php
@unlink("download_progress");
$server = $_POST["server"];
$domain = $_POST["domain"];
$username = $_POST["username"];
$session = $_POST["session"];
$campaign_id = $_POST["campaign"];
$token = $_POST["token"];
$synch_uid = $_POST["synch_uid"];
$sms_path = realpath(dirname(__FILE__)."/../sms");
$app_version = file_get_contents($sms_path."/version");

function progress($text, $pos = null, $total = null) {
	$f = fopen("download_progress","w");
	fwrite($f, ($pos !== null ? "%$pos,$total%" : "").$text);
	fclose($f);
}
progress("Setup software...");

/**
 * Remove a directory with its content
 * @param string $path the directory to remove
 */
function removeDirectory($path) {
	$dir = opendir($path);
	while (($filename = readdir($dir)) <> null) {
		if ($filename == ".") continue;
		if ($filename == "..") continue;
		if (is_dir($path."/".$filename))
			removeDirectory($path."/".$filename);
		else
			unlink($path."/".$filename);
	}
	closedir($dir);
	if (!@rmdir($path))
		if (!@rmdir($path))
			if (!@rmdir($path))
				rmdir($path);
}

// save the synch uid
@unlink(dirname(__FILE__)."/synch.uid");
$f = fopen(dirname(__FILE__)."/synch.uid", "w");
fwrite($f, $synch_uid);
fclose($f);

// first, reset our database
progress("Initializing the database on your computer...");
$db = mysqli_connect("localhost","root","","",8889);
if ($db === false) die("Error: unable to connect to the database");
set_time_limit(300);
mysqli_query($db, "DROP DATABASE `selectiontravel_$domain`");
mysqli_query($db, "CREATE DATABASE `selectiontravel_$domain` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
set_time_limit(300);
mysqli_query($db, "DROP DATABASE `selectiontravel_init`");
mysqli_query($db, "CREATE DATABASE `selectiontravel_init` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
mysqli_close($db);

// remove previous data
if (file_exists($sms_path."/data")) removeDirectory($sms_path."/data");
mkdir($sms_path."/data");
mkdir($sms_path."/data/$domain");

progress("Setup software...");
// write the config
$f = fopen($sms_path."/install_config.inc","w");
fwrite($f, "<?php global \$local_domain, \$db_config; \$local_domain = '$domain'; \$db_config = array(\"type\"=>\"MySQL\",\"server\"=>\"localhost\",\"port\"=>8889,\"user\"=>\"root\",\"password\"=>\"\",\"prefix\"=>\"selectiontravel_\");?>");
fclose($f);
// keep only one domain
$conf = include($sms_path."/conf/domains");
$domains = array_keys($conf);
foreach ($domains as $d) if ($d <> $domain) unset($conf[$d]);
$f = fopen($sms_path."/conf/domains","w");
fwrite($f,"<?php return ".var_export($conf,true).";?>");
fclose($f);
// generate an instance uid
$f = fopen($sms_path."/conf/instance.uid","w");
fwrite($f,"selection_travel.$domain.".time().".".rand(0,1000));
fclose($f);
// save the username
$f = fopen($sms_path."/conf/selection_travel_username","w");
fwrite($f, $username);
fclose($f);
// remove campaign id
@unlink($sms_path."/conf/selection_travel_campaign");

// download a backup
progress("The server is preparing a copy of the database");
$c = curl_init("http://$server/dynamic/selection/service/travel/download_backup?type=get_info&campaign=".$campaign_id);
curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($c, CURLOPT_POSTFIELDS, array("username"=>$username,"session"=>$session,"token"=>$token));
curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$app_version","User-Agent: Students Management Software - Travel Version Synchronization"));
curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($c, CURLOPT_TIMEOUT, 1000);
set_time_limit(1100);
$result = curl_exec($c);
if ($result === false) die("Error: unable to connect to the Students Management Software Server: ".curl_error($c));
$info = json_decode($result, true);
if ($info == null) die("Error: unexpected data received from the server: ".$result);
if (isset($info["errors"]) && count($info["errors"]) > 0) die("Error: server returned some errors: ".$result);
if (!isset($info["result"])) die("Error: no result received from the server: ".$result);
$info = $info["result"];
if (!isset($info["size"]) || !isset($info["id"])) die("Error: missing data received from the server: ".$result);
if (file_exists(dirname(__FILE__)."/data"))
	removeDirectory(dirname(__FILE__)."/data");
if (file_exists(dirname(__FILE__)."/data")) {
	sleep(1);
	removeDirectory(dirname(__FILE__)."/data");
}
@mkdir(dirname(__FILE__)."/data");
if (!file_exists(dirname(__FILE__)."/data")) {
	sleep(1);
	mkdir(dirname(__FILE__)."/data");
}
progress("Downloading a copy of the database",0,$info["size"]);
$step = 5*1024*1024;
$f = @fopen(dirname(__FILE__)."/data/data.zip","w");
if (!$f) {
	sleep(1);
	@mkdir(dirname(__FILE__)."/data");
	$f = fopen(dirname(__FILE__)."/data/data.zip","w");
	if (!$f) die();
}
for ($from = 0; $from < intval($info["size"]); ) {
	$to = $from + $step;
	if ($to > intval($info["size"])-1) $to = intval($info["size"])-1;
	$c = curl_init("http://$server/dynamic/selection/service/travel/download_backup?type=download&from=$from&to=$to&id=".$info["id"]);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_POSTFIELDS, array("username"=>$username,"session"=>$session,"token"=>$token));
	curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$app_version","User-Agent: Students Management Software - Travel Version Synchronization"));
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($c, CURLOPT_TIMEOUT, 120);
	set_time_limit(150);
	$data = curl_exec($c);
	if ($data === false) die("Error downloading database: ".curl_error($c));
	curl_close($c);
	fwrite($f, $data);
	$downloaded = strlen($data);
	if ($downloaded == 0) break;
	$from += $downloaded;
	progress("Downloading a copy of the database",floor($from/$step),floor(intval($info["size"])/$step));
}
fclose($f);
// extract the downloaded file
progress("Extracting the copy of the database...");
mkdir(dirname(__FILE__)."/data/unzip");
$zip = new ZipArchive();
$zip->open(dirname(__FILE__)."/data/data.zip");
$zip->extractTo(dirname(__FILE__)."/data/unzip");
$zip->close();
// copy configuration files sent by the server
if (file_exists(dirname(__FILE__)."/data/unzip/conf")) {
	$dir = opendir(dirname(__FILE__)."/data/unzip/conf");
	while (($file = readdir($dir)) <> null) {
		if (is_dir(dirname(__FILE__)."/data/unzip/conf/$file")) continue;
		copy(dirname(__FILE__)."/data/unzip/conf/$file", $sms_path."/conf/$file");
	}
}
// import backup in both database: init and domain
progress("Importing the copy of the database into your computer...");
set_include_path($sms_path);
chdir($sms_path);
include('install_config.inc');
require_once 'DataBaseSystem_MySQL.inc';
require_once("SQLQuery.inc");
require_once 'component/PNApplication.inc';
global $installing_selection_travel;
$installing_selection_travel = true;
if (PNApplication::$instance == null) {
	PNApplication::$instance = new PNApplication();
	PNApplication::$instance->local_domain = $domain;
	PNApplication::$instance->current_domain = $domain;
	PNApplication::$instance->init();
}
require_once("component/data_model/Model.inc");
require_once("component/data_model/DataBaseLock.inc");
require_once 'component/application/Backup.inc';

$db_system = new DataBaseSystem_MySQL();
$db_system->connect("localhost", "root", "", null, 8889);
set_time_limit(300);
Backup::synchronizeDatabase(dirname(__FILE__)."/data/unzip", $domain, $db_system, "selectiontravel_init");
set_time_limit(300);
Backup::synchronizeDatabase(dirname(__FILE__)."/data/unzip", $domain, $db_system, "selectiontravel_$domain");
set_time_limit(30);
// remove other users
$db_system->execute("DELETE FROM `selectiontravel_init`.`Users` WHERE `domain` != '".$db_system->escapeString($domain)."' OR `username` != '".$db_system->escapeString($username)."'");
$db_system->execute("DELETE FROM `selectiontravel_$domain`.`Users` WHERE `domain` != '".$db_system->escapeString($domain)."' OR `username` != '".$db_system->escapeString($username)."'");
// remove any locks
$db_system->execute("DELETE FROM `selectiontravel_$domain`.`DataLocks` WHERE 1");
// for potential access without full name, we are now using the domain database
$db_system->execute("USE `selectiontravel_$domain`");
// lock other campaigns
$res = $db_system->execute("SELECT `id` FROM `selectiontravel_$domain`.`SelectionCampaign` WHERE `id`!=".$campaign_id);
$campaigns = array();
while (($row = $db_system->nextRowArray($res)) <> null)
	array_push($campaigns, $row[0]);
foreach (DataModel::get()->getSubModel("SelectionCampaign")->internalGetTables() as $table) {
	foreach ($campaigns as $cid) {
		$locked_by = null;
		DataBaseLock::lockTableForEver($table->getSQLNameFor($cid), "Selection Travel Version - You can only edit the campaign you locked", $locked_by);
	}
}
// remove rights on any calendar except the one of the selection selection campaign
$res = $db_system->execute("SELECT `calendar` FROM `selectiontravel_$domain`.`SelectionCampaign` WHERE `id`=".$campaign_id);
$row = $db_system->nextRowArray($res);
$calendar_id = $row[0];
$db_system->execute("DELETE FROM `selectiontravel_$domain`.`UserCalendar` WHERE 1");
$db_system->execute("DELETE FROM `selectiontravel_$domain`.`CalendarRights` WHERE `calendar`!=".$calendar_id);
// add necessary rights on the calendar
$db_system->execute("INSERT INTO `selectiontravel_$domain`.`CalendarRights` (`calendar`,`right_name`,`right_value`,`writable`) VALUES ".
		 "($calendar_id,'manage_information_session',1,1)".
		",($calendar_id,'manage_exam_center',1,1)".
		",($calendar_id,'manage_interview_center',1,1)".
		",($calendar_id,'manage_selection_campaign',1,1)".
		",($calendar_id,'manage_trips',1,1)".
		",($calendar_id,'edit_social_investigation',1,1)"
);
if (PNApplication::hasErrors()) PNApplication::printErrors();
else {
	// save the campaign id
	$f = fopen($sms_path."/conf/selection_travel_campaign","w");
	fwrite($f, $campaign_id);
	fclose($f);
	// activate the software
	@unlink($sms_path."/index.php");
	copy($sms_path."/index_activated.php", $sms_path."/index.php");
	echo "OK";
}
@unlink("download_progress");
?>