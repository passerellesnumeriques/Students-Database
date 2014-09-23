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
<div id='section_maintenance' title='Maintenance' icon='<?php echo theme::$icons_16["config"];?>' collapsable='true' style='margin-top:10px'>
	<div style='padding:2px'>
		<div class='info_box'>
			<table style='broder-spacing:0px'><tr><td valign=top><img src='<?php echo theme::$icons_16["help"];?>'/></td><td>
			You can put the software into <i>Maintenance Mode</i>.<br/>
			When in maintenance mode, all the users will be disconnected and won't be able to use the software until it will come back into <i>Normal Mode</i>.<br/>
			To put the software into Maintenance Mode, you need first to inform the users, so they can finish their work and save what they are currently doing,
			then the application won't be accessible. Only you, using a specific password, will be able to perform operations and put back the application in Normal Mode.<br/>
			</td></tr></table>
		</div>
		<div style='padding:8px'>
			<form name='maintenance' onsubmit='return false;'> 
			Inform the users, and put the software into Maintenance Mode in <input name='timing' type='text' size=3 value='5'/> minutes.<br/>
			I will use the <b>username <i>maintenance</i></b> with the password <input name='pass1' type='password' size=15/>.<br/>
			Please re-type the maintenance password to confirm:  <input name='pass2' type='password' size=15/><br/>
			</form>
			<button class='action red' onclick="startMaintenance();">Start</button>
		</div>
	</div>
</div>
<div id='section_remote_access' title='Remote access' icon='/static/administration/remote_access.png' collapsable='true' style='margin-top:10px'>
	<div style='padding:2px'>
		<div class='info_box'>
			<table style='broder-spacing:0px'><tr><td valign=top><img src='<?php echo theme::$icons_16["help"];?>'/></td><td>
			Remote access allows each PN center (domain) to synchronize its data with the other centers.
			</td></tr></table>
		</div>
		<div style='padding:8px'>
			<div class='page_section_title3'>Local domain: <?php echo PNApplication::$instance->local_domain;?></div>
			<?php
			if (!file_exists("conf/".PNApplication::$instance->local_domain.".password")) {
				echo "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> You didn't setup a password for remote access. Other domains won't be able to synchronize with your data.<br/>";
				echo "Setup remote access password: <input type='password' id='remote_password'/> <button class='action' onclick='setRemotePassword();'>Setup</button><br/>";
			} else {
				echo "Reset remote access password: <input type='password' id='remote_password'/> <button class='action' onclick='setRemotePassword();'>Reset</button><br/>";
			} 
			?>
			<?php
			foreach (PNApplication::$instance->getDomains() as $domain=>$descr) {
				if ($domain == PNApplication::$instance->local_domain) continue;
				echo "<div class='page_section_title3' style='margin-top:5px;'>Remote domain: $domain</div>";
				if (!file_exists("conf/$domain.remote")) {
					echo "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> No configuration to access remotely to this domain: we cannot synchronize the data.<br/>";
					$remote_access = array("url"=>"","password"=>"");
				} else {
					$remote_access = include("conf/$domain.remote");
				}
				echo "<form name='remote_access_$domain' onsubmit='return false;'>";
				echo "URL <input type='text' name='url' size=50 value=\"".htmlentities($remote_access['url'])."\"/> ";
				echo "Password <input type='password' name='password' value=\"".htmlentities($remote_access['password'])."\"/> ";
				echo "<button class='action' onclick=\"saveRemoteAccess('$domain')\">Save</button>";
				echo " &nbsp; <button class='action red' onclick=\"removeRemoteAccess('$domain');\">Remove</button>";
				echo "</form>";
				echo "<br/>";
				echo "Latest synchronization: ";
				if (!file_exists("data/domains_synch/$domain/latest_recover")) {
					echo "Never";
				} else {
					$info = stat("data/domains_synch/$domain/latest_recover");
					$latest_download = include("data/domains_synch/$domain/latest_download");
					echo date("d M Y H:i", $info["mtime"]);
					echo " from backup of $domain done on ".date("d M Y H:i", $latest_download["time"]);
				}
				echo "<br/>Latest backup downloaded: ";
				if (!file_exists("data/domains_synch/$domain/latest_download")) {
					echo "None";
				} else {
					$info = stat("data/domains_synch/$domain/latest_download");
					$latest_download = include("data/domains_synch/$domain/latest_download");
					echo date("d M Y H:i", $latest_download["time"])." (version ".$latest_download["version"].") downloaded on ".date("d M Y H:i", $info["mtime"]);
				}
				echo "<br/>Download in progress: ";
				if (!file_exists("data/domains_synch/$domain/in_progress")) {
					echo "None";
				} else {
					$info = stat("data/domains_synch/$domain/in_progress");
					$latest_download = include("data/domains_synch/$domain/in_progress");
					echo "Backup of ".date("d M Y H:i", $latest_download["time"])." (version ".$latest_download["version"]."), last partial download on ".date("d M Y H:i", $info["mtime"]);
					$total_size = 0;
					$downloaded_size = 0;
					foreach ($latest_download["files"] as $filename=>$filesize) {
						if ($filesize < 0) continue;
						$total_size += $filesize;
						if (file_exists("data/domains_synch/$domain/$filename.progress"))
							$downloaded_size += filesize("data/domains_synch/$domain/$filename.progress");
					}
					echo ", size downloaded: ".number_format($downloaded_size/(1024*1024),2)."M/".number_format($total_size/(1024*1024),2)."M";
				}
			} 
			?>
		</div>
	</div>
