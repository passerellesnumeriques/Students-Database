<?php
$message = null;
if (isset($_POST["domain"]) && isset($_POST["username"]) && isset($_POST["password"])) {
	try {
		setcookie("domain",$_POST["domain"],time()+30*24*60*60,"/dynamic/application/page/enter");
		setcookie("username",$_POST["username"],time()+30*24*60*60,"/dynamic/application/page/enter");
		$error = PNApplication::$instance->user_management->login($_POST["domain"], $_POST["username"], $_POST["password"]);
		if ($error === null) {
			header("Location: enter");
			return;
		} else
			$message = "Authentication failed: ".$error;
	} catch (Exception $e) {
		$message = "Internal error while authenticating";
		PNApplication::error($e);
	}
}
$this->onload("window.top.pn_loading_end();");
?>
<form method="post" style="width:100%;height:100%" onsubmit="login();return true;">
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
<table style='border:1px solid #22bbea'>
	<tr align=left><td rowspan=6 style='padding: 5px'>
		<img src='/static/application/logo.png'/>
	</td></tr>
	<tr align=left>
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
		<td colspan=2 align=center>
			<button type="submit">Login</button>
		</td>
	</tr>
	<tr align=left>
		<td colspan=3 align=center>
			<a href="/dynamic/development/page/tools">Development tools</a>
			<br/>
			<a href="/dynamic/test/page/home">Tests</a>
			</td>
	</tr>
</table>
<br/>
<!-- 
<span id='application_loading' style='visibility:hidden'><span>Loading application</span> <iframe frameBorder=0 style='width:200px;height:15px;border:1px solid black;overflow:hidden;vertical-align:bottom' scrolling='no' src='/static/application/load.php?delay=5000' id='frame_loading'></iframe><span id='application_loading_text' style='font-size:8pt'></span></span>
 -->
</td></tr></table>
</form>
<script type='text/javascript'>
function login() {
	var f=document.getElementById('frame_loading');
	if(f) f.contentWindow.stop_loading = true;
	window.top.pn_loading_start();
}
</script>