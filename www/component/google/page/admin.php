<?php 
class page_admin extends Page {
	
	public function getRequiredRights() { return array("admin_google"); }
	
	public function execute() {
if (isset($_POST["action"])) {
	switch ($_POST["action"]) {
		case "remove_calendar":
			require_once("component/google/lib_api/PNGoogleCalendar.inc");
			$gcal = new PNGoogleCalendar();
			$gcal->removeCalendar($_POST["calendar_id"]);
			echo "<script type='text/javascript'>location.assign('/dynamic/google/page/admin');</script>";
			return;
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
	<div id='section_conf' title='Configuration'>
		<div>
			<?php
			if (!$this->component->isInstalled()) {
				echo "<div class='error_box'>Google is not yet configured, please provide Google API keys below</div>";
				$conf = null;
			} else $conf = include("conf/google.inc");
			?>
			<form method='POST' enctype="multipart/form-data" name='google_conf'>
			<input type='hidden' name='action' value='configuration'/>
			<div class='page_section_title2'>Web Client Keys</div>
			<div style='padding:5px;line-height:25px;'>
				Client ID <input type='text' size=80 name='client_id' value='<?php if ($conf) echo $conf["client_id"]; ?>'/><br/>
				API Key <input type='text' size=80 name='client_api_key' value='<?php if ($conf) echo $conf["client_id"]; ?>'/><br/>
			</div>
			<div class='page_section_title2'>Server Keys</div>
			<div style='padding:5px;line-height:25px;'>
				<textarea name='server_keys' style='width:90%;'><?php if ($conf) foreach ($conf["server_keys"] as $key) echo $key."\n"; ?></textarea>
			</div>
			<div class='page_section_title2'>Service Account</div>
			<div style='padding:5px;line-height:25px;'>
				EMail <input type='text' size=80 name='service_account' value='<?php if ($conf) echo $conf["service_account"]; ?>'/><br/>
				Security Key: <?php if ($conf) echo $conf["service_key"]; ?> <input type='file' name='service_key'/><br/>
			</div>
			<div class='page_section_title2'>Administration Service Account</div>
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
	<div id='section_calendars' title='Calendars'>
		<div style='padding:5px;'>
			<table class='all_borders'>
				<tr><th>ID</th><th>Summary</th><th>Description</th><th>Location</th><th>Access</th><th>Last Synch</th><th></th></tr>
				<?php 
				require_once("component/google/lib_api/PNGoogleCalendar.inc");
				$gcal = new PNGoogleCalendar();
				$list = $gcal->getGoogleCalendars();
				$synch = SQLQuery::create()->bypassSecurity()->select("GoogleCalendarSynchro")->execute();
				foreach ($list as $cal) {
					echo "<tr>";
					echo "<td style='font-size:8pt;font-family:Courier New;'>".toHTML($cal->getId())."</td>";
					echo "<td>".toHTML($cal->getSummary())."</td>";
					echo "<td>".toHTML($cal->getDescription())."</td>";
					echo "<td>".toHTML($cal->getLocation())."</td>";
					echo "<td><ul>";
					$acls = $gcal->getAcls($cal->getId());
					foreach ($acls as $acl) {
						echo "<li><b>".$acl->getRole()."</b>: ".$acl->getScope()->getType().": <i>".$acl->getScope()->getValue()."</i></li>";
					}
					echo "</ul></td>";
					echo "<td>";
					$last = null;
					foreach ($synch as $s) if ($s["google_id"] == $cal->getId()) { $last = $s["timestamp"]; break; }
					if ($last == null)
						echo "<i>Never</i>";
					else 
						echo date("d M Y H:i", $last);
					echo "</td>";
					echo "<td>";
					$id = $this->generateID();
					echo "<form method='POST' id='$id'><input type='hidden' name='action' value='remove_calendar'/><input type='hidden' name='calendar_id' value='".$cal->getId()."'/><button class='action red' onclick=\"document.forms['$id'].submit();\"><img src='".theme::$icons_16["remove_white"]."'/> Remove</button></form>";
					echo "</td>";
					echo "</tr>";
				}
				?>
			</table>
		</div>
	</div>
	
	<div id='section_users' title='Organizations and Users'>
		<div style='padding:5px;'>
			<?php
			require_once("component/google/lib_api/PNGoogleDirectory.inc");
			$dir = new PNGoogleDirectory();
			$root = $dir->getHierarchy();
			$this->generateSubOrganizations($root, 0);
			?>
		</div>
	</div>
<?php } ?>
</div>
<script type='text/javascript'>
sectionFromHTML('section_conf');
<?php if ($this->component->isInstalled()) { ?>
sectionFromHTML('section_calendars');
sectionFromHTML('section_users');
<?php } ?>
</script>
<?php 				
	}
	
	private function generateSubOrganizations($node, $indent) {
		foreach ($node["sub_organizations"] as $so) {
			echo "<div style='margin-left:".$indent."px;'>";
			echo "<b>".$so["org"]->name."</b>";
			echo " ".count($so["users"])." users";
			$this->generateSubOrganizations($so, $indent+20);
			echo "</div>";
		}
	}
}
?>