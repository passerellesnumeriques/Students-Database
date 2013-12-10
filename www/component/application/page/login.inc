<?php
$message = null;
if (isset($_POST["domain"]) && isset($_POST["username"]) && isset($_POST["password"])) {
	try {
		setcookie("domain",$_POST["domain"],time()+30*24*60*60,"/dynamic/application/page/enter");
		setcookie("username",$_POST["username"],time()+30*24*60*60,"/dynamic/application/page/enter");
		$error = PNApplication::$instance->user_management->login($_POST["domain"], $_POST["username"], $_POST["password"]);
		if ($error === null) {
			echo "<script type='text/javascript'>";
			echo "window.top.set_loading_message('Starting application...');";
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
	<tr><td rowspan=7 id='login_table_logo'>
		<img src='/static/application/logo.png'/>
	</td></tr>
	<tr>
		<td>Domain</td>
		<td>
			<select name="domain">
				<?php
				$def_domain = PNApplication::$instance->local_domain;
				if (isset($_COKKIE["domain"])) $def_domain = $_COKKIE["domain"];
				foreach (PNApplication::$instance->get_domains() as $domain=>$descr)
					echo "<option value=\"".$domain."\"".($def_domain==$domain ? " selected" : "").">".$domain."</option>";
				?>
			</select>
		</td>
	</tr>
	<tr align=left>
		<td>Username</td>
		<td><input type="text" size=30 maxlength=100 name="username" value='<?php if (isset($_COOKIE["username"])) echo $_COOKIE["username"];?>'/></td>
	</tr>
	<tr align=left>
		<td>Password</td>
		<td><input type="password" size=30 maxlength=100 name="password"/></td>
	</tr>
	<tr align=left>
		<td></td>
		<td>
			<div class="button" onclick="login();">
				Login
				<img src='/static/application/login.gif' style='vertical-align:bottom'/>
			</div>
		</td>
	</tr>
	<tr>
		<td></td>
		<td id='google_tr' style='visibility:hidden;position:absolute;top:-10000px'>
			<div class="button" onclick="google_login();">
				<img src='/static/google/google.png'/>
				Login with Google (<span id='user_from_google'></span>)
			</div>
		</td>
	</tr>
	<tr>
		<td></td>
		<td id='facebook_tr' style='visibility:hidden;position:absolute;top:-10000px'>
			<div class="button" onclick="facebook_login();">
				<img src='/static/facebook/facebook.png'/>
				Login with Facebook (<span id='user_from_facebook'></span>)
			</div>
		</td>
	</tr>
	<tr>
		<td colspan=3 id='login_table_footer'>
		</td>
	</tr>
</table>
<br/>
<a href="/dynamic/development/page/tools">Development tools</a>
<br/>
<a href="/dynamic/documentation/page/index">Technical Documentation</a>
<br/>
<a href="/dynamic/test/page/home">Tests</a>
</td></tr></table>
</form>
<style type="text/css">
#login_table {
	border: 1px solid #22bbea;
	border-spacing: 0;
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
setBorderRadius(document.getElementById('login_table'),10,10,10,10,10,10,10,10);
setBorderRadius(document.getElementById('login_table_header'),8,8,8,8,0,0,0,0);
setBorderRadius(document.getElementById('login_table_footer'),0,0,0,0,8,8,8,8);
setBackgroundGradient(document.getElementById('login_table'),"vertical",[{pos:0,color:'#FFFFFF'},{pos:30,color:'#FFFFFF'},{pos:100,color:'#E0E0E0'}]);
function login() {
	window.no_loading = true;
	window.top.pn_loading_start();
	if (window.loading && window.loading.stop) window.loading.stop();
	window.top.set_loading_message("Authentication on "+document.forms['login_form'].elements['domain'].value+"...");
	document.forms["login_form"].onsubmit = null;
	document.forms["login_form"].submit();
}

function google_login() {
	window.no_loading = true;
	window.top.pn_loading_start();
	if (window.loading && window.loading.stop) window.loading.stop();
	window.top.set_loading_message("Authentication on Google...");
	location.href = "/dynamic/google/page/auth?auth_token="+window.top.gapi.auth.getToken()["access_token"];
}

function facebook_login() {
	window.no_loading = true;
	window.top.pn_loading_start();
	if (window.loading && window.loading.stop) window.loading.stop();
	window.top.set_loading_message("Authentication on Facebook...");
	location.href = "/dynamic/facebook/page/auth?auth_token="+window.top.facebook.access_token;
}

window.top.require("google.js",function(){
	window.top.google.need_connection(function(){
		service.json("google","get_user",{auth_token:window.top.gapi.auth.getToken()["access_token"]},function(res){
			if (!res) return;
			document.getElementById('user_from_google').innerHTML = res.user.username;
			var tr = document.getElementById('google_tr');
			tr.style.visibility = 'visible';
			tr.style.position = 'static';
		});
	});

	window.top.require("facebook.js",function(){
		window.top.facebook.onconnect(function() {
			service.json("facebook","get_user",{auth_token:window.top.facebook.access_token},function(res){
				if (!res) return;
				document.getElementById('user_from_facebook').innerHTML = res.user.username;
				var tr = document.getElementById('facebook_tr');
				tr.style.visibility = 'visible';
				tr.style.position = 'static';
			});
		});
	
		require("load_static.js",function(){
			setTimeout(function() {
				if (window.no_loading) return;
				window.loading = new load_static_resources('login_table_footer');
			},1000);
		});
	});
});
</script>