</div>
<div id='section_cron' title='Scheduled Tasks' icon='/static/cron/scheduled_tasks.png' collapsable='true' style='margin-top:10px'>
	<div style='padding:10px'>
		<div class='page_section_title3'>Normal tasks</div>
		<table class='all_borders'>
		<tr><th>Task</th><th>Executed every</th><th>Last execution</th><th>Last duration</th><th>Last errors</th></tr>
		<?php 
		foreach (PNApplication::$instance->cron->getTasks() as $task) {
			echo "<tr>";
			echo "<td>".toHTML($task->task_name)."</td>";
			echo "<td align=center>".$task->every_minutes." min.</td>";
			if (!file_exists("data/cron/".$task->task_id)) {
				echo "<td colspan=3>";
				echo "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> <span style='font-style:italic'>Never</span>";
				echo "</td>";
			} else {
				echo "<td>";
				$info = stat("data/cron/".$task->task_id);
				echo date("d M Y H:i", $info["mtime"]);
				echo "</td>";
				echo "<td>";
				$time = file_get_contents("data/cron/".$task->task_id);
				echo $time." s.";
				echo "</td>";
				echo "<td>";
				if (!file_exists("data/cron/".$task->task_id.".errors")) {
					echo "<img src='".theme::$icons_16["ok"]."' style='vertical-align:bottom'/> Success";
				} else {
					echo "<img src='".theme::$icons_16["error"]."' style='vertical-align:bottom'/> Errors:".file_get_contents("data/cron/".$task->task_id.".errors");
				}
				echo "</td>";
			}
			echo "</td>";
			echo "</tr>";
		}
		?>
		</table>
		<br/>
		Last execution of cron.php: <?php
		if (!file_exists("data/cron/tasks_time")) echo "Never";
		else {
			$info = stat("data/cron/tasks_time");
			$seconds = file_get_contents("data/cron/tasks_time");
			echo date("d M Y H:i", $info["mtime"])." (in ".$seconds." second".(floatval($seconds) > 1 ? "s" : "").")";
		} 
		?><br/>
		<br/>
		<div class='page_section_title3'>Maintenance mode tasks</div>
		<table class='all_borders'>
		<tr><th>Task</th><th>Last execution</th><th>Last duration</th><th>Last errors</th></tr>
		<?php 
		foreach (PNApplication::$instance->cron->getMaintenanceTasks() as $task) {
			echo "<tr>";
			echo "<td>".toHTML($task->task_name)."</td>";
			if (!file_exists("data/cron/".$task->task_id)) {
				echo "<td colspan=3>";
				echo "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> <span style='font-style:italic'>Never</span>";
				echo "</td>";
			} else {
				echo "<td>";
				$info = stat("data/cron/".$task->task_id);
				echo date("d M Y H:i", $info["mtime"]);
				echo "</td>";
				echo "<td>";
				$time = file_get_contents("data/cron/".$task->task_id);
				echo $time." s.";
				echo "</td>";
				echo "<td>";
				if (!file_exists("data/cron/".$task->task_id.".errors")) {
					echo "<img src='".theme::$icons_16["ok"]."' style='vertical-align:bottom'/> Success";
				} else {
					echo "<img src='".theme::$icons_16["error"]."' style='vertical-align:bottom'/> Errors:".file_get_contents("data/cron/".$task->task_id.".errors");
				}
				echo "</td>";
			}
			echo "</tr>";
		}
		?>
		</table>
		<br/>
		Last execution of cron_maintenance.php: <?php
		if (!file_exists("data/cron/maintenance_tasks_time")) echo "Never";
		else {
			$info = stat("data/cron/maintenance_tasks_time");
			$seconds = floatval(file_get_contents("data/cron/maintenance_tasks_time"));
			$timing = "";
			if ($seconds >= 60) {
				$timing .= floor($seconds/60)."m";
				$seconds -= floor($seconds/60)*60;
			}
			$timing .= number_format($seconds,2)."s.";
			echo date("d M Y H:i", $info["mtime"])." (in $timing)";
		} 
		?><br/>
		<div class='info_box'>
			<img src='<?php echo theme::$icons_16["help"];?>' style='vertical-align:bottom'/>
			How to configure scheduled tasks ?<br/>
			<a href='/static/cron/setup_linux.html' target='_blank'>for Linux server</a><br/>
			<a href='/static/cron/setup_windows.html' target='_blank'>for Windows server</a><br/>
		</div>
	</div>
