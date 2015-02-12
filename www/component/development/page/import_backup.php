<?php 
class page_import_backup extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$domain = $_GET["domain"];
		echo "<div style='background-color:white;padding:10px;' id='content'>";
		
		if (!isset($_POST["action"])) {
?>
<form name='get_list' method="POST" action="?domain=<?php echo $domain;?>">
<input type='hidden' name='action' value='get_list'/>

Enter the URL of domain <?php echo $domain;?>: <input type='text' name='url' size=50/><br/>
<br/>
Enter the password for remote access: <input type='password' name='password'/><br/>
<br/>
<button class='action' onclick='getList();'>Connect</button>
</form>
<script type='text/javascript'>
function getList() {
	var form = document.forms['get_list'];
	var url = form.elements['url'].value;
	var pass = form.elements['password'].value;
	if (url.length == 0 || pass.length == 0) return;
	if (url.substring(url.length-1) != "/") form.elements['url'].value = url + "/";
	var content = document.createElement('DIV');
	content.innerHTML = "Connecting to <?php echo $domain?>...";
	form.parentNode.appendChild(content);
	layout.changed(content);
	form.style.display="none";
	form.submit();
}
</script>
<?php
		} else if ($_POST["action"] == "get_list") {
			require_once("component/application/RemoteAccess.inc");
			$domain_version = RemoteAccess::getDomainVersion($domain, $_POST["url"]);
			echo "<form name='import_backup' method='POST' action='?domain=$domain'>";
			echo "<input type='hidden' name='action' value='download'/>";
			echo "<input type='hidden' name='url' value='".$_POST["url"]."'/>";
			echo "<input type='hidden' name='password' value='".$_POST["password"]."'/>";
			echo "<input type='hidden' name='domain_version' value='$domain_version'/>";
			echo "<input type='hidden' name='version' value=''/>";
			echo "<input type='hidden' name='time' value=''/>";
			echo "</form>";
			if ($domain_version == null) echo "Unable to connect to $domain.<br/>";
			else {
				$list = RemoteAccess::getBackupList($domain, $domain_version, $_POST["url"], $_POST["password"]);
				if ($list == null) echo "Unable to retrieve the list of available backups from $domain.<br/>";
				else {
					$by_version = array();
					foreach ($list as $backup) {
						if (!isset($by_version[$backup["version"]])) $by_version[$backup["version"]] = array();
						array_push($by_version[$backup["version"]], $backup["time"]);
					}
					if (count($by_version) == 0) echo "No backup available on $domain.<br/>";
					else {
						echo "The available backups on $domain are:<ul>";
						foreach ($by_version as $version=>$backups) {
							echo "<li>Version $version:<ul>";
							foreach ($backups as $b)
								echo "<li><a href='#' onclick=\"importBackup('$version','$b');return false;\">".date("Y-m-d h:i", $b)."</a></li>";
							echo "</ul></li>";
						}
						echo "</ul>";
					}
				}
			}
			?>
			<script type='text/javascript'>
			function importBackup(version, time) {
				var form = document.forms['import_backup'];
				form.elements['version'].value = version;
				form.elements['time'].value = time;
				form.submit();
			}
			</script>
			<?php 
		} else if ($_POST["action"] == "download") {
			require_once("component/application/RemoteAccess.inc");
			$files = array();
			require_once("component/application/Backup.inc");
			foreach (Backup::$possible_files as $filename) {
				$size = RemoteAccess::getBackupFileSize($domain, $_POST["domain_version"], $_POST["url"], $_POST["password"], $_POST["version"], $_POST["time"], $filename);
				if ($size == null) { $files = null; break; }
				if ($size < 0) continue; // file no present
				$files[$filename] = $size;
			}
			if ($files === null) echo "An error occured while retrieving backup files list.<br/>";
			else {
				@mkdir("data/imported_backups");
				@mkdir("data/imported_backups/$domain");
				@mkdir("data/imported_backups/$domain/".$_POST["version"]);
				@mkdir("data/imported_backups/$domain/".$_POST["version"]."/".$_POST["time"]);
				foreach ($files as $filename=>$filesize) {
					RemoteAccess::downloadBackupFile($domain, $_POST["domain_version"], $_POST["url"], $_POST["password"], $_POST["version"], $_POST["time"], $filename, $filesize, "data/imported_backups/$domain/".$_POST["version"]."/".$_POST["time"]."/".$filename.".zip");
				}
				if (!PNApplication::hasErrors()) {
					$f = fopen("data/imported_backups/$domain/".$_POST["version"]."/".$_POST["time"]."/download_complete","w");
					fclose($f);
					echo "Backup successfully downloaded.";
				}
			}	
		}
		
		echo "</div>"; 
	}
	
}
?>