<?php 
class page_admin extends Page {
	
	public function getRequiredRights() { return array("admin_google"); }
	
	public function execute() {
if (isset($_POST["action"])) {
	switch ($_POST["action"]) {
		case "configuration":
			if (!isset($_POST["client_id"]) || trim($_POST["client_id"]) == "") echo "Missing Client ID<br/>";
			else if (!isset($_POST["client_api_key"]) || trim($_POST["client_api_key"]) == "") echo "Missing API Key<br/>";
			else if (!isset($_POST["server_keys"]) || trim($_POST["client_api_key"]) == "") echo "No server key<br/>";
			else if (!isset($_POST["service_account"])) echo "No service account email<br/>";
			else if (!isset($_FILES["service_key"])) echo "No service account key<br/>";
			else if ($_FILES["service_key"]["error"] == 4) echo "No service account key file uploaded<br/>";
			else if ($_FILES["service_key"]["error"] <> 0) echo "Error uploading service account key file: ".$_FILES["service_key"]["error"]."<br/>";
			else if (!isset($_POST["service_account_admin"])) echo "No admin service account email<br/>";
			else if (!isset($_FILES["service_key_admin"])) echo "No admin service account key<br/>";
			else if ($_FILES["service_key_admin"]["error"] == 4) echo "No admin service account key file uploaded<br/>";
			else if ($_FILES["service_key_admin"]["error"] <> 0) echo "Error uploading admin service account key file: ".$_FILES["service_key"]["error"]."<br/>";
			else {
				$list = explode("\n", $_POST["server_keys"]);
				$server_keys = array();
				foreach ($list as $key) {
					$key = trim($key);
					if ($key == "") continue;
					array_push($server_keys, $key);
				}
				if (count($server_keys) == 0) echo "No server key<br/>";
				else {
					move_uploaded_file($_FILES['service_key']['tmp_name'], "conf/google_service.p12");
					move_uploaded_file($_FILES['service_key_admin']['tmp_name'], "conf/google_admin.p12");
					$conf = "<?php return array(\n";
					$conf .= "\t'client_id'=>'".trim($_POST["client_id"])."',\n";
					$conf .= "\t'client_api_key'=>'".trim($_POST["client_api_key"])."',\n";
					$conf .= "\t'server_keys'=>array(\n";
					for ($i = 0; $i < count($server_keys); $i++) {
						$conf .= "\t\t'".$server_keys[$i]."'";
						if ($i < count($server_keys)-1) $conf .= ",";
						$conf .= "\n";
					}
					$conf .= "\t),\n";
					$conf .= "\t'service_account'=>'".trim($_POST["service_account"])."',\n";
					$conf .= "\t'service_key'=>'google_service.p12',\n";
					$conf .= "\t'service_account_admin'=>'".trim($_POST["service_account_admin"])."',\n";
					$conf .= "\t'service_key_admin'=>'google_admin.p12'\n";
					$conf .= "); ?>";
					$f = fopen("conf/google.inc","w");
					fwrite($f, $conf);
					fclose($f);
					echo "Configuration saved.<br/><a href='/dynamic/google/page/admin'>Back to administration page</a>";
				}
			}
			return;
	}
}
		
$this->requireJavascript("section.js");
theme::css($this, "section.css");
?>
<div style='padding:10px'>
	<div id='section_conf' title='Configuration' collapsable='true' collapsed='false'>
		<div>
			<?php
			if (!$this->component->isInstalled()) {
				echo "<div class='error_box'>Google is not yet configured, please provide Google API keys below</div>";
				$conf = null;
			} else $conf = include("conf/google.inc");
			?>
			<form method='POST' enctype="multipart/form-data" name='google_conf'>
			<input type='hidden' name='action' value='configuration'/>
			<div class='page_section_title2'>User Access Keys</div>
			<div class='info_box'>
				<img src='<?php echo theme::$icons_16["help"];?>' style='vertical-align:bottom'/>
				User Access allows the software to use Google functionalities:<ul>
					<li>Google Maps: to display maps in the software, or search for places</li>
					<li>Access to information about the user (its email address, calendars...). This will be only allowed by users who explicitly allows this software to access those information.</li>
				</ul>
				There are 2 types of access:<ul>
					<li>Web Client: for requests coming directly from the browser of the user</li>
					<li>Server: for requests coming from the server</li>
				</ul>
			</div>
			<div class='page_section_title3'>Web Client</div>
			<div style='padding:5px;line-height:25px;'>
				Client ID <input type='text' size=80 name='client_id' value='<?php if ($conf) echo $conf["client_id"]; ?>'/><br/>
				API Key <input type='text' size=80 name='client_api_key' value='<?php if ($conf) echo $conf["client_api_key"]; ?>'/><br/>
			</div>
			<div class='page_section_title3'>Server</div>
			<div style='padding:5px;line-height:25px;'>
				<textarea name='server_keys' style='width:90%;'><?php if ($conf) foreach ($conf["server_keys"] as $key) echo $key."\n"; ?></textarea>
			</div>
			<div class='page_section_title2'>Service Account</div>
			<div class='info_box'>
				<img src='<?php echo theme::$icons_16["help"];?>' style='vertical-align:bottom'/>
				The Service Account allows the software to have its own Google account.<br/>
				This is used by the software for instance to create Google Calendars, on its own account, and share them to the real users. 
			</div>
			<div style='padding:5px;line-height:25px;'>
				EMail <input type='text' size=80 name='service_account' value='<?php if ($conf) echo $conf["service_account"]; ?>'/><br/>
				Security Key: <?php if ($conf) echo $conf["service_key"]; ?> <input type='file' name='service_key'/><br/>
			</div>
			<div class='page_section_title2'>Administration Service Account</div>
			<div class='info_box'>
				<img src='<?php echo theme::$icons_16["help"];?>' style='vertical-align:bottom'/>
				The Administration Service Account allows the software to access to the list of users in passerellesnumeriques.org<br/>
				The allowed accesses to the software are in read-only, so there is no risk the software will modify any information about the users. 
			</div>
			<div style='padding:5px;line-height:25px;'>
				EMail <input type='text' size=80 name='service_account_admin' value='<?php if ($conf) echo $conf["service_account_admin"]; ?>'/><br/>
				Security Key: <?php if ($conf) echo $conf["service_key_admin"]; ?> <input type='file' name='service_key_admin'/><br/>
			</div>
			<div>
				<button class='action' onclick="document.forms['google_conf'].submit();">Save New Configuration</button>
			</div>
			</form>
		</div>
	</div>
<?php if ($this->component->isInstalled()) { ?>
	<div id='section_calendars' title='Calendars' collapsable='true' collapsed='false'>
		<div style='padding:5px;'>
			<div id='calendars_content'>
			<button class='action' onclick="this.innerHTML='Loading...';this.disabled='disabled';loadCalendars();">Load calendars list</button>
			</div>
			Search an event by UID: <input type='text' id='search_event_uid'/> <button class='action' onclick="searchEvent(document.getElementById('search_event_uid').value);">Search</button>
			<script type='text/javascript'>
			function loadCalendars() {
				service.html("google", "admin_calendars", {}, document.getElementById('calendars_content'));
			}
			function searchEvent(uid) {
				service.json("google","search_event_uid",{uid:uid},function(res) {
					infoDialog(res);
				});
			}
			</script>
		</div>
	</div>
	
	<div id='section_files' title='Files' collapsable='true' collapsed='false'>
		<div style='padding:5px;'>
			<div id='files_content'>
				<button class='action' onclick="this.innerHTML='Loading...';this.disabled='disabled';loadFiles();">Load files list</button>
			</div>
			<script type='text/javascript'>
			function loadFiles() {
				service.html("google", "admin_files", {}, document.getElementById('files_content'));
			}
			</script>
		</div>
	</div>
	
	<div id='section_users' title='Organizations and Users' collapsable='true' collapsed='false'>
		<div style='padding:5px;'>
			<div id='users_content'>
				<button class='action' onclick="this.innerHTML='Loading...';this.disabled='disabled';loadUsers();">Load users list</button>
			</div>
			<script type='text/javascript'>
			function loadUsers() {
				service.html("google", "admin_users", {}, document.getElementById('users_content'));
			}
			</script>
		</div>
	</div>
<?php } ?>
</div>
<script type='text/javascript'>
sectionFromHTML('section_conf');
<?php if ($this->component->isInstalled()) { ?>
sectionFromHTML('section_calendars');
sectionFromHTML('section_files');
sectionFromHTML('section_users');
<?php } ?>
</script>
<?php 				
	}
}
?>