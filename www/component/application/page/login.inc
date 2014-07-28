<?php
global $need_app_loading;
if (isset($_GET["destroy_session"])) {
	session_destroy();
}
$message = null;
if (isset($_POST["domain"]) && isset($_POST["username"]) && isset($_POST["password"])) {
	try {
		setcookie("domain",$_POST["domain"],time()+30*24*60*60,"/dynamic/application/page/enter");
		setcookie("username",$_POST["username"],time()+30*24*60*60,"/dynamic/application/page/enter");
		if (isset($_POST["tz_offset"]))
			PNApplication::$instance->timezone_offset = $_POST["tz_offset"];
		$error = PNApplication::$instance->user_management->login($_POST["domain"], $_POST["username"], $_POST["password"]);
		if ($error === null) {
			echo "<script type='text/javascript'>";
			echo "window.top.set_loading_message('Starting application...');";
			echo "window.top.pnapplication.logged_in = true;";
			echo "window.top.pnapplication.onlogin.fire();";
			echo "location.href = 'enter';";
			echo "</script>";
			return;
		} else
			$message = "Authentication failed: ".$error;
	} catch (Exception $e) {
		$message = "Internal error while authenticating";
		PNApplication::error($e);
	}
} else if (isset($_POST["message"]))
	$message = $_POST["message"];
	
$this->onload("window.top.pn_loading_end();");
?>
<form method="post" style="width:100%;height:100%" onsubmit="login();return false;" name="login_form">
<input type="hidden" name="tz_offset" id="timezone_input"/>
<table style="width:100%;height:100%"><tr><td valign=middle align=center>
<?php
if (isset($_GET["from"])) {
	switch ($_GET["from"]) {
	case "inactivity": echo "You have been automatically logged out because you were inactive more than 30 minutes."; break;
	}
}
if ($message <> null) {
	echo "<img src='".theme::$icons_16["error"]."' style='vertical-align:bottom'/> <span style='color:red'>".$message."</span>";
}
?>
<table id='login_table'>
	<tr>
		<td colspan=3 id='login_table_header' align=center>
			PN Students Management Software
		</td>
	</tr>
	<tr><td rowspan=5 id='login_table_logo'>
		<img src='/static/application/logo.png'/>
	</td></tr>
	<tr align=left>
		<td>Domain</td>
		<td>
			<select name="domain">
				<?php
				$def_domain = PNApplication::$instance->local_domain;
				if (isset($_COOKIE["domain"])) $def_domain = $_COOKIE["domain"];
				foreach (PNApplication::$instance->getDomains() as $domain=>$descr)
					echo "<option value=\"".$domain."\"".($def_domain==$domain ? " selected" : "").">".$domain."</option>";
				?>
			</select>
		</td>
	</tr>
	<tr align=left>
		<td>Username</td>
		<td><input type="text" size=25 maxlength=100 name="username" value='<?php if (isset($_COOKIE["username"])) echo $_COOKIE["username"];?>'/></td>
	</tr>
	<tr align=left>
		<td>Password</td>
		<td><input type="password" size=25 maxlength=100 name="password"/></td>
	</tr>
	<tr align=left>
		<td></td>
		<td>
			<button onclick="login();" style="padding:2px 5px">
				Login
				<img src='/static/application/login.gif' style='vertical-align:bottom'/>
			</button>
			<input type='submit' style='position:absolute;top:-10000px'/>
		</td>
	</tr>
	<?php if ($need_app_loading) {?>
	<tr>
		<td colspan=3 id='login_table_footer'>
		</td>
	</tr>
	<?php } ?>
</table>
<br/>
<a href="/dynamic/development/page/tools">Development tools</a>
<br/>
<a href="/dynamic/documentation/page/index">Technical Documentation</a>
<br/>
<a href="/dynamic/test/page/home">Tests</a>
<br/>
<a href="?destroy_session=true">Destroy session</a>
</td></tr></table>
</form>
<style type="text/css">
#login_table {
	border: 1px solid #22bbea;
	border-spacing: 0;
	background-color: white;
	box-shadow: 0 0 5px 3px #A0A0A0;
}
#login_table td#login_table_header {
	background-color: #22bbea;
	padding: 2px 10px 2px 10px;
	font-size: 12pt;
	font-weight: bold;
	color: white;
}
#login_table td#login_table_footer {
	border-top: 1px solid #a0d0f0;
	padding: 2px 10px 2px 10px;
	height: 10px;
	font-size: 8pt;
	text-align: center;
}
#login_table td {
	padding: 5px;
}
#login_table input, #login_table select {
	border: 1px solid #808080;
}
</style>
<script type='text/javascript'>
document.getElementById("timezone_input").value = -(new Date().getTimezoneOffset());
window.top.pnapplication.onlogout.fire();
setBorderRadius(document.getElementById('login_table'),10,10,10,10,10,10,10,10);
setBorderRadius(document.getElementById('login_table_header'),8,8,8,8,0,0,0,0);
setBorderRadius(document.getElementById('login_table_footer'),0,0,0,0,8,8,8,8);
setBackgroundGradient(document.getElementById('login_table'),"vertical",[{pos:0,color:'#FFFFFF'},{pos:30,color:'#FFFFFF'},{pos:100,color:'#E0E0E0'}]);
setBoxShadow(document.getElementById('login_table'), 0, 0, 5, 5, "#ffffff");
function login() {
	window.no_loading = true;
	window.top.pn_loading_start();
	if (window.loading && window.loading.stop) window.loading.stop();
	window.top.set_loading_message("Authentication on "+document.forms['login_form'].elements['domain'].value+"...");
	document.forms["login_form"].onsubmit = null;
	document.forms["login_form"].submit();
}
document.forms["login_form"].elements["username"].focus();
<?php if ($need_app_loading) {?>
require("load_static.js",function(){
	setTimeout(function() {
		if (window.no_loading) return;
		window.loading = new load_static_resources('login_table_footer');
	},1000);
});
<?php } ?>
</script>