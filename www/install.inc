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
function connectDB() {
	require_once("DataBaseSystem_".$_POST["db_type"].".inc");
	$db_system_class = "DataBaseSystem_".$_POST["db_type"];
	/* @var $db_system DataBaseSystem */
	$db_system = new $db_system_class;
	$res = $db_system->connect($_POST["db_server"], $_POST["db_user"], $_POST["db_password"]);
	if ($res <> DataBaseSystem::ERR_OK) {
		header("HTTP/1.0 200 Error");
		switch ($res) {
			case DataBaseSystem::ERR_CANNOT_CONNECT_TO_SERVER: die("Unable to connect to the database server");
			case DataBaseSystem::ERR_INVALID_CREDENTIALS: die("Invalid credentials to connect to the database server");
			default: die("Unknown result when connecting to the database server");
		}
	}
	return $db_system;
}
if (isset($_POST["req"])) {
	switch ($_POST["req"]) {
		case "check_db":
			$dir = opendir("data");
			while (($file = readdir($dir)) <> null) {
				if ($file == "." || $file == ".." || $file == "init") continue;
				if (is_dir("data/$file")) removeDirectory("data/$file");
				else unlink("data/$file");
			}
			closedir($dir);
			connectDB();
			die("OK");		
		case "create_db":
			global $local_domain, $db_config;
			$local_domain = $_POST["domain"];
			$db_config = array(
					"type"=>$_POST["db_type"],
					"server"=>$_POST["db_server"],
					"user"=>$_POST["db_user"],
					"password"=>$_POST["db_password"],
			);
			$domain = $_POST["domain"];
			require_once("component/PNApplication.inc");
			PNApplication::$instance = new PNApplication();
			PNApplication::$instance->local_domain = $domain;
			PNApplication::$instance->current_domain = $domain;
			PNApplication::$instance->init();
			$db = connectDB();
			set_time_limit(240);
			require_once("SQLQuery.inc");
			require_once("component/data_model/Model.inc");
			$model = DataModel::get();
			$res = $db->execute("CREATE DATABASE IF NOT EXISTS students_".$domain." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",false);
			$res = $db->execute("SELECT concat('DROP TABLE IF EXISTS ', table_name, ';') FROM information_schema.tables WHERE table_schema = 'students_$domain'");
			$db->execute("USE students_".$domain);
			while (($sql = $db->nextRowArray($res)) <> null)
				$db->execute($sql[0]);
			require_once("component/data_model/DataBaseUtilities.inc");
			$ref = new ReflectionClass("DataModel");
			$p = $ref->getProperty("tables");
			$p->setAccessible(true);
			$tables = $p->getValue($model);
			foreach ($tables as $table)
				DataBaseUtilities::createTable($db, $table);
			if (PNApplication::hasErrors()) {
				header("HTTP/1.0 200 Error");
				PNApplication::printErrors();
				die();
			}
			die("OK");
		case "init_db":
			global $local_domain, $db_config;
			$local_domain = $_POST["domain"];
			$db_config = array(
				"type"=>$_POST["db_type"],
				"server"=>$_POST["db_server"],
				"user"=>$_POST["db_user"],
				"password"=>$_POST["db_password"],
			);
			$domain = $_POST["domain"];
			set_time_limit(240);
			require_once("component/PNApplication.inc");
			require_once("SQLQuery.inc");
			require_once("component/data_model/Model.inc");
			PNApplication::$instance = new PNApplication();
			PNApplication::$instance->local_domain = $domain;
			PNApplication::$instance->current_domain = $domain;
			PNApplication::$instance->init();
			$components = PNApplication::sortComponentsByDependencies();
			foreach ($components as $c) {
				if (file_exists("data/init/".$c->name."/init_data.inc"))
					include("data/init/".$c->name."/init_data.inc");
			}
			if (PNApplication::hasErrors()) {
				header("HTTP/1.0 200 Error");
				PNApplication::printErrors();
				die();
			}
			die("OK");
		case "save":
			// remove installer
			if (file_exists("installer")) removeDirectory(realpath("installer"));
			// remove all sessions
			$sessions_path = ini_get("session.save_path");
			$i = strrpos($sessions_path, ";");
			if ($i !== false) $sessions_path = substr($sessions_path, $i+1);
			$sessions_path = realpath($sessions_path);
			$sessions = array();
			$dir = opendir($sessions_path);
			while (($filename = readdir($dir)) <> null) {
				if (is_dir($filename)) continue;
				$content = @file_get_contents($sessions_path."/".$filename);
				if (strpos($content,"PNApplication") !== false) {
					@unlink($sessions_path."/".$filename);
					if (file_exists($sessions_path."/".$filename)) $remaining++;
				}
			}
			closedir($dir);
			// generate configuration
			$f = fopen(dirname(__FILE__)."/install_config.inc", "w");
			fwrite($f,
				"<?php \n".
				"global \$local_domain, \$db_config;\n".
				"\$local_domain = \"".$_POST["local_domain"]."\";\n".
				"\$db_config = array(\n".
				"\t\"type\"=>\"".$_POST["db_type"]."\",\n".
				"\t\"server\"=>\"".$_POST["db_server"]."\",\n".
				"\t\"user\"=>\"".$_POST["db_user"]."\",\n".
				"\t\"password\"=>\"".$_POST["db_password"]."\",\n".
				");\n".
				"?>"
			);
			fclose($f);
			if (!empty($_POST["proxy_server"])) {
				$f = fopen("conf/proxy","w");
				fwrite($f, "<?php ");
				fwrite($f, "curl_setopt(\$c, CURLOPT_PROXY, ".json_encode($_POST["proxy_server"].":".$_POST["proxy_port"]).");");
				if (isset($_POST["proxy_user"]))
					fwrite($f, "curl_setopt(\$c, CURLOPT_PROXYUSERPWD, ".json_encode(str_replace(":","\\:",$_POST["proxy_user"]).":".str_replace(":","\\:",$_POST["proxy_pass"])).");");
				fwrite($f, "?>");
				fclose($f);
			} else
				@unlink("conf/proxy");
			die("OK");
		default:
			header("HTTP/1.0 200 Error");
			die("Invalid request");
	}
} 
?><html>
<head>
<title>Installation</title>
<style type="text/css">
html, body, table, input, select {
	font-family: Arial, Tahoma, Verdana;
	font-size: 10pt;
}
.install_table {
	border: 1px solid #22bbea;
	border-spacing: 0;
	box-shadow: 0 0 5px 3px #A0A0A0;
	border-radius: 10px;
	background: linear-gradient(to bottom, #FFFFFF 0%, #FFFFFF 30%, #E0E0E0 100%);
}
.install_table td.header {
	background-color: #22bbea;
	padding: 2px 10px 2px 10px;
	font-size: 12pt;
	font-weight: bold;
	text-align: center;
	color: white;
	border-radius: 8px 8px 0px 0px;
}
.install_table td {
	padding: 2px 5px;
}
.install_table input, .install_table select {
	border: 1px solid #808080;
}
.install_table td.section_title {
	padding: 3px;
	border-top: 1px solid #22bbea;
	border-bottom: 1px solid #22bbea;
	font-weight: bold;
	text-align: center;
}
ul {
	margin-top: 0px;
}
</style>
</head>
<body style="background-color:#E8E8E8;height:100%;">
<div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;">
	<form name='config' method="POST" onsubmit="checkDB();return false;">
	<table class='install_table' id='install_table'>
		<tr>
			<td colspan=3 class='header'>Students Management Software - Installation</td>
		</tr>
		<?php if (!isset($_POST["local_domain"])) { ?>
		<tr>
			<td colspan=3>
				What is your local domain ?
				<select name='local_domain'>
					<?php
					$domains = include("conf/domains");
					foreach ($domains as $domain=>$conf)
						echo "<option value='$domain'>$domain</option>"; 
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan=3 class='section_title'>
				Database Configuration
			</td>
		</tr>
		<tr>
			<td>Server type</td>
			<td align="left">
				<select name='db_type'>
					<option value='MySQL'>MySQL</option>
				</select>
			</td>
			<td><div style="width:200px"></div></td>
		</tr>
		<tr>
			<td>Server IP/Hostname</td>
			<td align="left"><input type='text' name='db_server' value='localhost'/></td>
			<td></td>
		</tr>
		<tr>
			<td>Username</td>
			<td align="left"><input type='text' name='db_user'/></td>
			<td></td>
		</tr>
		<tr>
			<td>Password</td>
			<td align="left"><input type='password' name='db_password'/></td>
			<td></td>
		</tr>
		<tr>
			<td colspan=3>
				For the database, you have 2 options:<ul>
					<li>Specify a user having enough privileges to create and remove databases<br/>
						<i>Typically for development environment, not production</i>
					</li>
					<li>Specify a dedicated user for the software. In this case, you must first:<ul>
						<li>Create the databases for each domain (students_PNC,students_PNP,students_PNV)</li>
						<li>The databases must use UTF8</li>
					</ul></li>
				</ul>
			</td>
		</tr>
		<tr>
			<td colspan=3 class='section_title'>
				Proxy Settings
			</td>
		</tr>
		<tr>
			<td>Server</td>
			<td><input type="text" name="proxy_server"/></td>
			<td></td>
		</tr>
		<tr>
			<td>Port</td>
			<td><input type="text" name="proxy_port"/></td>
			<td></td>
		</tr>
		<tr>
			<td colspan=3><input type='checkbox' name='proxy_auth' onchange="this.form.elements['proxy_user'].disabled = this.checked ? '' : 'disabled';this.form.elements['proxy_pass'].disabled = this.checked ? '' : 'disabled';"/> Needs authentication</td>
		</tr>
		<tr>
			<td>Username</td>
			<td><input type="text" name="proxy_user" disabled='disabled'/></td>
			<td></td>
		</tr>
		<tr>
			<td>Password</td>
			<td><input type="text" name="proxy_pass" disabled='disabled'/></td>
			<td></td>
		</tr>
		<tr>
			<td colspan=3 align=center>
				<input type='submit' value='Install'/>
			</td>
		</tr>
		<?php } else { ?>
		<tr>
			<td colspan=3 id='progress'>
			</td>
		</tr>
		<?php } ?>
	</table>
	</form>
</div>
<script type='text/javascript'>
var db_type, db_server, db_user, db_password, proxy_server, proxy_port, proxy_user, proxy_pass;
function request(params, handler) {
	var xhr = new XMLHttpRequest();
	xhr.open("POST","?", true);
	xhr.onreadystatechange = function() {
	    if (this.readyState != 4) return;
		if (this.statusText == "Error")
			handler(true,xhr.responseText);
		else if (xhr.responseText != "OK")
				handler(true, "Unexpected answer: "+xhr.responseText);
			else
				handler(false,xhr.responseText);
	};
	xhr.setRequestHeader('Content-type', "application/x-www-form-urlencoded");
	params += "&db_type="+encodeURIComponent(db_type);
	params += "&db_server="+encodeURIComponent(db_server);
	params += "&db_user="+encodeURIComponent(db_user);
	params += "&db_password="+encodeURIComponent(db_password);
	params += "&proxy_server="+encodeURIComponent(proxy_server);
	params += "&proxy_port="+encodeURIComponent(proxy_port);
	if (proxy_user) {
		params += "&proxy_user="+encodeURIComponent(proxy_user);
		params += "&proxy_pass="+encodeURIComponent(proxy_pass);
	}
	xhr.send(params);
}
<?php if (!isset($_POST["local_domain"])) { ?>
var message = null;
function checkDB() {
	var form = document.forms['config'];
	db_type = form.elements['db_type'].value;
	db_server = form.elements['db_server'].value;
	db_user = form.elements['db_user'].value;
	db_password = form.elements['db_password'].value;
	proxy_server = form.elements['proxy_server'].value;
	proxy_port = form.elements['proxy_port'].value;
	if (form.elements['proxy_auth'].checked) {
		proxy_user = form.elements['proxy_user'].value;
		proxy_pass = form.elements['proxy_pass'].value;
	} else {
		proxy_user = null;
		proxy_pass = null;
	}
	var table = document.getElementById('install_table');
	if (!message) {
		message = document.createElement("DIV");
		table.parentNode.insertBefore(message, table);
	}
	message.style.color = "black";
	message.innerHTML = "Checking Database connection...";
	table.style.display = "none";
	request("req=check_db",function(error,res) {
		if (error) {
			message.innerHTML = "Unable to connect to the Database: "+res;
			message.style.color = "red";
			table.style.display = "";
			return;
		}
		form.onsubmit = null;
		form.submit();
	});
}
<?php } else { ?>
db_type = <?php echo json_encode($_POST["db_type"]);?>;
db_server = <?php echo json_encode($_POST["db_server"]);?>;
db_user = <?php echo json_encode($_POST["db_user"]);?>;
db_password = <?php echo json_encode($_POST["db_password"]);?>;
proxy_server = <?php echo json_encode($_POST["proxy_server"]);?>;
proxy_port = <?php echo json_encode($_POST["proxy_port"]);?>;
proxy_user = <?php echo isset($_POST["proxy_user"]) ? json_encode($_POST["proxy_user"]) : "null";?>;
proxy_pass = <?php echo isset($_POST["proxy_user"]) ? json_encode($_POST["proxy_pass"]) : "null";?>;
var icon_ok = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABlklEQVR42r2Sz0oCURTGz/0zmZYmRRFSgZsiCDJoUzuhRbXJdi19gCChB9Bor0QP4CPUrkUL3Ri1CIV24kKiRULUlDZlM/fezjgaGSgTQQeGO8M5v+87Z+4h8Mcg/y8Q1YIaVwnfhNyzGhD8lQBb43HfmMwMz8ggQfLhhp8SVExgropPHnKm3svV45VZ/7SMaX4FygJ4qbKS8UyjJLBDlXdcQvOJ6q81emSeW6kfcMQXQHhKRIDitwCo3yFcp1HbkPANnhidFxnmVSAMgMYtJh+dpO3sHZE5fwhhDCUB3mtUr+vMzpe+fuLAFs8Gola8VWQCGGWqv1XYtieE886JiFKYQNi8J1AvcxvOd98COg0uipxnWUbsQrvNjwoFPimBaA6ssDujwA/ESfeI5Pusnk1R5LOy1Sq0XTunecWqZlqE++4BifGUtiCTsC4dEBwBdUbBvGRdrfdcJL7PmmpXDHQVHTPDSoshd5uIo/AVcS2SkraW55CCVWBhdK+6E+iIrIqi/WpdsKXOlbkXaIu0zj5wfwGX8QnItKrE+XQVRgAAAABJRU5ErkJggg==";
var icon_error = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAbwAAAG8B8aLcQwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAJqSURBVDjLfZPLT+JQGMW7af8Xlv0PXGjcuDBRE3WhxqQKlAgYqfgIQi6xPhCBFAYoA4jzWszWmDDBTDKrcXRh6kID0bjwNTMZdXx0ZjZnbtsMBoNzk1/a7/acc7+29zIAmKesr6+zpVKJX1tbE4rFolAoFHgK20zbUFADRw3kVaGgbyaT2InH8SUWw0YigaKq6tlslmQyGa5pAF3Bls/ntU8rK/iTzQL5fAPG3MflZSSTSU1RFFtDQC6X41RV1WoLC4Ci/JeqLCMej2uxWIyrB6TTaVIxzIuL+JVK4WJnB/eqatYGD+m0OfeQyZh1mRBEIhFiBtCW2JSi6PrcHEC53t6GMW5OTnBH3/1hdRVXtZo5d727a2p+BwKIyLIuyzLLJBIJ/h1tCxMTFsEgcHZmGY6O8OPw0LzH5SUQCtV1JRoSDod5JhqNChW/H3A4HpEk4PQU9XFxAUxONmjKNCQYDArM0tKS8MHnAwYH69zShzcHB3X/z2oVt080m14vZmdnBWZ+fp5/OTMDdHUB3d24EkV83duznOfnAP0Wxvi+v48bl8vUGNoXNMDv9/MMIYQNh0L6XXs70NqKbxsbltl4hYEBoL8fOD62QsplU3Pf1oYZSdJ9Ph9r/sZAIEDeOp1ASwvQ0QFUKkBPj1UbdHYCW1vWldavBQHj4+Okvg+mp6e5qakpbbuv79H0DJ97e+F2u7WxsTGuYStLkmSjLWlvRkZwT9t8ajTmSsPDcLlcmiiKtqaHyev1ch6Ph0y43fqq3Y73Q0MmEdqyRxR1p9NJHA4H9+xp/AddgaXw1CBQg2C32/nR0dGmx/kvBdTCbxuFDrgAAAAASUVORK5CYII=";
var icon_loading = "data:image/gif;base64,R0lGODlhEAAQAPQAAP///2t+v/r6/IuazLfA326BwICRyObp9MzS6HeJxK+53Kax2O7w98LK5N3h8JSi0Jyp1AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAAFUCAgjmRpnqUwFGwhKoRgqq2YFMaRGjWA8AbZiIBbjQQ8AmmFUJEQhQGJhaKOrCksgEla+KIkYvC6SJKQOISoNSYdeIk1ayA8ExTyeR3F749CACH5BAkKAAAALAAAAAAQABAAAAVoICCKR9KMaCoaxeCoqEAkRX3AwMHWxQIIjJSAZWgUEgzBwCBAEQpMwIDwY1FHgwJCtOW2UDWYIDyqNVVkUbYr6CK+o2eUMKgWrqKhj0FrEM8jQQALPFA3MAc8CQSAMA5ZBjgqDQmHIyEAIfkECQoAAAAsAAAAABAAEAAABWAgII4j85Ao2hRIKgrEUBQJLaSHMe8zgQo6Q8sxS7RIhILhBkgumCTZsXkACBC+0cwF2GoLLoFXREDcDlkAojBICRaFLDCOQtQKjmsQSubtDFU/NXcDBHwkaw1cKQ8MiyEAIfkECQoAAAAsAAAAABAAEAAABVIgII5kaZ6AIJQCMRTFQKiDQx4GrBfGa4uCnAEhQuRgPwCBtwK+kCNFgjh6QlFYgGO7baJ2CxIioSDpwqNggWCGDVVGphly3BkOpXDrKfNm/4AhACH5BAkKAAAALAAAAAAQABAAAAVgICCOZGmeqEAMRTEQwskYbV0Yx7kYSIzQhtgoBxCKBDQCIOcoLBimRiFhSABYU5gIgW01pLUBYkRItAYAqrlhYiwKjiWAcDMWY8QjsCf4DewiBzQ2N1AmKlgvgCiMjSQhACH5BAkKAAAALAAAAAAQABAAAAVfICCOZGmeqEgUxUAIpkA0AMKyxkEiSZEIsJqhYAg+boUFSTAkiBiNHks3sg1ILAfBiS10gyqCg0UaFBCkwy3RYKiIYMAC+RAxiQgYsJdAjw5DN2gILzEEZgVcKYuMJiEAOwAAAAAAAAAAAA==";

var progress = document.getElementById('progress');
var errors = false;
var done = 0;
function createDomainDB(domain) {
	var div = document.createElement("DIV");
	progress.appendChild(div);
	div.innerHTML = "Initialization of domain "+domain;
	var img = document.createElement("IMG");
	img.style.marginLeft = "5px";
	div.appendChild(img);
	img.src = icon_loading;
	request("req=create_db&domain="+domain,function(error,res) {
		if (error) {
			img.src = icon_error;
			div.appendChild(document.createTextNode(res));
			errors = true;
			done++;
			checkEnd();
			return;
		}
		img.src = icon_ok;
		var sub_div = document.createElement("DIV");
		sub_div.style.marginLeft = "20px";
		sub_div.innerHTML = "Inserting initial data into domain "+domain;
		div.appendChild(sub_div);
		img = document.createElement("IMG");
		img.style.marginLeft = "5px";
		sub_div.appendChild(img);
		img.src = icon_loading;
		request("req=init_db&domain="+domain,function(error,res) {
			if (error) {
				img.src = icon_error;
				sub_div.appendChild(document.createTextNode(res));
				errors = true;
				done++;
				checkEnd();
				return;
			}
			img.src = icon_ok;
			done++;
			checkEnd();
		});
	});
}
<?php $domains = include("conf/domains"); ?>
function checkEnd() {
	if (done == <?php echo count($domains); ?>) {
		if (errors) {
			var tr = document.createElement("TR");
			var td = document.createElement("TD");
			td.colSpan = 3;
			tr.appendChild(td);
			var button = document.createElement("BUTTON");
			button.innerHTML = "Retry installation";
			td.appendChild(button);
			progress.parentNode.parentNode.appendChild(tr);
			button.onclick = function() {
				window.location.assign("/");
			};
			return;
		}
		var div = document.createElement("DIV");
		progress.appendChild(div);
		div.innerHTML = "Saving configuration";
		var img = document.createElement("IMG");
		img.style.marginLeft = "5px";
		div.appendChild(img);
		img.src = icon_loading;
		var req = "req=save&local_domain=<?php echo $_POST["local_domain"];?>";
		if (proxy_server.trim().length > 0) {
			req += "&proxy_server="+encodeURIComponent(proxy_server);
			req += "&proxy_port="+encodeURIComponent(proxy_port);
			if (proxy_user) {
				req += "&proxy_user="+encodeURIComponent(proxy_user);
				req += "&proxy_pass="+encodeURIComponent(proxy_pass);
			}
		}
		request(req,function(error,res) {
			if (error) {
				img.src = icon_error;
				div.appendChild(document.createTextNode(res));
				errors = true;
				checkEnd();
				return;
			}
			img.src = icon_ok;
			window.location.assign("/");
		});
	}
}
<?php foreach ($domains as $domain=>$conf) echo "createDomainDB(".json_encode($domain).");"; ?>
<?php } ?>
</script>
</body>
</html>