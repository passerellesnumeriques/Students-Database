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

if (file_exists("installer")) {
	removeDirectory(realpath("installer"));
}
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
?>
<html>
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
	padding: 5px;
}
.install_table input, .install_table select {
	border: 1px solid #808080;
}
ul {
	margin-top: 0px;
}
</style>
</head>
<body style="background-color:#E8E8E8;height:100%;">
<div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;">
<?php 
if (isset($_POST["local_domain"])) {
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
?>
	<table class='install_table'>
		<tr>
			<td class='header'>Students Management Software - Installation</td>
		</tr>
		<tr>
			<td>
				Configuration done.
			</td>
		<tr>
			<td>
				Initialization of the database:<br/>
				<?php 
				$domains = include("conf/domains");
				foreach ($domains as $domain=>$conf) {
				echo $domain.":<br/>";
				?>
				<iframe src='/dynamic/development/page/reset_db?domain=<?php echo $domain;?>' style='width:500px;height:140px;'></iframe>
				<br/>
				<?php }?>
			</td>
		</tr>
		<tr>
			<td>Once database is initialized, you can <a href='/'>Enter the application</a></td>
		</tr>
	</table>
<?php 
} else {
?>
	<form method='post'>
	<table class='install_table'>
	<colgroup><col style='width:180px'/><col style='width:170px'/><col style='width:150px'/></colgroup>
	<tbody>
		<tr>
			<td colspan=3 class='header'>Students Management Software - Installation of Development version</td>
		</tr>
		<tr>
			<td>What is your local domain ?</td>
			<td align="left">
				<select name='local_domain'>
					<option value='PNC'>PNC</option>
					<option value='PNP'>PNP</option>
					<option value='PNV'>PNV</option>
				</select>
			</td>
			<td></td>
		</tr>
		<tr>
			<td>Database type</td>
			<td align="left">
				<select name='db_type'>
					<option value='MySQL'>MySQL</option>
				</select>
			</td>
			<td></td>
		</tr>
		<tr>
			<td>Database server</td>
			<td align="left"><input type='text' name='db_server' value='localhost'/></td>
			<td></td>
		</tr>
		<tr>
			<td>Database user</td>
			<td align="left"><input type='text' name='db_user' value='root'/></td>
			<td></td>
		</tr>
		<tr>
			<td>Database password</td>
			<td align="left"><input type='password' name='db_password' value=''/></td>
			<td></td>
		</tr>
		<tr>
			<td colspan=3>
				For the database, you have 2 options:<ul>
					<li>Specify a user having enough privileges to create and remove databases<br/>
						<i>Typically for development environment, not production</i>
					</li>
					<li>Specify a dedicated user for the software. In this case, you must first:<ul>
						<li>Create the databases for each domain (students_pnc,students_pnp,students_pnv)</li>
						<li>The databases must use UTF8</li>
					</ul></li>
				</ul>
			</td>
		</tr>
		<tr>
			<td colspan=3 align=center>
				<input type='submit' value='Install'/>
			</td>
		</tr>
	</tbody></table>
	</form>
<?php } ?>
</div>
</body>
</html>