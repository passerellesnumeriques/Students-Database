<?php
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
function iniSize($s) {
	$i = strpos($s,"K");
	if ($i !== false) return "".(intval(substr($s,0,$i))*1024);
	$i = strpos($s,"M");
	if ($i !== false) return "".(intval(substr($s,0,$i))*1024*1024);
	$i = strpos($s,"G");
	if ($i !== false) return "".(intval(substr($s,0,$i))*1024*1024*1024);
	return $s;
}

if (isset($_POST["step"])) {
	switch ($_POST["step"]) {
		case "phpversion": die(phpversion());
		case "apacheversion": die(@$_SERVER["SERVER_SOFTWARE"]);
		case "phpext":
			$list = get_loaded_extensions();
			foreach ($list as $ext) if ($ext == $_POST["ext"]) die("OK");
			die("KO");
		case "phpconfig_memory":
			$mem = iniSize(ini_get("memory_limit"));
			die($mem);
		case "phpconfig_post_size":
			$size = iniSize(ini_get("post_max_size"));
			die($size);
		case "phpconfig_upload_max_filesize":
			$size = iniSize(ini_get("upload_max_filesize"));
			die($size);
		case "phpconfig_max_file_uploads":
			$size = iniSize(ini_get("max_file_uploads"));
			die($size);
		case "phpconfig_session_autostart":
			if (ini_get("session.auto_start") <> "0") die("KO");
			die("OK");
		case "checkfs":
			$path = realpath(dirname(__FILE__));
			$f = @fopen($path."/test.tmp","w");
			if ($f == null) die("Unable to create a file, please give the right to create files in the directory ".htmlentities($path));
			fclose($f);
			@unlink($path."/test.tmp");
			if (file_exists($path."/test.tmp")) die("Unable to remove our temporary file, please give the right to remove files in the directory ".htmlentities($path));
			@mkdir($path."/tmp");
			if (!file_exists($path."/tmp") || !is_dir($path."/tmp")) die("Unable to create a directory, please give the right to create sub-directories into ".htmlentities($path));
			@rmdir($path."/tmp");
			if (file_exists($path."/tmp")) die("Unable to remove our temporary sub-directory, please give the right to remove sub-directories into ".htmlentities($path));
			die("OK");
		case "checkzip":
			if (class_exists("ZipArchive")) die("OK");
			if (file_exists("/usr/bin/unzip")) die("OK");
			die("Unable to find Zip functionalities on the server");
		case "phpsessions":
			$sessions_path = ini_get("session.save_path");
			$i = strrpos($sessions_path, ";");
			if ($i !== false) $sessions_path = substr($sessions_path, $i+1);
			$sessions_path = realpath($sessions_path);
			$dir = @opendir($sessions_path);
			if ($dir == null) die("We cannot access to the PHP sessions directory.");
			closedir($dir);
			$f = @fopen($sessions_path."/tmp","w");
			if ($f == null) die("We cannot write in the PHP sessions directory.");
			fclose($f);
			unlink($sessions_path."/tmp");
			die("OK");
		case "internet":
			$c = curl_init("http://www.google.com/");
			if (isset($_POST["proxy_server"]))
				curl_setopt($c, CURLOPT_PROXY, $_POST["proxy_server"].":".$_POST["proxy_port"]);
			if (isset($_POST["proxy_user"]))
				curl_setopt($c, CURLOPT_PROXYUSERPWD, str_replace(":","\\:",str_replace("\\","\\\\",$_POST["proxy_user"])).":".str_replace(":","\\:",str_replace("\\","\\\\",$_POST["proxy_pass"])));
			curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($c, CURLOPT_TIMEOUT, 30);
			set_time_limit(45);
			$result = curl_exec($c);
			if ($result === false) {
				$err = "Error connecting to Internet (".curl_errno($c)."): ".curl_error($c);
				curl_close($c);
				die($err);
			}
			curl_close($c);
			die("OK");
		case "init_download":
			if (file_exists("installer")) removeDirectory(realpath(dirname(__FILE__))."/installer");
			mkdir("installer");
			mkdir("installer/conf");
			if (isset($_POST["proxy_server"])) {
				$f = fopen("installer/conf/proxy","w");
				fwrite($f, "<?php ");
				fwrite($f, "curl_setopt(\$c, CURLOPT_PROXY, ".json_encode($_POST["proxy_server"].":".$_POST["proxy_port"]).");");
				if (isset($_POST["proxy_user"]))
					fwrite($f, "curl_setopt(\$c, CURLOPT_PROXYUSERPWD, ".json_encode(str_replace(":","\\:",$_POST["proxy_user"]).":".str_replace(":","\\:",$_POST["proxy_pass"])).");");
				fwrite($f, "?>");
				fclose($f);
			}
			die();
		case "download":
			$c = curl_init($_POST["url"]);
			if (file_exists("installer/conf/proxy")) include("installer/conf/proxy");
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
				die($err);
			}
			curl_close($c);
			$f = fopen("installer/".$_POST["file"],"w");
			fwrite($f,$result);
			fclose($f);
			die();
	}
}
?><html>
<head>
<title>PN Students Management Software - Installation</title>
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
		</div>
	</div>
