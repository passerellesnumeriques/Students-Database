<?php
$server = $_POST["server"];
$domain = $_POST["domain"];
$username = $_POST["username"];
$session = $_POST["session"];
$campaign_id = $_POST["campaign"];
$token = $_POST["token"];
$sms_path = realpath(dirname(__FILE__)."/../sms");
$app_version = file_get_contents($sms_path."/version");

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
			self::removeDirectory($path."/".$filename);
		else
			unlink($path."/".$filename);
	}
	closedir($dir);
	if (!@rmdir($path))
		rmdir($path);
}

// first, reset our database
$db = mysqli_connect("localhost","root","","",8889);
mysqli_query($db, "DROP DATABASE `selectiontravel_$domain`");
mysqli_query($db, "CREATE DATABASE `selectiontravel_$domain` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
mysqli_query($db, "DROP DATABASE `selectiontravel_init`");
mysqli_query($db, "CREATE DATABASE `selectiontravel_init` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
mysqli_close($db);

// write the config
$f = fopen($sms_path."/install_config.inc","w");
fwrite($f, "<?php global \$local_domain, \$db_config; \$local_domain = '$domain'; \$db_config = array(\"type\"=>\"MySQL\",\"server\"=>\"localhost\",\"port\"=>8889,\"user\"=>\"root\",\"password\"=>\"\",\"prefix\"=>\"selectiontravel_\");?>");
fclose($f);
// keep only one domain
$conf = include($sms_path."/conf/domains");
$domains = array_keys($conf);
foreach ($domains as $d) if ($d <> $domain) unset($conf[$d]);
$f = fopen($sms_path."/conf/domains","w");
fwrite($d,"<?php return ".var_export($conf,true).";?>");
fclose($f);
// generate an instance uid
$f = fopen($sms_path."/conf/instance.uid","w");
fwrite($f,"selection_travel.$domain.".time().".".rand(0,1000));
fclose($f);
// save the username
$f = fopen($sms_path."/conf/selection_travel_username","w");
fwrite($f, $username);
fclose($f);

// download a backup
$c = curl_init("http://$server/dynamic/selection/service/download_backup_from_travel_install?type=get_info");
curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($c, CURLOPT_POSTFIELDS, array("username"=>$username,"session"=>$session,"token"=>$token));
curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$app_version","User-Agent: Students Management Software - Travel Version Synchronization"));
curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($c, CURLOPT_TIMEOUT, 1000);
set_time_limit(1100);
$info = curl_exec($c);
$info = json_decode($info, true);
removeDirectory(dirname(__FILE__)."/data");
@mkdir(dirname(__FILE__)."/data");
$f = fopen(dirname(__FILE__)."/data/data.zip","w");
for ($from = 0; $from < intval($info["size"]); ) {
	$to = $from + 5*1024*1024;
	if ($to > intval($info["size"])-1) $to = intval($info["size"])-1;
	$c = curl_init("http://$server/dynamic/selection/service/download_backup_from_travel_install?type=download&from=$from&to=$to&id=".$info["id"]);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_POSTFIELDS, array("username"=>$username,"session"=>$session,"token"=>$token));
	curl_setopt($c, CURLOPT_HTTPHEADER, array("Cookie: pnversion=$app_version","User-Agent: Students Management Software - Travel Version Synchronization"));
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($c, CURLOPT_TIMEOUT, 1000);
	set_time_limit(1100);
	$data = curl_exec($c);
	fwrite($f, $data);
}
fclose($f);
// extract the downloaded file
mkdir(dirname(__FILE__)."/data/unzip");
$zip = new ZipArchive();
$zip->open(dirname(__FILE__)."/data/data.zip");
$zip->extractTo(dirname(__FILE__)."/data/unzip");
$zip->close();
// copy configuration files sent by the server
$dir = opendir(dirname(__FILE__)."/data/unzip/conf");
while (($file = readdir($dir)) <> null) {
	if (is_dir(dirname(__FILE__)."/data/unzip/conf/$file")) continue;
	copy(dirname(__FILE__)."/data/unzip/conf/$file", $sms_path."/conf/$file");
}
// import backup in both database: init and domain
set_include_path($sms_path);
chdir($sms_path);
require_once 'component/application/Backup.inc';
require_once 'DataBaseSystem_MySQL.inc';
require_once 'component/PNApplication.inc';
if (PNApplication::$instance == null) {
	PNApplication::$instance = new PNApplication();
	PNApplication::$instance->local_domain = $domain;
	PNApplication::$instance->current_domain = $domain;
	PNApplication::$instance->init();
}
require_once("SQLQuery.inc");
require_once("component/data_model/Model.inc");
require_once("component/data_model/DataBaseLock.inc");

$db_system = new DataBaseSystem_MySQL();
$db_system->connect("localhost", "root", "", null, 8889);
Backup::importBackupFrom(dirname(__FILE__)."/data/unzip", dirname(__FILE__)."/data/unzip/datamodel.json", $db_system, "selectiontravel_init", $sms_path."/data");
Backup::importBackupFrom(dirname(__FILE__)."/data/unzip", dirname(__FILE__)."/data/unzip/datamodel.json", $db_system, "selectiontravel_$domain", $sms_path."/data");
// remove other users
$db_system->execute("DELETE FROM `selectiontravel_init`.`Users` WHERE `domain` != '".$db_system->escapeString($domain)."' OR `username` != '".$db_system->escapeString($username)."'");
$db_system->execute("DELETE FROM `selectiontravel_$domain`.`Users` WHERE `domain` != '".$db_system->escapeString($domain)."' OR `username` != '".$db_system->escapeString($username)."'");
// remove any locks
$db_system->execute("DELETE FROM `selectiontravel_$domain`.`DataLocks` WHERE 1");
// lock other campaigns
$campaigns = SQLQuery::create()->bypassSecurity()->select("SelectionCampaign")->whereNotValue("SelectionCampaign","id",$campaign_id)->execute();
if (count($campaigns) > 0) {
	$locked_by = null;
	$sm = DataModel::get()->getSubModel("SelectionCampaign");
	foreach ($sm->internalGetTables() as $table)
		foreach ($campaigns as $c)
			DataBaseLock::lockTableForEver($table->getSQLNameFor($c["id"]), "You are on a travelling version, but not for this campaign", $locked_by);
}
if (PNApplication::hasErrors()) PNApplication::printErrors();
else echo "OK";
?>