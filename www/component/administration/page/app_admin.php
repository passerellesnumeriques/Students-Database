<?php 
class page_app_admin extends Page {
	
	public function getRequiredRights() { return array("manage_application"); }
	
	public function execute() {
		$this->requireJavascript("section.js");
		theme::css($this, "section.css");
		
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
					$data = self::decode_session($content);
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
<div id='section_maintenance' title='Maintenance' collapsable='true' style='margin-top:10px'>
	<div style='padding:10px'>
		You can put the software into <i>Maintenance Mode</i>.<br/>
		When in maintenance mode, all the users will be disconnected and won't be able to use the software until it will come back into <i>Normal Mode</i>.<br/>
		To put the software into Maintenance Mode, you need first to inform the users, so they can finish their work and save what they are currently doing,
		then the application won't be accessible. Only you, using a specific password, will be able to perform operations and put back the application in Normal Mode.<br/>
		<br/>
		<form name='maintenance' onsubmit='return false;'> 
		Inform the users, and put the software into Maintenance Mode in <input name='timing' type='text' size=3 value='5'/> minutes.<br/>
		I will use the username <i>maintenance</i> with the password <input name='pass1' type='password' size=15/>.<br/>
		Please re-type the maintenance password to confirm:  <input name='pass2' type='password' size=15/><br/>
		</form>
		<button class='action important' onclick="startMaintenance();">Start</button>
	</div>
</div>
</div>
<script type='text/javascript'>
section_updates = sectionFromHTML('section_updates');
section_sessions = sectionFromHTML('section_sessions');
section_maintenance = sectionFromHTML('section_maintenance');

function migration(img,msg) {
	msg.innerHTML = "Retrieving information about how to migrate to the new version...";
	require("deploy_utils.js",function() {
		getURLFile("/dynamic/administration/service/download_update?download=true", "http://sourceforge.net/projects/studentsdatabase/files/versions.txt/download", function(error,content) {
			if (error) {
				img.src = theme.icons_16.error;
				msg.innerHTML = content;
				return;
			}
			var current = "<?php echo $pn_app_version?>";
			var versions = content.split("\n");
			var path = [];
			var found = false;
			for (var i = 0; i < versions.length; ++i) {
				versions[i] = versions[i].trim();
				if (!found) {
					if (versions[i] == current) found = true;
					continue;
				}
				path.push(versions[i-1]+"_to_"+versions[i]);
			}
			msg.innerHTML = "Downloading migration scripts: ";
			var span_file = document.createElement("SPAN");
			var span_progress = document.createElement("SPAN");
			span_progress.style.marginLeft = "5px";
			msg.appendChild(span_file);
			msg.appendChild(span_progress);
			var download_migration;
			var next = function(index) {
				if (index == path.length) {
					// TODO
					img.src = theme.icons_16.ok;
					msg.innerHTML = "New version downloaded and ready to be installed. You can now put the software into <i>Maintenance Mode</i> (see below), then you will have the option to install it.";
					return;
				}
				span_file.innerHTML = path[index];
				// checking if already downloaded
				span_progress.innerHTML = "Checking download...";
				service.customOutput("administration","download_update",{step:'check_if_done',version:path[index]},function(res) {
					if (res == "not_downloaded") {
						download_migration(index);
						return;
					}
					if (res == "invalid_download") {
						span_progress.innerHTML = "Downloaded file is invalid. Re-downloading...";
						download_migration(index,true);
						return;
					}
					if (res == "OK") {
						next(index+1);
						return;
					}
					img.src = theme.icons_16.error;
					msg.innerHTML = "Unexpected answer: "+res;
				});	
			};
			download_migration = function(index,reset) {
				download("/dynamic/administration/service/download_update?download=true"+(reset ? "&reset=true" : false), "http://sourceforge.net/projects/studentsdatabase/files/updates/Students_Management_Software_"+path[index]+".zip/download", "data/updates/Students_Management_Software_"+path[index]+".zip", span_progress, function(error) {
					if (error) {
						img.src = theme.icons_16.error;
						msg.innerHTML = error;
						return;
					}
					span_file.innerHTML = path[index]+" checksum file";
					download("/dynamic/administration/service/download_update?download=true"+(reset ? "&reset=true" : false), "http://sourceforge.net/projects/studentsdatabase/files/updates/Students_Management_Software_"+path[index]+".zip.checksum/download", "data/updates/Students_Management_Software_"+path[index]+".zip.checksum", span_progress, function(error) {
						if (error) {
							img.src = theme.icons_16.error;
							msg.innerHTML = error;
							return;
						}
						span_progress.innerHTML = "Checking download...";
						service.customOutput("administration","download_update",{step:'check_if_done',version:path[index]},function(res) {
							if (res == "not_downloaded") {
								img.src = theme.icons_16.error;
								span_progress.innerHTML = "We cannot find the file after download!";
								return;
							}
							if (res == "invalid_download") {
								img.src = theme.icons_16.error;
								span_progress.innerHTML = "Downloaded file is invalid. Please retry.";
								return;
							}
							if (res == "OK") {
								next(index+1);
								return;
							}
							img.src = theme.icons_16.error;
							msg.innerHTML = "Unexpected answer: "+res;
						});
					});
				});
			};
			next(0);
		});
	});
}

service.json("administration","latest_version",null,function(res) {
	var span = document.getElementById('latest_version');
	if (res && res.version) {
		span.innerHTML = res.version;
		var current = "<?php echo $pn_app_version?>";
		current = current.split(".");
		var latest = res.version.split(".");
		var need_update = false;
		for (var i = 0; i < current.length; ++i) {
			if (latest.length <= i) break;
			var c = parseInt(current[i]);
			var l = parseInt(latest[i]);
			if (l > c) { need_update = true; break; }
			if (l < c) break;
		}
		if (need_update) {
			var new_version = res.version;
			section_updates.content.innerHTML += "<img src='"+theme.icons_16.warning+"' style='vertical-align:bottom'/> <span style='color:#806000;font-weight:bold;'>A newer version is available !</span><br/>";
			var div = document.createElement("DIV");
			var img = document.createElement("IMG");
			img.src = theme.icons_16.loading;
			img.style.verticalAlign = "bottom";
			img.style.marginRight = "5px";
			div.appendChild(img);
			var msg = document.createElement("SPAN");
			msg.innerHTML = "Checking download...";
			div.appendChild(msg);
			section_updates.content.appendChild(div);
			var download_new_version = function(reset) {
				var filename_span = document.createElement("SPAN");
				div.appendChild(filename_span);
				var progress = document.createElement("SPAN");
				progress.style.marginLeft = "10px";
				div.appendChild(progress);
				require("deploy_utils.js",function() {
					filename_span.innerHTML = ": Students_Management_Software_"+new_version+".zip";
					download("/dynamic/administration/service/download_update?download=true"+(reset ? "&reset=true" : false), "http://sourceforge.net/projects/studentsdatabase/files/updates/Students_Management_Software_"+new_version+".zip/download", "data/updates/Students_Management_Software_"+new_version+".zip", progress, function(error) {
						if (error) {
							img.src = theme.icons_16.error;
							msg.innerHTML = error;
							div.removeChild(filename_span);
							div.removeChild(progress);
							return;
						}
						filename_span.innerHTML = ": Students_Management_Software_"+new_version+".zip.checksum";
						download("/dynamic/administration/service/download_update?download=true"+(reset ? "&reset=true" : false), "http://sourceforge.net/projects/studentsdatabase/files/updates/Students_Management_Software_"+new_version+".zip.checksum/download", "data/updates/Students_Management_Software_"+new_version+".zip.checksum", progress, function(error) {
							div.removeChild(filename_span);
							div.removeChild(progress);
							if (error) {
								img.src = theme.icons_16.error;
								msg.innerHTML = error;
								return;
							}
							msg.innerHTML = "Checking download...";
							service.customOutput("administration","download_update",{step:'check_if_done',version:new_version},function(res) {
								if (res == "not_downloaded") {
									img.src = theme.icons_16.error;
									msg.innerHTML = "We cannot find the file after download!";
									return;
								}
								if (res == "invalid_download") {
									img.src = theme.icons_16.error;
									msg.innerHTML = "Downloaded file is invalid. Please retry.";
									return;
								}
								if (res == "OK") {
									migration(img,msg);
									return;
								}
								img.src = theme.icons_16.error;
								msg.innerHTML = "Unexpected answer: "+res;
							});
						});
					});
				});
			};
			service.customOutput("administration","download_update",{step:'check_if_done',version:new_version},function(res) {
				if (res == "not_downloaded") {
					msg.innerHTML = "Downloading new version";
					download_new_version();
					return;
				}
				if (res == "invalid_download") {
					msg.innerHTML = "Downloaded file is invalid. Re-downloading version "+new_version;
					download_new_version(true);
					return;
				}
				if (res == "OK") {
					migration(img,msg);
					return;
				}
				img.src = theme.icons_16.error;
				msg.innerHTML = "Unexpected answer: "+res;
			});
		} else {
			var s = document.createElement("SPAN");
			s.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> The version is up to date !";
			section_updates.content.appendChild(s);
		}
	} else
		span.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Error";
});

section_sessions.addButton(null,"Remove all sessions except mine","action",function() {
	alert("TODO");
});

function startMaintenance() {
	var form = document.forms['maintenance'];
	var timing = form.elements['timing'].value;
	timing = parseInt(timing);
	if (isNaN(timing)) { alert("Please enter a valid number of minutes"); return; }
	if (timing < 2) { alert("You must give at least 2 mintues to the users before they will be disconnected..."); return; }
	if (timing > 15) { if (!confirm("Are you sure you want to wait "+timing+" minutes before maintenance mode ?")) return; }
	var pass1 = form.elements['pass1'].value;
	if (pass1.length == 0) { alert("Please enter a password"); return; }
	if (pass1.length < 6) { alert("Password is too short: you must use at least 6 characters."); return; }
	if (pass1 == "maintenance") { alert("You cannot use maintenance as password, this is too easy to guess..."); return; }
	var pass2 = form.elements['pass2'].value;
	if (pass1 != pass2) { alert("The 2 passwords are different. Please retry."); return; }
	var locker = lock_screen(null, "Starting maintenance mode...");
	service.json("administration","start_maintenance",{timing:timing,password:pass1},function(res) {
		unlock_screen(locker);
		if (res) {
			window.open("/maintenance");
		}
	});
}
</script>
	<?php 
	}

	private static function decode_session($session_string){
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