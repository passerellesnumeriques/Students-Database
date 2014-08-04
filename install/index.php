<html>
<head>
<style type='text/css'>
html, body {
	width: 100%;
	height: 100%;
	margin: 0px;
	padding: 0px;
}
html, body, table {
	font-family: Verdana;
}
#container {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
	background-color: #D0D0D0;
}
#box {
	border: 1px solid #22bbea;
	border-radius: 5px;
	box-shadow: 5px 5px 5px 0px #808080;
}
#title {
	border-bottom: 1px solid #22bbea;
	font-weight: bold;
	text-align: center;
	font-size: 14pt;
	background-color: #22bbea;
	border-top-left-radius: 5px;
	border-top-right-radius: 5px;
	padding: 3px;
	color: white;
	text-shadow: #40a0c0 0.1em 0.1em 0.1em;
}
#content {
	padding: 10px;
	font-size: 10pt;
	background-color: white;
}
#box>#content:last-child {
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
}
#footer {
	border-top: 1px solid #22bbea;
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
	padding: 5px;
	background-color: white;
}
button {
    padding: 1px 3px 1px 3px;
    border-radius: 7px;
    background: linear-gradient(to bottom, #3498db, #2980b9);
    color: white;
    font-size: 9pt;
    font-weight: bold;
    text-transform: uppercase;
    border: 1px solid rgba(0,0,0,0);
	text-align: center;
    box-shadow: 1px 1px 1px rgba(170,170,170,0);
    display: inline-block;
    font-family: Arial;
    white-space: nowrap;
    cursor: pointer;
    margin: 1px;
    outline: 1px solid rgba(0,0,0,0);
}
button:hover {
    border: 1px solid rgba(255,255,255,0.4);
    box-shadow: 1px 1px 1px rgba(170,170,170,0.6);
    background: linear-gradient(to bottom, #44a8eb, #3990c9);
}
button:active {
    box-shadow: 0px 0px 0px rgba(170,170,170,0);
    position: relative;
    top: 1px;
    left: 1px;
}
button:focus {
	outline: 1px dotted #404080;
}
</style>
</head>
<body>
<div id='container'>
	<div id='box'>
		<div id='title'>Students Management Software - Installation</div>
		<div id='content'>
<?php
if (!isset($_POST["step"])) $step = 0; else $step = intval($_POST["step"]);

global $stop, $steps;
$stop = false;
$steps = array(
	array("description"=>"Checking PHP Version","function"=>"checkPHPVersion"),
	array("description"=>"Checking Apache Version","function"=>"checkApacheVersion"),
	array("description"=>"Checking PHP Extensions","function"=>"checkPHPExtensions"),
	array("description"=>"Checking file system access","function"=>"checkFSAccess"),
	array("description"=>"Checking zip functions","function"=>"checkZip"),
	array("description"=>"Checking Internet access","function"=>"checkInternetAccess"),
	array("description"=>"Download installer","function"=>"downloadInstaller"),
);

function checkPHPVersion() {
	$version = phpversion();
	$version = explode(".",$version);
	if (intval($version[0]) < 5)
		return "Old version: ".phpversion().", please install at least version 5";
	if (intval($version[0]) > 5) return "OK: Version ".phpversion()." found.";
	if (count($version) == 1 || intval($version[1]) < 4)
		return "WARNING: Version ".phpversion()." found, we recommend at least version 5.4";
	return "OK: Version ".phpversion()." found.";
}
function checkApacheVersion() {
	$soft = @$_SERVER["SERVER_SOFTWARE"];
	if ($soft == null) return "Unable to determine Web Server";
	if (substr($soft,0,7) <> "Apache/") return "This server is not Apache: ".$soft;
	$version = substr($soft,7);
	$v = explode(".",$version);
	if (intval($v[0]) < 2)
		return "Old version: ".$version.", please install at least version 2.2, version 2.4 is recommended";
	if (intval($v[0]) > 2) return "OK: Version ".$version." found.";
	if (count($v) == 1 || intval($v[1]) < 2)
		return "Old version: ".$version.", please install at least version 2.2, version 2.4 is recommended";
	if (intval($v[1]) < 4)
		return "WARNING: ".$version." found, at least version 2.4 is recommended";
	return "OK: Version ".$version." found.";
}
function checkPHPExtensions() {
	$list = get_loaded_extensions();
	$curl = false;
	$gd = false;
	$mbstring = false;
	$mysqli = false;
	foreach ($list as $ext) switch($ext) {
		case "curl": $curl = true; break;
		case "gd": $gd = true; break;
		case "mbstring": $mbstring = true; break;
		case "mysqli": $mysqli = true; break;
		case "curl": $curl = true; break;
	}

	if (!$curl) return "curl extension must be installed";
	if (!$gd) return "gd extension must be installed";
	if (!$mbstring) return "mbstring extension must be installed";
	if (!$mysqli) return "mysqli extension must be installed";
	return "OK: extensions curl,gd,mbstring,mysqli found.";
}
function checkFSAccess() {
	$path = realpath(dirname(__FILE__));
	$f = @fopen($path."/test.tmp","w");
	if ($f == null) return "Unable to create a file, please give the right to create files in the directory ".htmlentities($path);
	fclose($f);
	@unlink($path."/test.tmp");
	if (file_exists($path."/test.tmp")) return "Unable to remove our temporary file, please give the right to remove files in the directory ".htmlentities($path);
	@mkdir($path."/tmp");
	if (!file_exists($path."/tmp") || !is_dir($path."/tmp")) return "Unable to create a directory, please give the right to create sub-directories into ".htmlentities($path);
	@rmdir($path."/tmp");
	if (file_exists($path."/tmp")) return "Unable to remove our temporary sub-directory, please give the right to remove sub-directories into ".htmlentities($path);
	return "OK: Access rights correct.";
}
function checkZip() {
	if (class_exists("ZipArchive")) return "OK:";
	if (file_exists("/usr/bin/unzip")) return "OK:";
	return "Unable to find Zip functionalities on the server";
}
function checkInternetAccess() {
	$c = curl_init("http://www.google.com/");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($c, CURLOPT_TIMEOUT, 25);
	set_time_limit(45);
	$result = curl_exec($c);
	if ($result === false) {
		$err = "Error connecting to Internet (".curl_errno($c)."): ".curl_error($c);
		curl_close($c);
		return $err;
	}
	curl_close($c);
	return "OK:";
}
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
	rmdir($path);
}
function downloadInstaller() {
	$c = curl_init("http://sourceforge.net/projects/studentsdatabase/files/installer.zip/download");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($c, CURLOPT_TIMEOUT, 25);
	set_time_limit(240);
	$result = curl_exec($c);
	if ($result === false) {
		$err = "Error downloading installer (".curl_errno($c)."): ".curl_error($c);
		curl_close($c);
		return $err;
	}
	curl_close($c);

	if (file_exists("installer")) removeDirectory(realpath(dirname(__FILE__))."/installer");
	mkdir("installer");
	$f = fopen("installer/installer.zip","w");
	fwrite($f,$result);
	fclose($f);
	if (class_exists("ZipArchive")) {
		$zip = new ZipArchive();
		$zip->open("installer/installer.zip");
		$zip->extractTo(realpath(dirname(__FILE__))."/installer");
		$zip->close();
	} else {
		$output = array();
		$ret = 0;
		exec("/usr/bin/unzip \"".realpath(dirname(__FILE__))."/installer/installer.zip\" -d \"".realpath(dirname(__FILE__))."/installer/\"", $output, $ret);
		if ($ret <> 0)
			return "Error unzipping installer (".$ret.")";
	}
	unlink("installer/installer.zip");
	return "OK:";
}

global $icon_ok, $icon_error, $icon_warning;
$icon_ok = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABlklEQVR42r2Sz0oCURTGz/0zmZYmRRFSgZsiCDJoUzuhRbXJdi19gCChB9Bor0QP4CPUrkUL3Ri1CIV24kKiRULUlDZlM/fezjgaGSgTQQeGO8M5v+87Z+4h8Mcg/y8Q1YIaVwnfhNyzGhD8lQBb43HfmMwMz8ggQfLhhp8SVExgropPHnKm3svV45VZ/7SMaX4FygJ4qbKS8UyjJLBDlXdcQvOJ6q81emSeW6kfcMQXQHhKRIDitwCo3yFcp1HbkPANnhidFxnmVSAMgMYtJh+dpO3sHZE5fwhhDCUB3mtUr+vMzpe+fuLAFs8Gola8VWQCGGWqv1XYtieE886JiFKYQNi8J1AvcxvOd98COg0uipxnWUbsQrvNjwoFPimBaA6ssDujwA/ESfeI5Pusnk1R5LOy1Sq0XTunecWqZlqE++4BifGUtiCTsC4dEBwBdUbBvGRdrfdcJL7PmmpXDHQVHTPDSoshd5uIo/AVcS2SkraW55CCVWBhdK+6E+iIrIqi/WpdsKXOlbkXaIu0zj5wfwGX8QnItKrE+XQVRgAAAABJRU5ErkJggg==";
$icon_error = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAbwAAAG8B8aLcQwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAJqSURBVDjLfZPLT+JQGMW7af8Xlv0PXGjcuDBRE3WhxqQKlAgYqfgIQi6xPhCBFAYoA4jzWszWmDDBTDKrcXRh6kID0bjwNTMZdXx0ZjZnbtsMBoNzk1/a7/acc7+29zIAmKesr6+zpVKJX1tbE4rFolAoFHgK20zbUFADRw3kVaGgbyaT2InH8SUWw0YigaKq6tlslmQyGa5pAF3Bls/ntU8rK/iTzQL5fAPG3MflZSSTSU1RFFtDQC6X41RV1WoLC4Ci/JeqLCMej2uxWIyrB6TTaVIxzIuL+JVK4WJnB/eqatYGD+m0OfeQyZh1mRBEIhFiBtCW2JSi6PrcHEC53t6GMW5OTnBH3/1hdRVXtZo5d727a2p+BwKIyLIuyzLLJBIJ/h1tCxMTFsEgcHZmGY6O8OPw0LzH5SUQCtV1JRoSDod5JhqNChW/H3A4HpEk4PQU9XFxAUxONmjKNCQYDArM0tKS8MHnAwYH69zShzcHB3X/z2oVt080m14vZmdnBWZ+fp5/OTMDdHUB3d24EkV83duznOfnAP0Wxvi+v48bl8vUGNoXNMDv9/MMIYQNh0L6XXs70NqKbxsbltl4hYEBoL8fOD62QsplU3Pf1oYZSdJ9Ph9r/sZAIEDeOp1ASwvQ0QFUKkBPj1UbdHYCW1vWldavBQHj4+Okvg+mp6e5qakpbbuv79H0DJ97e+F2u7WxsTGuYStLkmSjLWlvRkZwT9t8ajTmSsPDcLlcmiiKtqaHyev1ch6Ph0y43fqq3Y73Q0MmEdqyRxR1p9NJHA4H9+xp/AddgaXw1CBQg2C32/nR0dGmx/kvBdTCbxuFDrgAAAAASUVORK5CYII=";
$icon_warning = "data:image/png;base64,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";

global $has_errors;
$has_errors = false;
function showStepResult($step) {
	global $has_errors;
	global $steps, $icon_ok, $icon_error, $icon_warning, $stop;
	echo $steps[$step]["description"];
	$result = $_POST["step_result_".$step];
	if (substr($result,0,3) == "OK:") {
		echo " <img src='".$icon_ok."' style='vertical-align:bottom'/> ".substr($result,3);
	} else if (substr($result,0,8) == "WARNING:") {
		echo " <img src='".$icon_warning."' style='vertical-align:bottom'/> ".substr($result,8);
	} else {
		$has_errors = true;
		echo " <img src='".$icon_error."' style='vertical-align:bottom'/> ".$result;
	}
	echo "<br/>";
}
function performStep($step) {
	global $stop, $steps, $has_errors;
	if ($step >= count($steps)) $stop = true;
	else if ($step == 6 && $has_errors) $stop = true;
	else {
		echo $steps[$step]["description"];
		$result = call_user_func($steps[$step]["function"]);
		$_POST["step_result_".$step] = $result;
	}
}
for ($i = 0; $i < $step; $i++) showStepResult($i);
performStep($step);
?>
		</div>
<?php if ($stop) { ?>
		<div id='footer'>
			<button onclick="location.reload();">Restart checks</button>
<?php if ($step == count($steps) && !$has_errors) { ?>
			<button onclick="location.href = 'installer/installer.php';">Continue installation</button>
<?php } ?>
		</div>
<?php } ?>
	</div>
</div>
<script type='text/javascript'>
<?php if (!$stop) {?>
function addInput(form, name, value) {
	var input = document.createElement("INPUT");
	input.type = "hidden";
	input.name = name;
	input.value = value;
	form.appendChild(input);
}
var form = document.createElement("FORM");
form.method = "POST";
<?php
for ($i = 0; $i <= $step; $i++) echo "addInput(form,'step_result_".$i."',".json_encode($_POST["step_result_".$i]).");";
echo "addInput(form,'step',".($step+1).");";
?>
document.body.appendChild(form);
form.submit();
<?php } ?>
</script>
</body>
</html>