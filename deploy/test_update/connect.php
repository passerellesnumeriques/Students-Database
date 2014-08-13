<?php 
function remove_directory($path) {
	$dir = opendir($path);
	while (($filename = readdir($dir)) <> null) {
		if ($filename == ".") continue;
		if ($filename == "..") continue;
		if (is_dir($path."/".$filename))
			remove_directory($path."/".$filename);
		else
			unlink($path."/".$filename);
	}
	closedir($dir);
	rmdir($path);
}
if (file_exists("download"))
	remove_directory("download");
if (file_exists("conf"))
	remove_directory("conf");
mkdir("conf");
if (file_exists("../../www/conf/proxy")) copy("../../www/conf/proxy", "conf/proxy");
?>
<?php include("../header.inc");?>
<div style='flex:none;background-color:white;padding:10px' id='content'>
</div>
<script type='text/javascript' src='/static/application/deploy_utils.js'></script>
<script type='text/javascript'>
var url = <?php echo json_encode($_POST["url"]);?>;
var pass = <?php echo json_encode($_POST["pass"]);?>;
var content = document.getElementById('content');
content.innerHTML = "Connecting to "+url+"...";

function request(params, data, handler) {
	var xhr = new XMLHttpRequest();
	xhr.open("POST","bridge.php?"+params,true);
	xhr.onreadystatechange = function() {
	    if (this.readyState != 4) return;
	    if (this.status != 200) {
		    handler(true, "Error: "+this.status);
	        return;
	    }
	    handler(false,xhr.responseText);
	};
	xhr.setRequestHeader('Content-type', "application/x-www-form-urlencoded");
	xhr.send(data);
}

var remote_version = null;

var end_count = 0;
var backup_version;
var backup_time;
function downloadEnd(progress) {
	progress = document.getElementById(progress);
	progress.innerHTML = "Done.";
	if (++end_count == 5)
		location.href="finalize.php?path="+encodeURIComponent(<?php echo json_encode($_POST["path"]);?>)+"&version="+backup_version+"&time="+backup_time;
}
function downloadBackup(version, time) {
	backup_version = version;
	backup_time = time;
	content.innerHTML = 
		"Downloading database backup: <span id='progress_db'></span><br/>"+
		"Downloading files backup: <span id='progress_storage'></span><br/>"+
		"Downloading custom tables backup: <span id='progress_custom_tables'></span><br/>"+
		"Downloading Software version "+version+": <span id='progress_software'></span><br/>"+
		"Downloading Initial data of version "+version+": <span id='progress_init_data'></span><br/>";
	download("bridge.php?type=backup&file=db&version="+remote_version+"&file_version="+version+"&file_time="+time,url+"*pass="+pass,"",document.getElementById('progress_db'),function() { downloadEnd('progress_db'); });
	download("bridge.php?type=backup&file=storage&version="+remote_version+"&file_version="+version+"&file_time="+time,url+"*pass="+pass,"",document.getElementById('progress_storage'),function() { downloadEnd('progress_storage'); });
	download("bridge.php?type=backup&file=custom_tables&version="+remote_version+"&file_version="+version+"&file_time="+time,url+"*pass="+pass,"",document.getElementById('progress_custom_tables'),function() { downloadEnd('progress_custom_tables'); });
	download("bridge.php?type=software&version="+remote_version,"software","",document.getElementById('progress_software'),function() { downloadEnd('progress_software'); });
	download("bridge.php?type=software&version="+remote_version,"init_data","",document.getElementById('progress_init_data'),function() { downloadEnd('progress_init_data'); });
}

request("type=get_version","url="+encodeURIComponent(url),function(error,result) {
	if (error) {
		content.innerHTML += "<br/>"+result;
		return;
	}
	content.innerHTML += " It's using version "+result+"<br/>Retrieving backups list...";
	remote_version = result;
	request("type=get_list","url="+encodeURIComponent(url)+"&password="+encodeURIComponent(pass)+"&version="+remote_version+"&request=get_list", function(error,result) {
		if (error) {
			content.innerHTML += "<br/>"+result;
			return;
		}
		var backups_list = eval('('+result+')').result;
		content.innerHTML += "<br/>"+backups_list.length+" backups found. Please select the one to use:";
		var ul = document.createElement("UL");
		content.appendChild(ul);
		for (var i = 0; i < backups_list.length; ++i) {
			var li = document.createElement("LI");
			var link = document.createElement("A");
			li.appendChild(link);
			link.innerHTML = "Version "+backups_list[i].version+" done on "+new Date(parseInt(backups_list[i].time)*1000).toLocaleString();
			ul.appendChild(li);
			link.href = '#';
			link.version = backups_list[i].version;
			link.time = backups_list[i].time;
			link.onclick = function() {
				downloadBackup(this.version, this.time);
				return false;
			};
		}
	});
});
/*

var xhr = new XMLHttpRequest();
xhr.open("POST","bridge.php",true);
xhr.onreadystatechange = function() {
    if (this.readyState != 4) return;
    if (this.status != 200) {
        content.innerHTML = "Error: "+this.status;
        return;
    }
    content.innerHTML = "OK: "+xhr.responseText;
};
xhr.setRequestHeader('Content-type', "application/x-www-form-urlencoded");
var data = "type=remote&url="+encodeURIComponent(url)+"&password="+encodeURIComponent(pass)+"&request=get_list";
xhr.send(data);
*/
</script>
<?php include("../footer.inc");?>