</div>
<script type='text/javascript'>
var content = document.getElementById('content');
var icon_ok = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABlklEQVR42r2Sz0oCURTGz/0zmZYmRRFSgZsiCDJoUzuhRbXJdi19gCChB9Bor0QP4CPUrkUL3Ri1CIV24kKiRULUlDZlM/fezjgaGSgTQQeGO8M5v+87Z+4h8Mcg/y8Q1YIaVwnfhNyzGhD8lQBb43HfmMwMz8ggQfLhhp8SVExgropPHnKm3svV45VZ/7SMaX4FygJ4qbKS8UyjJLBDlXdcQvOJ6q81emSeW6kfcMQXQHhKRIDitwCo3yFcp1HbkPANnhidFxnmVSAMgMYtJh+dpO3sHZE5fwhhDCUB3mtUr+vMzpe+fuLAFs8Gola8VWQCGGWqv1XYtieE886JiFKYQNi8J1AvcxvOd98COg0uipxnWUbsQrvNjwoFPimBaA6ssDujwA/ESfeI5Pusnk1R5LOy1Sq0XTunecWqZlqE++4BifGUtiCTsC4dEBwBdUbBvGRdrfdcJL7PmmpXDHQVHTPDSoshd5uIo/AVcS2SkraW55CCVWBhdK+6E+iIrIqi/WpdsKXOlbkXaIu0zj5wfwGX8QnItKrE+XQVRgAAAABJRU5ErkJggg==";
var icon_error = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAbwAAAG8B8aLcQwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAJqSURBVDjLfZPLT+JQGMW7af8Xlv0PXGjcuDBRE3WhxqQKlAgYqfgIQi6xPhCBFAYoA4jzWszWmDDBTDKrcXRh6kID0bjwNTMZdXx0ZjZnbtsMBoNzk1/a7/acc7+29zIAmKesr6+zpVKJX1tbE4rFolAoFHgK20zbUFADRw3kVaGgbyaT2InH8SUWw0YigaKq6tlslmQyGa5pAF3Bls/ntU8rK/iTzQL5fAPG3MflZSSTSU1RFFtDQC6X41RV1WoLC4Ci/JeqLCMej2uxWIyrB6TTaVIxzIuL+JVK4WJnB/eqatYGD+m0OfeQyZh1mRBEIhFiBtCW2JSi6PrcHEC53t6GMW5OTnBH3/1hdRVXtZo5d727a2p+BwKIyLIuyzLLJBIJ/h1tCxMTFsEgcHZmGY6O8OPw0LzH5SUQCtV1JRoSDod5JhqNChW/H3A4HpEk4PQU9XFxAUxONmjKNCQYDArM0tKS8MHnAwYH69zShzcHB3X/z2oVt080m14vZmdnBWZ+fp5/OTMDdHUB3d24EkV83duznOfnAP0Wxvi+v48bl8vUGNoXNMDv9/MMIYQNh0L6XXs70NqKbxsbltl4hYEBoL8fOD62QsplU3Pf1oYZSdJ9Ph9r/sZAIEDeOp1ASwvQ0QFUKkBPj1UbdHYCW1vWldavBQHj4+Okvg+mp6e5qakpbbuv79H0DJ97e+F2u7WxsTGuYStLkmSjLWlvRkZwT9t8ajTmSsPDcLlcmiiKtqaHyev1ch6Ph0y43fqq3Y73Q0MmEdqyRxR1p9NJHA4H9+xp/AddgaXw1CBQg2C32/nR0dGmx/kvBdTCbxuFDrgAAAAASUVORK5CYII=";
var icon_warning = "data:image/png;base64,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";
var has_errors = false;

