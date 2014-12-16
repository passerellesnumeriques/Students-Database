<?php
$server = $_POST["server"];
$domain = $_POST["domain"];
$username = $_POST["username"];
$session = $_POST["session"];
$sms_path = realpath(dirname(__FILE__)."/../sms");

// first, reset our database
$db = mysqli_connect("localhost","root","","",8889);
mysqli_query($db, "DROP DATABASE `selectiontravel_$domain`");
mysqli_query($db, "CREATE DATABASE `selectiontravel_$domain` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");

// write the config
$f = fopen($sms_path."/install_config.inc","w");
fwrite($f, "<?php global \$local_domain, \$db_config; \$local_domain = '$domain'; \$db_config = array(\"type\"=>\"MySQL\",\"server"=>\"localhost\",\"port\"=>8889,\"user\"=>\"root\",\"password\"=>\"\",\"prefix\"=>\"selectiontravel_\");?>");
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
$f = fopen($sms_path."/conf/selection_travel_username","w"_;
fwrite($f, $username);
fclose($f);

// download a backup
// TODO

// download Google config
// TODO

// recover from backup
// TODO

mysqli_close($db);
?>