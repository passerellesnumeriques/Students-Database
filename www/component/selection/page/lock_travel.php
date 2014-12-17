<?php 
require_once("SelectionPage.inc");
class page_lock_travel extends SelectionPage {
	public function getRequiredRights() { return array("manage_selection_campaign"); }
	public function executeSelectionPage(){
		require_once 'update_urls.inc';
		global $pn_app_version;
		$www_filename = "Students_Management_Software_".$pn_app_version."_Selection_Travel.zip";
		$www_url = getUpdateURL($www_filename);
		$server_filename = "SelectionToolTravel_WebServer.zip";
		$server_url = "http://sourceforge.net/projects/studentsdatabase/files/$server_filename/download";
?>
<div style='background-color:white;padding:10px' id='content'>
	Are you currently on the computer with which you will travel ?
</div>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);
var content = document.getElementById('content');
var server_version = <?php echo json_encode($pn_app_version);?>;

function checkInstall() {
	popup.removeButtons();
	content.innerHTML = "Checking installation on your computer...";
	layout.changed(content);
	ajax.post("http://127.0.0.1:8888/server_comm/check_install",{},function(error) {
		content.innerHTML = 
			"We cannot find the Travelling version of this software on your computer.<br/>"+
			"<br/>"+
			"If you never installed it, please follow the steps:<ol>"+
			"<li>Download the file <a href='<?php echo $server_url;?>' target='_blank'><?php echo $server_filename;?></a></li>"+
			"<li>Create a directory <b>C:\\SelectionToolTravel</b>, and extract the file you downloaded inside this directory</li>"+
			"<li>Download another file <a href='<?php echo $www_url;?>' target='_blank'><?php echo $www_filename;?></a></li>"+
			"<li>Extract this second file into the directory <b>C:\\SelectionToolTravel\\www</b> (so this is the directory www inside the previous directory)</li>"+
			"<li>Execute the file <b>start.bat</b> located in the directory C:\\SelectionToolTravel</li>"+
			"<li>Finally, click on the button 'try again' below</li>"+
			"</ol>"+
			"<br/>"+
			"If you already installed it, please start it by launching the file <b>start.bat</b> located in the directory <b>C:\\SelectionToolTravel</b><br/>"+
			"Once done, try again.<br/>"+
			"<br/>"+
			"<button class='action' onclick='checkInstall();'>Try Again</button>";
		layout.changed(content);
	},function(xhr) {
		var version = xhr.responseText;
		if (version != server_version) {
			content.innerHTML = 
				"The version on your computer is outdated and we need to update it.<br/>"+
				"<br/>"+
				"To update your version, follow the steps:<ol>"+
				"<li>Stop it, by launching the file <b>stop.bat</b> in the directory <b>C:\\SelectionToolTravel</b></li>"+
				"<li>Go into the directory <b>C:\\SelectionToolTravel\\www</b> and remove everything: all files and directories, it must be empty</li>"+
				"<li>Download the latest version <a href='<?php echo $www_url;?>' target='_blank'><?php echo $www_filename;?></a></li>"+
				"<li>Extract it in your directory <b>C:\\SelectionToolTravel\\www</b></li>"+
				"<li>Start it by launching the file <b>start.bat</b> located in the directory C:\\SelectionToolTravel</li>"+
				"<li>Once done, you can try again</li>"+
				"</ol>"+
				"<br/>"+
				"<button class='action' onclick='checkInstall();'>Try Again</button>";
			layout.changed(content);
			return;
		}
		synchronizeDatabases();
	});
}

function synchronizeDatabases() {
	content.innerHTML =	"Locking campaign...";
	layout.changed(content);
	service.json("selection","lock_campaign",{reason:'travel'},function(res) {
		if (!res || !res.token) { return; }
		theme.css("progress_bar.css");
		content.innerHTML = "Synchronizing your computer with the Database.<br/>This may take several minutes...<br/><br/><div id='progress_text'></div><div id='progress_bar' style='display:none'></div>";
		layout.changed(content);
		var pb = null;
		require("progress_bar.js",function() {
			pb = new progress_bar(250,20);
			var cont = document.getElementById('progress_bar');
			if (cont) cont.appendChild(pb.element);
		});
		var progress = function() {
			ajax.post("http://127.0.0.1:8888/server_comm/download_progress",{},function(error){},function(xhr){
				var text = document.getElementById('progress_text');
				if (!text) return;
				var pb_cont = document.getElementById('progress_bar');
				var s = xhr.responseText;
				if (s.substring(0,1) == "%") {
					s = s.substring(1);
					var i = s.indexOf('%');
					var s2 = s.substr(0,i);
					s = s.substr(i+1);
					if (pb) {
						i = s2.indexOf(',');
						pb.setPosition(parseInt(s2.substr(0,i)));
						pb.setTotal(parseInt(s2.substr(i+1)));
						pb_cont.style.display = "";
					}
				} else
					pb_cont.style.display = "none";
				text.innerHTML = s;
				layout.changed(content);
				setTimeout(progress,1000);
			});
		};
		setTimeout(progress, 1000);
		ajax.post("http://127.0.0.1:8888/server_comm/download_database",{
			server:location.host,
			domain:<?php echo json_encode(PNApplication::$instance->local_domain);?>,
			username:<?php echo json_encode(PNApplication::$instance->user_management->username);?>,
			session:<?php echo json_encode(session_id());?>,
			token:res.token,
			campaign:<?php echo PNApplication::$instance->selection->getCampaignId();?>
		},function(error) {
			content.innerHTML = "An error occured during the synchronization: "+error;
			layout.changed(content);
		},function(xhr) {
			if (xhr.responseText != "OK") {
				content.innerHTML = "An error occured during the synchronization of your computer:<br/>"+xhr.responseText;
				layout.changed(content);
				service.json("selection","unlock_campaign",{},function(res) {
				});
				return;
			}
			content.innerHTML = "The campaign has been locked, and your computer is ready to travel.<br/>"+
				"You can now use the address <a href='http://localhost:8888/' target='_blank'>http://localhost:8888/</a> to access it on your computer.<br/>";
			layout.changed(content);
			popup.removeButtons();
			popup.addCloseButton(function() {
				var win = window.top.frames["pn_application_frame"];
				win.sectionClicked(win.getSection('selection'));
				popup.close();
			});
		});
	});
}

popup.removeButtons();
popup.addYesNoButtons(function() {
	checkInstall();
}, function() {
	content.innerHTML = "You must lock the campaign using the computer that you will use for travelling,<br/>so we can install this software on it.";
	layout.changed(content);
	popup.removeButtons();
	popup.addCloseButton();
});

</script>
<?php 
	}
}