function addText(text) {
	content.appendChild(document.createTextNode(text));
}
function addResult(icon,text,nobr) {
	var img = document.createElement("IMG");
	img.src = icon;
	img.style.marginLeft = "5px";
	content.appendChild(img);
	if (text) addText(text);
	if (!nobr) content.appendChild(document.createElement("BR"));
}
function addOk(text,nobr) { addResult(icon_ok, text,nobr); }
function addWarning(text,nobr) { addResult(icon_warning, text,nobr); }
function addError(text,nobr) { addResult(icon_error, text,nobr); has_errors = true; }

function request(step,params,handler) {
	var xhr = new XMLHttpRequest();
	xhr.open("POST","index.php", true);
	xhr.onreadystatechange = function() {
	    if (this.readyState != 4) return;
		handler(xhr.responseText);
	};
	xhr.setRequestHeader('Content-type', "application/x-www-form-urlencoded");
	var data = "step="+encodeURIComponent(step)+params;
	xhr.send(data);
}
function checkPHPVersion() {
	addText("Checking PHP Version");
	request("phpversion","",function(res) {
		var s = res.split(".");
		var main = parseInt(s[0]);
		if (main < 5 || (main == 5 && (s.length == 1 || parseInt(s[1]) < 3))) addError("Too old version ("+res+"), please install at least version 5.3");
		else if (main == 5 && s.length > 1 && parseInt(s[1]) < 4) addWarning("Version "+res+" found. We recommend at least version 5.4, but it can work starting from version 5.3");
		else addOk("Version "+res);
		checkApacheVersion();
	});
}
function checkApacheVersion() {
	addText("Checking Apache Version");
	request("apacheversion","",function(res) {
		if (res.length == 0) addError("Unable to determine which Web Server is used");
		else {
			var i = res.indexOf('/');
			if (i < 0) {
				if (res == "Apache") {
					addWarning("Your server is Apache, but we cannot check which version. Please check your Apache server is at least the version 2.2");
					checkPHPExtensions();
				} else
					addError("Unable to determine which Web Server is used, the server said: "+res);
			} else {
				var server = res.substring(0,i);
				var version = res.substring(i+1);
				if (server != "Apache") addError("This Web Server is not Apache: "+server+" found");
				else {
					var s = version.split(".");
					var main = parseInt(s[0]);
					if (main < 2 || (main == 2 && (s.length == 1 || parseInt(s[1]) < 2))) addError("Too old version ("+version+"), please install at least version 2.2");
					else if (main == 2 && s.length > 1 && parseInt(s[1]) < 4) addWarning("Version "+version+" found. We recommend at least version 2.4, but it can work starting from version 2.2");
					else addOk("Version "+version);
					checkPHPExtensions();
				}
			}
		}
	});
}
function checkPHPExtensions() {
	addText("Checking PHP Extensions: curl");
	request("phpext","&ext=curl",function(res) {
		if (res == "OK") addOk(null,true); else addError(null,true);
		addText(" gd");
		request("phpext","&ext=gd",function(res) {
			if (res == "OK") addOk(null,true); else addError(null,true);
			addText(" mbstring");
			request("phpext","&ext=mbstring",function(res) {
				if (res == "OK") addOk(null,true); else addError(null,true);
				addText(" mysqli");
				request("phpext","&ext=mysqli",function(res) {
					if (res == "OK") addOk(null,false); else addError(null,false);
					checkPHPConfig();
				});
			});
		});
	});
}
function checkPHPConfig() {
	addText("Checking PHP Configuration");
	request("phpconfig_memory","",function(res) {
		var size;
		size = parseInt(res);
		addText(" memory_limit: ");
		if (size >= 250*1024*1024) addOk((size/(1024*1024))+"M",true);
		else if (size >= 64*1024*1024) addWarning((size/(1024*1024))+"M can be short to process Excel files (recommended is at least 128M, 256M is the best)",true);
		else addWarning((size/(1024*1024))+"M is too small, we won't be able to process most of the Excel files (recommended is at least 128M, 256M is the best)",true);
		request("phpconfig_post_size","",function(res) {
			size = parseInt(res);
			addText(" post_max_size: ");
			if (size >= 100*1024*1024) addOk((size/(1024*1024))+"M",true);
			else if (size >= 32*1024*1024) addWarning((size/(1024*1024))+"M can be short if the user wants to upload big files (recommended is at least 128M, 256M is the best)",true);
			else addWarning((size/(1024*1024))+"M is too small, it will be difficult for the user to upload some files (recommended is at least 128M, 256M is the best)",true);
			request("phpconfig_upload_max_filesize","",function(res) {
				size = parseInt(res);
				addText(" upload_max_filesize: ");
				if (size >= 100*1024*1024) addOk((size/(1024*1024))+"M",true);
				else if (size >= 32*1024*1024) addWarning((size/(1024*1024))+"M can be short if the user wants to upload big files (recommended is at least 128M, 256M is the best)",true);
				else addWarning((size/(1024*1024))+"M is too small, it will be difficult for the user to upload some files (recommended is at least 128M, 256M is the best)",true);
				request("phpconfig_max_file_uploads","",function(res) {
					size = parseInt(res);
					addText(" max_file_uploads: ");
					if (size >= 5) addOk(size,true);
					else addWarning(size+" is quite small, in case the user wants to upload a batch of files",true);
					request("phpconfig_session_autostart","",function(res) {
						addText(" session.auto_start: ");
						if (res != "OK") addError("must be set to 0, we don't want always a session! especially it will not allow to use caching");
						else addOk("");
						checkFSAccess();
					});
				});
			});
		});
	});
}
function checkFSAccess() {
	addText("Checking file system access");
	request("checkfs","",function(res) {
		if (res == "OK") addOk("Access rights are correct"); else addError(res);
		checkZip();
	});
}
function checkZip() {
	addText("Checking zip functions");
	request("checkzip","",function(res) {
		if (res == "OK") addOk(); else addError(res);
		checkPHPSessions();
	});
}
function checkPHPSessions() {
	addText("Checking Access to PHP Sessions");
	request("phpsessions","",function(res) {
		if (res == "OK") addOk(); else addError(res);
		checkInternet();
	});
}
var proxy_config = null;
function checkInternet() {
	addText("Checking Internet Access");
	request("internet","",function(res) {
		if (res == "OK") {
			addOk();
			downloadInstaller();
		} else {
			var has_other_errors = has_errors;
			addError(res);
			end();
			content.appendChild(document.createTextNode("May be your server needs to go through a Proxy ?"));
			content.appendChild(document.createElement("BR"));
			content.appendChild(document.createTextNode("Proxy server"));
			var proxy_server = document.createElement("INPUT"); content.appendChild(proxy_server);
			proxy_server.type = 'text';
			content.appendChild(document.createTextNode(" Port"));
			var proxy_port = document.createElement("INPUT"); content.appendChild(proxy_port);
			proxy_port.type = 'text';
			proxy_port.size = 4;
			content.appendChild(document.createElement("BR"));
			var need_auth = document.createElement("INPUT"); content.appendChild(need_auth);
			need_auth.type = 'checkbox';
			content.appendChild(document.createTextNode("needs authentication: Username"));
			var proxy_user = document.createElement("INPUT"); content.appendChild(proxy_user);
			proxy_user.type = 'text';
			content.appendChild(document.createTextNode(" Password"));
			var proxy_pass = document.createElement("INPUT"); content.appendChild(proxy_pass);
			proxy_pass.type = 'password';
			content.appendChild(document.createElement("BR"));
			var button = document.createElement("BUTTON");
			button.innerHTML = "Try with proxy";
			content.appendChild(button);
			button.onclick = function() {
				content.appendChild(document.createTextNode(" Trying with proxy..."));
				request("internet","&proxy_server="+encodeURIComponent(proxy_server.value)+"&proxy_port="+encodeURIComponent(proxy_port.value)+(need_auth.checked ? "&proxy_user="+encodeURIComponent(proxy_user.value)+"&proxy_pass="+encodeURIComponent(proxy_pass.value) : ""),function(res) {
					if (res == "OK") {
						addOk();
						if (!has_other_errors) has_errors = false;
						proxy_config = { server: proxy_server.value, port:proxy_port.value };
						if (need_auth.checked) {
							proxy_config.user = proxy_user.value;
							proxy_config.pass = proxy_pass.value;
						}
						downloadInstaller();
					} else {
						addError("Still cannot access to Internet even through proxy: "+res);
					}
				});
			};
		}
	});
}
function downloadInstaller() {
	if (has_errors) { end(); return; }
	addText("Downloading installer");
	request("init_download",proxy_config ? "&proxy_server="+encodeURIComponent(proxy_config.server)+"&proxy_port="+encodeURIComponent(proxy_config.port)+(typeof proxy_config.user != 'undefined' ? "&proxy_user="+proxy_config.user+"&proxy_pass="+proxy_config.pass : "") : "",function(res) {
		var progress = document.createElement("SPAN");
		progress.style.marginLeft = "10px";
		progress.innerHTML = "0%";
		content.appendChild(progress);
		request("download","&url="+encodeURIComponent("https://github.com/passerellesnumeriques/Students-Database/raw/master/install/installer/installer.php")+"&file=installer.php",function(res) {
			if (res.length > 0) {
				content.removeChild(progress);
				addError(res);
				end();
				return;
			}
			progress.innerHTML = "16%";
			request("download","&url="+encodeURIComponent("https://github.com/passerellesnumeriques/Students-Database/raw/master/install/installer/bridge.php")+"&file=bridge.php",function(res) {
				if (res.length > 0) {
					content.removeChild(progress);
					addError(res);
					end();
					return;
				}
				progress.innerHTML = "33%";
				request("download","&url="+encodeURIComponent("https://github.com/passerellesnumeriques/Students-Database/raw/master/www/component/application/static/deploy_utils.js")+"&file=deploy_utils.js",function(res) {
					if (res.length > 0) {
						content.removeChild(progress);
						addError(res);
						end();
						return;
					}
					progress.innerHTML = "49%";
					request("download","&url="+encodeURIComponent("https://github.com/passerellesnumeriques/Students-Database/raw/master/www/component/application/service/deploy_utils.inc")+"&file=deploy_utils.inc",function(res) {
						if (res.length > 0) {
							content.removeChild(progress);
							addError(res);
							end();
							return;
						}
						progress.innerHTML = "66%";
						request("download","&url="+encodeURIComponent("https://github.com/passerellesnumeriques/Students-Database/raw/master/www/update_urls.inc")+"&file=update_urls.inc",function(res) {
							if (res.length > 0) {
								content.removeChild(progress);
								addError(res);
								end();
								return;
							}
							progress.innerHTML = "82%";
							request("download","&url="+encodeURIComponent("https://github.com/passerellesnumeriques/Students-Database/raw/master/www/conf/update_urls")+"&file=conf/update_urls",function(res) {
								if (res.length > 0) {
									content.removeChild(progress);
									addError(res);
									end();
									return;
								}
								content.removeChild(progress);
								addOk();
								end();
							});
						});
					});
				});
			});
		});
	});
}
function end() {
	var footer = document.getElementById('footer');
	var button;
	if (!footer) {
		footer = document.createElement("DIV");
		footer.id = "footer";
		var button = document.createElement("BUTTON");
		footer.appendChild(button);
		content.parentNode.appendChild(footer);
	} else
		button = footer.childNodes[0];
	if (has_errors) {
		button.innerHTML = "Restart checks";
		button.onclick = function() { location.reload(); };
	} else {
		button.innerHTML = "Continue installation";
		button.onclick = function() { location.href = 'installer/installer.php'; };
	}
}
checkPHPVersion();
</script>
</body>
</html>