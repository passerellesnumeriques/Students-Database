<?php
/**
 * Page to manage the application: updates, backups, maintenance
 */ 
class page_app_admin extends Page {
	
	public function getRequiredRights() { return array("manage_application"); }
	
	public function execute() {
		$this->requireJavascript("section.js");
		theme::css($this, "section.css");
		require_once("update_urls.inc");
		
		$sessions_path = ini_get("session.save_path");
		$i = strrpos($sessions_path, ";");
		if ($i !== false) $sessions_path = substr($sessions_path, $i+1);
		$sessions_path = realpath($sessions_path);
		$sessions = array();
		$dir = opendir($sessions_path);
		while (($filename = readdir($dir)) <> null) {
			if (is_dir($sessions_path."/".$filename)) continue;
			array_push($sessions, $filename);
		}
		closedir($dir);
?>
<div style='padding:10px'>
<div id='section_updates' icon='<?php echo theme::$icons_16["refresh"];?>' title='Software Update'>
	<div style='padding:5px;'>
		<?php
		global $pn_app_version;
		echo "Current version: ".$pn_app_version."<br/>";
		echo "Latest version: <span id='latest_version'><img src='".theme::$icons_16["loading"]."'/></span><br/>";
		?>
	</div>
</div>
<div id='section_maintenance' title='Maintenance' collapsable='true' style='margin-top:10px'>
	<div style='padding:10px'>
		You can put the software into <i>Maintenance Mode</i>.<br/>
		When in maintenance mode, all the users will be disconnected and won't be able to use the software until it will come back into <i>Normal Mode</i>.<br/>
		To put the software into Maintenance Mode, you need first to inform the users, so they can finish their work and save what they are currently doing,
		then the application won't be accessible. Only you, using a specific password, will be able to perform operations and put back the application in Normal Mode.<br/>
		<br/>
		<form name='maintenance' onsubmit='return false;'> 
		Inform the users, and put the software into Maintenance Mode in <input name='timing' type='text' size=3 value='5'/> minutes.<br/>
		I will use the <b>username <i>maintenance</i></b> with the password <input name='pass1' type='password' size=15/>.<br/>
		Please re-type the maintenance password to confirm:  <input name='pass2' type='password' size=15/><br/>
		</form>
		<button class='action red' onclick="startMaintenance();">Start</button>
	</div>
</div>
<div id='section_backup' title='Backups and Remote access' collapsable='true' style='margin-top:10px'>
	<div style='padding:10px'>
		<?php
		if (!file_exists("conf/".PNApplication::$instance->local_domain.".password")) {
			echo "<img src='".theme::$icons_16["warning"]."'/> You didn't setup a password for remote access. Other domains won't be able to synchronize with your data.<br/>";
			echo "Setup remote access password: <input type='password' id='remote_password'/> <button class='action' onclick='setRemotePassword();'>Setup</button><br/>";
		} else {
			echo "Reset remote access password: <input type='password' id='remote_password'/> <button class='action' onclick='setRemotePassword();'>Reset</button><br/>";
		} 
		?>
		<table class='all_borders'>
			<tr><th>Version</th><th>Backup date</th></tr>
			<?php
			require_once("component/application/Backup.inc");
			$backups = Backup::listBackups();
			usort($backups, function($b1,$b2) {
				if ($b1["time"] < $b2["time"]) return -1;
				if ($b1["time"] > $b2["time"]) return 1;
				return 0;
			});
			foreach ($backups as $b) {
				echo "<tr>";
				echo "<td>".$b["version"]."</td>";
				echo "<td>".date("d M Y H:i", $b["time"])."</td>";
				echo "</tr>";
			}
			?>
		</table>
	</div>
</div>
<div id='section_sessions' title='Open Sessions' collapsable='true' style='margin-top:10px'>
	<table>
		<tr><th>Session ID</th><th>Creation</th><th>Last modification</th><th>User</th></tr>
		<?php 
		$method = ini_get("session.serialize_handler");
		foreach ($sessions as $session) {
			$id = substr($session,5);
			echo "<tr>";
			echo "<td><code>".$id."</code></td>";
			$info = stat($sessions_path."/".$session);
			echo "<td>".date("Y-m-d h:i A", $info["ctime"])."</td>";
			echo "<td>".date("Y-m-d h:i A", $info["mtime"])."</td>";
			echo "<td>";
			if ($id == session_id()) {
				echo "<b>You</b>";
			} else {
				$content = file_get_contents($sessions_path."/".$session);
				if (strpos($content, "\"PNApplication\"") === false)
					echo "<i>Another application</i>";
				else {
					$data = self::decodeSession($content);
					if ($data <> null) {
						echo @$data["app"]->user_management->username;
					}
				}
			}
			echo "</td>";
			echo "</tr>";
		}
		?>
	</table>		
</div>
</div>
<script type='text/javascript'>
section_updates = sectionFromHTML('section_updates');
section_sessions = sectionFromHTML('section_sessions');
section_maintenance = sectionFromHTML('section_maintenance');
section_backup = sectionFromHTML('section_backup');

var latest_url = <?php echo json_encode(getLatestVersionURL());?>;
var versions_url = <?php echo json_encode(getVersionsListURL());?>;
var update_url = <?php echo json_encode(getGenericUpdateURL());?>;
function getUpdateURL(filename) {
	return update_url.replace("##FILE##",filename);
}

function download_new_version(new_versions, new_version_index, icon, msg, reset) {
	icon.src = theme.icons_16.loading;
	msg.innerHTML = "Downloading version "+new_versions[new_version_index];
	var progress = document.createElement("SPAN");
	progress.style.marginLeft = "10px";
	msg.appendChild(progress);
	download("/dynamic/administration/service/download_update?download=true"+(reset ? "&reset=true" : ""), getUpdateURL("Students_Management_Software_"+new_versions[new_version_index]+".zip"), "data/updates/Students_Management_Software_"+new_versions[new_version_index]+".zip", progress, function(error) {
		if (error) {
			icon.src = theme.icons_16.error;
			msg.innerHTML = error;
			var button = document.createElement("BUTTON");
			button.className = "action";
			button.innerHTML = "Retry";
			button.style.marginLeft = "5px";
			button.onclick = function() {
				download_new_version(new_versions, new_version_index, icon, msg);
			};
			msg.appendChild(button);
			return;
		}
		// download checksum
		msg.innerHTML = "Downloading checksum for version "+new_versions[new_version_index];
		progress = document.createElement("SPAN");
		progress.style.marginLeft = "10px";
		msg.appendChild(progress);
		download("/dynamic/administration/service/download_update?download=true"+(reset ? "&reset=true" : ""), getUpdateURL("Students_Management_Software_"+new_versions[new_version_index]+".zip.checksum"), "data/updates/Students_Management_Software_"+new_versions[new_version_index]+".zip.checksum", progress, function(error) {
			if (error) {
				icon.src = theme.icons_16.error;
				msg.innerHTML = error;
				var button = document.createElement("BUTTON");
				button.className = "action";
				button.innerHTML = "Retry";
				button.style.marginLeft = "5px";
				button.onclick = function() {
					download_new_version(new_versions, new_version_index, icon, msg);
				};
				msg.appendChild(button);
				return;
			}
			// checking the download is correct
			msg.innerHTML = "Checking download...";
			service.customOutput("administration","download_update",{step:'check_if_done',version:new_versions[new_version_index]},function(res) {
				if (res == "OK") {
					// download migration script
					var prev_version = new_version_index > 0 ? new_versions[new_version_index-1] : "<?php echo $pn_app_version?>";
					var new_version = new_versions[new_version_index];
					msg.innerHTML = "Downloading migration scripts from version "+prev_version+" to version "+new_version;
					progress = document.createElement("SPAN");
					progress.style.marginLeft = "10px";
					msg.appendChild(progress);
					download("/dynamic/administration/service/download_update?download=true"+(reset ? "&reset=true" : ""), getUpdateURL("Students_Management_Software_"+prev_version+"_to_"+new_version+".zip"), "data/updates/Students_Management_Software_"+prev_version+"_to_"+new_version+".zip", progress, function(error) {
						if (error) {
							icon.src = theme.icons_16.error;
							msg.innerHTML = error;
							var button = document.createElement("BUTTON");
							button.className = "action";
							button.innerHTML = "Retry";
							button.style.marginLeft = "5px";
							button.onclick = function() {
								download_new_version(new_versions, new_version_index, icon, msg);
							};
							msg.appendChild(button);
							return;
						}
						// download checksum
						msg.innerHTML = "Downloading checksum for migration scripts";
						progress = document.createElement("SPAN");
						progress.style.marginLeft = "10px";
						msg.appendChild(progress);
						download("/dynamic/administration/service/download_update?download=true"+(reset ? "&reset=true" : ""), getUpdateURL("Students_Management_Software_"+prev_version+"_to_"+new_version+".zip.checksum"), "data/updates/Students_Management_Software_"+prev_version+"_to_"+new_version+".zip.checksum", progress, function(error) {
							if (error) {
								icon.src = theme.icons_16.error;
								msg.innerHTML = error;
								var button = document.createElement("BUTTON");
								button.className = "action";
								button.innerHTML = "Retry";
								button.style.marginLeft = "5px";
								button.onclick = function() {
									download_new_version(new_versions, new_version_index, icon, msg);
								};
								msg.appendChild(button);
								return;
							}
							// checking the download is correct
							msg.innerHTML = "Checking migration scripts...";
							service.customOutput("administration","download_update",{step:'check_if_done',version:prev_version+"_to_"+new_version},function(res) {
								if (res == "OK") {
									icon.src = theme.icons_16.ok;
									msg.innerHTML = "New version downloaded and ready to be installed. You can now put the software into <i>Maintenance Mode</i> (see below), then you will have the option to install it.";
									return;
								}
								if (res == "not_downloaded") {
									icon.src = theme.icons_16.error;
									msg.innerHTML = "We cannot find the file after download!";
								} else if (res == "invalid_download") {
									icon.src = theme.icons_16.error;
									msg.innerHTML = "Downloaded file is invalid. Please retry.";
								} else {
									icon.src = theme.icons_16.error;
									msg.innerHTML = "Unexpected answer: "+res;
								}
								var button = document.createElement("BUTTON");
								button.className = "action";
								button.innerHTML = "Retry";
								button.style.marginLeft = "5px";
								button.onclick = function() {
									download_new_version(new_versions, new_version_index, icon, msg, true); // retry, with reset
								};
								msg.appendChild(button);
							});
						});
					});
					return;
				}
				if (res == "not_downloaded") {
					icon.src = theme.icons_16.error;
					msg.innerHTML = "We cannot find the file after download!";
				} else if (res == "invalid_download") {
					icon.src = theme.icons_16.error;
					msg.innerHTML = "Downloaded file is invalid. Please retry.";
				} else {
					icon.src = theme.icons_16.error;
					msg.innerHTML = "Unexpected answer: "+res;
				}
				var button = document.createElement("BUTTON");
				button.className = "action";
				button.innerHTML = "Retry";
				button.style.marginLeft = "5px";
				button.onclick = function() {
					download_new_version(new_versions, new_version_index, icon, msg, true); // retry, with reset
				};
				msg.appendChild(button);
			});
		});
	});		
}

require("deploy_utils.js",function() {
	var span = document.getElementById('latest_version');
	getURLFile("/dynamic/administration/service/download_update?download=true", versions_url, function(error,content) {
		if (error) {
			span.innerHTML = "<img src='"+theme.icons_16.error+"'/> "+content;
			return;
		}
		var current = "<?php echo $pn_app_version?>";
		var versions = content.split("\n");
		var new_versions = [];
		var current_found = false;
		for (var i = 0; i < versions.length; ++i) {
			if (!current_found) {
				if (versions[i] == current) current_found = true;
				continue;
			}
			new_versions.push(versions[i]);
		}
		if (new_versions.length == 0) {
			span.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> The version is up to date !";
			return;
		}
		span.innerHTML = "<img src='"+theme.icons_16.warning+"'/> "+(new_versions.length)+" newer version"+(new_versions.length > 1 ? "s" : "")+" found!";
		var div = document.createElement("DIV");
		section_updates.content.appendChild(div);
		var icon = document.createElement("IMG");
		icon.style.verticalAlign = "bottom";
		var msg = document.createElement("SPAN");
		div.appendChild(icon);
		div.appendChild(msg);
		download_new_version(new_versions, 0, icon, msg);
	});
});


section_sessions.addButton(null,"Remove all sessions except mine","action",function() {
	alert("TODO");
});

function startMaintenance() {
	var form = document.forms['maintenance'];
	var timing = form.elements['timing'].value;
	timing = parseInt(timing);
	if (isNaN(timing)) { alert("Please enter a valid number of minutes"); return; }
	if (timing < 5) { if (!confirm("Are you sure you want to launch maintenance mode in only "+timing+" minute(s) ? This may be too short notice for the users.")) return; }
	var pass1 = form.elements['pass1'].value;
	if (pass1.length == 0) { alert("Please enter a password"); return; }
	if (pass1.length < 6) { alert("Password is too short: you must use at least 6 characters."); return; }
	if (pass1 == "maintenance") { alert("You cannot use maintenance as password, this is too easy to guess..."); return; }
	var pass2 = form.elements['pass2'].value;
	if (pass1 != pass2) { alert("The 2 passwords are different. Please retry."); return; }
	var locker = lock_screen(null, "Starting maintenance mode...");
	service.json("administration","start_maintenance",{timing:timing,password:pass1},function(res) {
		if (!res) { unlock_screen(locker); return; }
		var div = document.createElement("DIV");
		div.innerHTML = "Maintenance mode scheduled.<br/>Please ";
		var link = document.createElement("A");
		link.href = "/maintenance";
		link.target = "_blank";
		link.innerHTML = "click here";
		div.appendChild(link);
		set_lock_screen_content(locker, div);
		link.onclick = function() {
			unlock_screen(locker);
		};
	});
}
function setRemotePassword() {
	var pass = document.getElementById('remote_password').value;
	if (pass.length < 10) { alert("Remote Access Password must have at least 10 characters"); return; }
	service.json("administration","set_remote_password",{password:pass},function(res) {
		if (res) location.reload();
	});
}
</script>
	<?php 
	}

	/** Decode a session file
	 * @param string $session_string content of session file
	 * @return array decoded content of session
	 */
	private static function decodeSession($session_string){
	    $current_session = session_encode();
	    foreach ($_SESSION as $key => $value){
	        unset($_SESSION[$key]);
	    }
	    session_decode($session_string);
	    $restored_session = $_SESSION;
	    foreach ($_SESSION as $key => $value){
	        unset($_SESSION[$key]);
	    }
	    session_decode($current_session);
	    return $restored_session;
	}
}
?>