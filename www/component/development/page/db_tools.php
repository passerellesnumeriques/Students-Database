<?php 
class page_db_tools extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		require_once("update_urls.inc");
		
		$datamodels = array();
		if (!file_exists("data/datamodels")) @mkdir("data/datamodels");
		$dir = opendir("data/datamodels");
		while (($filename = readdir($dir)) <> null) {
			if (strlen($filename) < 29+14+1) continue;
			if (substr($filename,0,29) <> "Students_Management_Software_") continue;
			if (substr($filename,strlen($filename)-14) <> "_datamodel.zip") continue;
			$version = substr($filename, 29);
			$version = substr($version, 0, strlen($version)-14);
			array_push($datamodels, $version);
		}
		closedir($dir);
		
		$backups = array();
		if (file_exists("data/backups")) {
			$versions = array();
			$dir = opendir("data/backups");
			while (($file = readdir($dir)) <> null) {
				if ($file == "." || $file == "..") continue;
				if (!is_dir("data/backups/$file")) continue;
				$backups[$file] = array();
			}
			closedir($dir);
			foreach ($backups as $version=>$list) {
				$dir = opendir("data/backups/$version");
				while (($file = readdir($dir)) <> null) {
					if ($file == "." || $file == "..") continue;
					if (!is_dir("data/backups/$version/$file")) continue;
					array_push($backups[$version], $file);
				}
				closedir($dir);
			}
		}
		
		$migration_scripts = array();
		foreach (PNApplication::$instance->components as $cname=>$c) {
			if (!file_exists("component/$cname/updates")) continue;
			$dir = opendir("component/$cname/updates");
			while (($filename = readdir($dir)) <> null) {
				if ($filename == "." || $filename == ".." || !is_dir("component/$cname/updates/$filename")) continue;
				if (!isset($migration_scripts[$filename])) $migration_scripts[$filename] = array();
				array_push($migration_scripts[$filename], $cname);
			}
			closedir($dir);
		}
		
		$avail_migrations = array();
		if (file_exists("data/updates")) {
			$dir = opendir("data/updates");
			while (($filename = readdir($dir)) <> null) {
				if (strlen($filename) < 29) continue;
				if (substr($filename,0,29) <> "Students_Management_Software_") continue;
				$s = substr($filename,29);
				if (strlen($s) < 4) continue;
				if (substr($s,strlen($s)-4) <> ".zip") continue;
				$s = substr($s,0,strlen($s)-4);
				if (strpos($s, "_to_") === false) continue;
				array_push($avail_migrations, $s);
			}
			closedir($dir);
		}
		
		theme::css($this, "section.css");
?>
<div style='padding:10px'>
	<div id='progress' style='margin:10px'></div>
	<div class='section'>
		<div class='header'>
			<div>Reset Database</div>
		</div>
		<div style='padding:5px;background-color:white;'>
			<div>
				Resetting database remove all data, and rebuild each table based on the DataModel
			</div>
			Reset database using:<ul id='reset_db_list'>
				<li><a href='#' onclick="resetDB('current');return false;">Current DataModel (<?php global $pn_app_version; echo $pn_app_version;?>)</a></li>
			</ul>
			<div id='reset_db_loading_datamodels'><img src='<?php echo theme::$icons_16["loading"];?>'/> Searching previous datamodels...</div>
		</div>
	</div>
	<div class='section'>
		<div class='header'>
			<div>Backups</div>
		</div>
		<div style='padding:5px;background-color:white;'>
			Reset database and import data from backup:<ul id='backups_versions_list'>
			</ul>
			<div id='backups_loading_datamodels'><img src='<?php echo theme::$icons_16["loading"];?>'/> Searching previous datamodels...</div>
		</div>
		<div class='footer'>
			<button class='action' onclick='createBackup();'>Create New Backup</button>
		</div>
	</div>
	<div class='section'>
		<div class='header'>
			<div>Migration</div>
		</div>
		<div style='padding:5px;background-color:white;' id='migrations'>
			<img src='<?php echo theme::$icons_16["loading"];?>'/> Retrieving versions list...
		</div>
	</div>