</div>
<div id='section_backup' title='Backups' icon='/static/data_model/database.png' collapsable='true' style='margin-top:10px'>
	<div style='padding:10px'>
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
<div id='section_sessions' title='Sessions' icon='/static/user_management/user_16.png' collapsable='true' style='margin-top:10px'>
	<div style='padding:5px'>
		<?php
		$max_time = ini_get("session.gc_maxlifetime");
		echo "Session expiration: ";
		if ($max_time >= 60) { echo floor($max_time/3600)."h"; $max_time -= floor($max_time/3600)*3600; }
		echo floor($max_time/60)."m";
		echo "<br/>";
		$sessions_path = ini_get("session.save_path");
		$method = ini_get("session.serialize_handler");
		$i = strrpos($sessions_path, ";");
		if ($i !== false) $sessions_path = substr($sessions_path, $i+1);
		$sessions_path = realpath($sessions_path);
		$sessions = array();
		$dir = opendir($sessions_path);
		while (($filename = readdir($dir)) <> null) {
			if (is_dir($sessions_path."/".$filename)) continue;
			if (substr($filename,0,5) <> "sess_") continue;
			$id = substr($filename,5);
			$info = stat($sessions_path."/".$filename);
			if ($id == session_id())
				array_push($sessions, array("id"=>$id,"creation"=>$info["ctime"],"modification"=>$info["mtime"],"size"=>$info["size"],"user"=>"<b>You</b>","remote"=>@$_SERVER["REMOTE_ADDR"],"user_agent"=>@$_SERVER["HTTP_USER_AGENT"]));
			else {
				$content = file_get_contents($sessions_path."/".$filename);
				if (strpos($content, "\"PNApplication\"") === false) continue; // Another application
				$data = self::decodeSession($content);
				$user = "?";
				$remote = "?";
				$user_agent = "?";
				if ($data <> null) {
					$user = @$data["app"]->user_management->domain;
					$user .= "\\";
					$user .= @$data["app"]->user_management->username;
					$remote = @$data["remote"];
					$user_agent = @$data["user_agent"];
				}
				array_push($sessions, array("id"=>$id,"creation"=>$info["ctime"],"modification"=>$info["mtime"],"size"=>$info["size"],"user"=>$user,"remote"=>$remote,"user_agent"=>$user_agent));
			}
		}
		closedir($dir);
		usort($sessions,function($s1,$s2) {
			return $s2["modification"]-$s1["modification"];
		});
		echo "".count($sessions)." sessions open:<br/>";
		echo "<table>";
		echo "<tr><th>Session ID</th><th>Creation</th><th>Modification</th><th>Size</th><th>User</th><th>Client</th><th>Browser</th></tr>";
		require_once("Browser.inc");
		foreach ($sessions as $session) {
			echo "<tr>";
			echo "<td style='padding:0px 3px'><code>".$session["id"]."</code></td>";
			echo "<td style='padding:0px 3px;font-size:9pt'>".date("Y-m-d h:i A", $session["creation"])."</td>";
			echo "<td style='padding:0px 3px;font-size:9pt'>".date("Y-m-d h:i A", $session["modification"])."</td>";
			$size = intval($session["size"]);
			echo "<td style='padding:0px 3px;font-size:9pt' align=right>".($size >= 1024 ? (number_format($size/1024,1)."K") : $size." bytes")."</td>";
			echo "<td style='padding:0px 3px;font-size:9pt'>".$session["user"]."</td>";
			echo "<td style='padding:0px 3px;font-size:9pt'>".$session["remote"]."</td>";
			$br = $session["user_agent"];
			if ($session["user_agent"] <> "") {
				$browser = new Browser($session["user_agent"]);
				$br = $browser->getName();
				if ($br == "Unknown") $br = $session["user_agent"];
			}
			echo "<td style='padding:0px 3px;font-size:9pt'>".$br."</td>";
			echo "</tr>";
		}
		echo "</table>";
		?>
	</div>		
