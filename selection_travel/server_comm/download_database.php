<?php
$server = $_POST["server"];
$domain = $_POST["domain"];
$username = $_POST["username"];
$session = $_POST["session"];
$token = $_POST["token"];
$sms_path = realpath(dirname(__FILE__)."/../sms");
$app_version = file_get_contents($sms_path."/version");

// first, reset our database
$db = mysqli_connect("localhost","root","","",8889);
mysqli_query($db, "DROP DATABASE `selectiontravel_$domain`");
mysqli_query($db, "CREATE DATABASE `selectiontravel_$domain` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");

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
// TODO remove directory
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
// TODO

// download Google config
// TODO

// recover from backup
// TODO

mysqli_close($db);
?>