</div>
<script type='text/javascript'>
var known_datamodels = <?php echo json_encode($datamodels);?>;
var backups = <?php echo json_encode($backups);?>;
var migrations = <?php echo json_encode($migration_scripts);?>;
var avail_migrations = <?php echo json_encode($avail_migrations);?>;
var domains = <?php echo json_encode(array_keys(PNApplication::$instance->getDomains()));?>;
var current_version = <?php global $pn_app_version; echo json_encode($pn_app_version); ?>;

var versions_url = <?php echo json_encode(getVersionsListURL());?>;
var update_url = <?php echo json_encode(getGenericUpdateURL());?>;
function getUpdateURL(filename) {
	return update_url.replace("##FILE##",filename);
}

function ResetDB_AddDataModel(version) {
	var ul = document.getElementById('reset_db_list');
	var li = document.createElement("LI");
	li.innerHTML = "<a href='#' onclick=\"resetDB('"+version+"');return false;\">"+version+"</a>";
	ul.appendChild(li);
}

var waiting_datamodels = [
  {loading:'reset_db_loading_datamodels',ondatamodel:ResetDB_AddDataModel},
  {loading:'backups_loading_datamodels',ondatamodel:addBackupRecoverVersion}
];

function resetDB(version,ondone) {
	var locker = lock_screen(null,"Resetting Database...");
	var resetDomain = function(index) {
		if (index == domains.length) { unlock_screen(locker); if(ondone) ondone(); return; }
		set_lock_screen_content(locker, "Resetting Database: Domain "+domains[index]+": Removing all data...");
		service.json("development","empty_db",{domain:domains[index]},function(res) {
			if (!res) { unlock_screen(locker); return; }
			set_lock_screen_content(locker, "Resetting Database: Domain "+domains[index]+": Creating datamodel version: "+version);
			service.json("development","create_datamodel",{domain:domains[index],version:version},function(res) {
				if (!res) { unlock_screen(locker); return; }
				resetDomain(index+1);
			});
		});
	};
	resetDomain(0);
}

function addBackup(version, time) {
	var ul = document.getElementById("backups_list_v"+version);
	if (!ul) {
		var list = document.getElementById('backups_versions_list');
		var li = document.createElement("LI");
		li._version = version;
		li.appendChild(document.createTextNode("DataModel Version "+version));
		ul = document.createElement("UL");
		li._ul = ul;
		ul.id = "backups_list_v"+version;
		li.appendChild(ul);
		list.appendChild(li);
	}
	var li = document.createElement("LI");
	li.appendChild(document.createTextNode(new Date(parseInt(time)*1000).toLocaleString()));
	ul.appendChild(li);
	var a = document.createElement("A");
	a.href = "#";
	a.onclick = function() { recoverBackup(version, time, 'current'); return false; };
	a.appendChild(document.createTextNode("Using current datamodel"));
	a.style.marginLeft = "5px";
	li.appendChild(a);
}

function addBackupRecoverVersion(version) {
	var ul = document.getElementById('backups_versions_list');
	for (var i = 0; i < ul.childNodes.length; ++i) {
		var li = ul.childNodes[i];
		if (li._version != version) continue;
		var ul2 = li._ul;
		for (var j = 0; j < ul2.childNodes.length; ++j) {
			var li2 = ul2.childNodes[j];
			var a = document.createElement("A");
			a.href = "#";
			a.onclick = function() { recoverBackup(version, time, version); return false; };
			a.appendChild(document.createTextNode("Using datamodel v."+version));
			a.style.marginLeft = "5px";
			li2.appendChild(a);
		}
	}
}

function createBackup() {
	var locker = lock_screen(null, "Backuping database...");
	service.json("development","backup",{},function(res) {
		unlock_screen(locker);
		if (!res) {
			error_dialog("Error during backup");
			return;
		}
		addBackup(res.version, res.time);
	});
}

function recoverBackup(version, time, model_version) {
	resetDB(model_version,function() {
		var locker = lock_screen(null, "Importing backup...");
		service.json("development","recover",{version:version,time:time,datamodel_version:model_version},function(res) {
			unlock_screen(locker);
		});
	});
}