</div>
</div>
<script type='text/javascript'>
section_updates = sectionFromHTML('section_updates');
section_sessions = sectionFromHTML('section_sessions');
section_maintenance = sectionFromHTML('section_maintenance');
section_remote_access = sectionFromHTML('section_remote_access');
section_cron = sectionFromHTML('section_cron');
section_backup = sectionFromHTML('section_backup');

var latest_url = <?php echo json_encode(getLatestVersionURL());?>;
var versions_url = <?php echo json_encode(getVersionsListURL());?>;
var update_url = <?php echo json_encode(getGenericUpdateURL());?>;
function getUpdateURL(filename) {
	return update_url.replace("##FILE##",filename);
}

function download_new_version(new_versions, new_version_index, icon, msg, reset, onsuccess) {
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
				download_new_version(new_versions, new_version_index, icon, msg, false, onsuccess);
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
					download_new_version(new_versions, new_version_index, icon, msg, false, onsuccess);
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
								download_new_version(new_versions, new_version_index, icon, msg, false, onsuccess);
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
									download_new_version(new_versions, new_version_index, icon, msg, false, onsuccess);
								};
								msg.appendChild(button);
								return;
							}
							// checking the download is correct
							msg.innerHTML = "Checking migration scripts...";
							service.customOutput("administration","download_update",{step:'check_if_done',version:prev_version+"_to_"+new_version},function(res) {
								if (res == "OK") {
									icon.src = theme.icons_16.ok;
									msg.innerHTML = "New version ("+new_version+") downloaded and ready to be installed. You can now put the software into <i>Maintenance Mode</i> (see below), then you will have the option to install it.";
									if (onsuccess) onsuccess();
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
									download_new_version(new_versions, new_version_index, icon, msg, true, onsuccess); // retry, with reset
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
					download_new_version(new_versions, new_version_index, icon, msg, true, onsuccess); // retry, with reset
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
		span.innerHTML = "<img src='"+theme.icons_16.warning+"' style='vertical-align:bottom'/> "+(new_versions.length)+" newer version"+(new_versions.length > 1 ? "s" : "")+" found!";
		var next_version = function(index) { 
			var div = document.createElement("DIV");
			section_updates.content.appendChild(div);
			var icon = document.createElement("IMG");
			icon.style.verticalAlign = "bottom";
			var msg = document.createElement("SPAN");
			div.appendChild(icon);
			div.appendChild(msg);
			download_new_version(new_versions, index, icon, msg, false, function() {
				if (index < new_versions.length-1)
					next_version(index+1);
			});
		};
		next_version(0);
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
function saveRemoteAccess(domain) {
	var form = document.forms['remote_access_'+domain];
	var url = form.elements['url'].value;
	var password = form.elements['password'].value;
	url = url.trim();
	if (url.length == 0) { alert("You didn't specify an URL"); return; }
	if (password.length == 0) { alert("You didn't specify a password"); return; }
	var locker = lock_screen(null,'Saving remote access for domain '+domain+'...');
	service.json("administration","save_remote_access",{domain:domain,url:url,password:password},function(res) {
		unlock_screen(locker);
		if (res && res.version) {
			window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "Successfully connected to "+domain+". It's version is "+res.version, [{action:"close"}], 5000));
		}
	});
}
function removeRemoteAccess(domain) {
	confirm_dialog("Remove remote access configuration for "+domain+" ?<br/>No synchronization of data will be done anymore.",function(yes) {
		if (!yes) return;
		var locker = lock_screen(null,'Saving remote access for domain '+domain+'...');
		service.json("administration","save_remote_access",{domain:domain,url:"",password:""},function(res) {
			unlock_screen(locker);
			if (res) {
				var form = document.forms['remote_access_'+domain];
				form.elements['url'].value = "";
				form.elements['password'].value = "";
			}
		});
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
		// $session_open = session_status() == PHP_SESSION_ACTIVE; this is PHP 5.4, not compatible with 5.3
		//if (session_id() == "") $session_open = false;
		//else $session_open = true;
		$session_open = false; // anyway, the session is always closed at this point
		if (!$session_open) @session_start();
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
	    if (!$session_open) session_write_close();
	    return $restored_session;
	}
}
?>