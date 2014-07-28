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
</style>
</head>
<body style="background-color:#E8E8E8;height:100%;">
<div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;">
<?php 
if (isset($_POST["development_mode"])) {
	$f = fopen(dirname(__FILE__)."/install_config.inc", "w");
	fwrite($f, 
		"<?php \n".
		"global \$development_mode, \$local_domain, \$db_config;\n".
		"\$development_mode = ".($_POST["development_mode"] == "on" ? "true" : "false").";\n".
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
				<iframe src='/dynamic/development/page/reset_db?domain=<?php echo $_POST["local_domain"];?>' style='width:500px;height:150px;'></iframe>
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
		<tr>
			<td colspan=2 class='header'>Students Management Software - Installation</td>
		</tr>
		<tr>
			<td colspan=2>
				<input type='checkbox' name='development_mode' checked='checked'/>
				This is a development installation
			</td>
		</tr>
		<tr>
			<td>What is your local domain ?</td>
			<td>
				<select name='local_domain'>
					<option value='PNC'>PNC</option>
					<option value='PNP'>PNP</option>
					<option value='PNV'>PNV</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>Database type</td>
			<td>
				<select name='db_type'>
					<option value='MySQL'>MySQL</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>Database server</td>
			<td><input type='text' name='db_server' value='localhost'/></td>
		</tr>
		<tr>
			<td>Database user</td>
			<td><input type='text' name='db_user' value='root'/></td>
		</tr>
		<tr>
			<td>Database password</td>
			<td><input type='password' name='db_password' value=''/></td>
		</tr>
		<tr>
			<td colspan=2 align=center>
				<input type='submit' value='Install'/>
			</td>
		</tr>
	</table>
	</form>
<?php } ?>
</div>
</body>
</html>