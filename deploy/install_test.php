<?php 
set_time_limit(300);
$type = $_GET["type"];
$deploy_path = realpath($_POST["path"]);
$test_path = realpath(dirname(__FILE__)."/..")."/test_deploy";
$version = $_POST["version"];

function remove_directory($path) {
	$dir = opendir($path);
	while (($filename = readdir($dir)) <> null) {
		if ($filename == ".") continue;
		if ($filename == "..") continue;
		if (is_dir($path."/".$filename))
			remove_directory($path."/".$filename);
		else
			unlink($path."/".$filename);
	}
	closedir($dir);
	if (!@rmdir($path))
		rmdir($path);
}

if (file_exists($test_path)) remove_directory($test_path);
mkdir($test_path); 

switch ($type) {
	case "fresh":
		$zip = new ZipArchive();
		$zip->open($deploy_path."/to_deploy/Students_Management_Software_".$version.".zip");
		$zip->extractTo($test_path);
		$zip->close();
		@mkdir($test_path."/data");
		@mkdir($test_path."/data/init");
		$zip = new ZipArchive();
		$zip->open($deploy_path."/to_deploy/Students_Management_Software_".$version."_init_data.zip");
		$zip->extractTo($test_path."/data/init");
		$zip->close();
		$domains = include($test_path."/conf/domains");
		$new_domains = file_get_contents($test_path."/conf/domains");
		foreach ($domains as $domain=>$conf)
			$new_domains = str_replace("\"$domain\"","\"$domain"."_Test\"",$new_domains);
		$f = fopen($test_path."/conf/domains","w");
		fwrite($f,$new_domains);
		fclose($f);
		echo "OK";
		break;
	case "update":
		// TODO
		echo "TODO";
		break;
}

?>