function updateMigrationScripts(versions_list) {
	var div = document.getElementById('migrations');
	div.innerHTML = "";
	var ul = document.createElement("UL");
	div.appendChild(ul);
	var versions = [];
	for (var i = 0; i < versions_list.length; ++i) versions.push(versions_list[i]);
	if (versions.length > 0 && versions[versions.length-1] != current_version) versions.push(current_version);
	for (var i = 1; i < versions.length; ++i) {
		if (typeof migrations[versions[i]] != 'undefined') {
			var components = migrations[versions[i]];
			delete migrations[versions[i]];
			var li = document.createElement("LI");
			var a = document.createElement("A");
			a.href = "#";
			a._from = versions[i-1];
			a._to = versions[i];
			a.onclick = function() { migrate(this._from, this._to); return false; };
			a.appendChild(document.createTextNode(versions[i-1]+" to "+versions[i]));
			li.appendChild(a);
			var s = " (";
			for (var j = 0; j < components.length; ++j)
				s += (j > 0 ? ", " : "")+components[j];
			s += ")";
			li.appendChild(document.createTextNode(s));
			ul.appendChild(li);
		}
	}
	for (var version in migrations) {
		var li = document.createElement("LI");
		var s = "Obsolete: migration to version "+version;
		s += " (";
		for (var j = 0; j < components.length; ++j)
			s += (j > 0 ? ", " : "")+components[j];
		s += ")";
		li.appendChild(document.createTextNode(s));
		ul.appendChild(li);
	}
}

function migrate(from, to) {
	if (!known_datamodels.contains(from)) { alert("We do not have the DataModel version "+from); return; }
	if (typeof backups[from] == 'undefined') { alert("We do not have any backup from version "+from+". We cannot migrate data from this version."); return; }
	if (!avail_migrations.contains(from+"_to_"+to)) { alert("Migration from "+from+" to "+to+" is not available: please download or generate file Students_Management_Software_"+from+"_to_"+to+".zip in directory data/updates"); return; }
	alert("TODO");
	// TODO
	// 1-reset database with model 'from'
	// 2-import a backup from this version
	// 3-apply migration
}

for (var version in backups) for (var i = 0; i < backups[version].length; ++i) addBackup(version, backups[version][i]);
for (var i = 0; i < known_datamodels.length; ++i)
	for (var j = 0; j < waiting_datamodels.length; ++j)
		waiting_datamodels[j].ondatamodel(known_datamodels[i]);
		
require("deploy_utils.js",function() {
	var progress = document.getElementById('progress');
	getURLFile("/dynamic/development/service/download?download=true", versions_url, function(error,content) {
		if (error) {
			var html = "<img src='"+theme.icons_16.error+"'/> Error downloading versions list: "+content;
			for (var i = 0; i < waiting_datamodels.length; i++)
				document.getElementById(waiting_datamodels[i].loading).innerHTML = html;
			progress.innerHTML = html;
			return;
		}
		var versions = content.split("\n");
		updateMigrationScripts(versions);
		var to_download = [];
		for (var i = 0; i < versions.length; ++i)
			if (!known_datamodels.contains(versions[i])) to_download.push(versions[i]);
		var downloadNext = function(index) {
			if (index == to_download.length) {
				for (var i = 0; i < waiting_datamodels.length; ++i) {
					var div = document.getElementById(waiting_datamodels[i].loading);
					div.parentNode.removeChild(div);
				}
				progress.parentNode.removeChild(progress);
				return;
			}
			for (var i = 0; i < waiting_datamodels.length; i++)
				document.getElementById(waiting_datamodels[i].loading).innerHTML = "<img src='"+theme.icons_16.loading+"'/> Downloading DataModel "+to_download[index];
			download("/dynamic/development/service/download?download=true&reset=true", getUpdateURL("Students_Management_Software_"+to_download[index]+"_datamodel.zip"), "data/datamodels/Students_Management_Software_"+to_download[index]+"_datamodel.zip", progress, function(error) {
				if (error) {
					var html = "<img src='"+theme.icons_16.error+"'/> Error downloading datamodel "+to_download[index]+": "+content;
					for (var i = 0; i < waiting_datamodels.length; i++)
						document.getElementById(waiting_datamodels[i].loading).innerHTML = html;
					progress.innerHTML = html;
					return;
				}
				for (var i = 0; i < waiting_datamodels.length; i++)
					waiting_datamodels[i].ondatamodel(to_download[index]);
				known_datamodels.push(to_download[index]);
				downloadNext(index+1);
			});
		};
		downloadNext(0);
	});
});
</script>
<?php 
	}
	
}
?>