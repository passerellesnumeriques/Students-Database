<?php 
require_once("SelectionPage.inc");
class page_travel_copy extends SelectionPage {
	public function getRequiredRights() { return array("can_access_selection_data"); }
	public function executeSelectionPage(){
		$campaign = SQLQuery::create()->select("SelectionCampaign")->whereValue("SelectionCampaign","id",$this->component->getCampaignId())->executeSingleRow();
		$travel_version = SQLQuery::create()->bypassSecurity()->select("TravelVersion")->executeSingleRow();
		$people_id = PNApplication::$instance->user_management->getPeopleFromUser($travel_version["user"]);
		$people = PNApplication::$instance->people->getPeople($people_id, true);
		$him = $people["sex"] == "F" ? "her" : "him";
		$his = $people["sex"] == "F" ? "her" : "his";
		
		global $pn_app_version;
		require_once 'update_urls.inc';
		$www_filename = "Students_Management_Software_".$pn_app_version."_Selection_Travel.zip";
		$www_url = getUpdateURL($www_filename);
		$server_filename = "SelectionToolTravel_WebServer.zip";
		$server_url = "http://sourceforge.net/projects/studentsdatabase/files/$server_filename/download";
		
?>
<div style='background-color:white;padding:10px' id='content'>
	<?php echo toHTML($people["first_name"]." ".$people["last_name"]);?> is currently travelling with the database of campaign <?php echo toHTML($campaign["name"]);?>.<br/>
	Only <?php echo $him;?> can modify this campaign on <?php echo $his;?> computer.<br/>
	<br/>
	But if you need to travel as well with the data, you can create a copy of the database on your computer.<br/>
	However, any modification you will do on your computer will remain on your computer, meaning you should not<br/>
	modify data on your computer, and use this copy only to ease your work during your travel (for example<br/>
	to apply eligibility rules after an exam).<br/>
	<br/>
	<button class='action' onclick='createCopy();'>Create a travel copy on my computer</button>
</div>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);
var content = document.getElementById('content');
var server_version = <?php echo json_encode($pn_app_version);?>;

function createCopy() {
	popup.removeButtons();
	content.innerHTML = "Are you the one who will use the software during the travel ?";
	layout.changed(content);
	popup.addYesNoButtons(function() {
		content.innerHTML = "Are you currently on the computer with which you will travel ?";
		layout.changed(content);
		popup.removeButtons();
		popup.addYesNoButtons(function() {
			checkInstall();
		}, function() {
			content.innerHTML = "You must use this functionality on the computer that you will use for travelling,<br/>so we can install this software on it.";
			layout.changed(content);
			popup.removeButtons();
			popup.addCloseButton();
		});
	}, function() {
		content.innerHTML = "You must use this functionality with the user who will be the one using the software during the travel.";
		layout.changed(content);
		popup.removeButtons();
		popup.addCloseButton();
	});
		
}

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
			"<li>Execute the file <b>start.bat</b> located in the directory C:\\SelectionToolTravel to start the software on your computer</li>"+
			"<li>Finally, click on the button 'try again' below</li>"+
			"</ol>"+
			"<br/>"+
			"If you already installed it, please start it by executing the file <b>start.bat</b> located in the directory <b>C:\\SelectionToolTravel</b><br/>"+
			"Once done, try again.<br/>"+
			"<br/>"+
			"<button class='action' onclick='checkInstall();'>Try Again</button>";
		layout.changed(content);
	},function(xhr) {
		var version = xhr.responseText;
		ajax.post("http://127.0.0.1:8888/server_comm/check_synch",{},function(error) {
			content.innerHTML = "An error occured while connecting to your computer<br/>"+
				"<button class='action' onclick='checkInstall();'>Try Again</button><br/>";
			layout.changed(content);
		},function(xhr) {
			var synch_uid = xhr.responseText;
			if (synch_uid == <?php echo json_encode($travel_version["uid"]);?>) {
				content.innerHTML =
					"The software on this computer is already the one used when you locked the campaign.<br/>"+
					"This computer is already ready to travel, with a version that you can modify and synchronize when you will be back.";
				layout.changed(content);
				return;
			}
		
			if (version != server_version) {
				upgradeVersion(version);
				return;
			}
			synchronizeDatabases();
		});
	});
}

function upgradeVersion(version) {
	if (version.length == 0)
		content.innerHTML = 
			"This is the first time you installed the software on your computer, and we need to<br/>"+
			"download the latest version of Students Management Software for Travel<br/><br/>";
	else
		content.innerHTML = 
			"The version on your computer ("+version+") is outdated and we need to update it to version "+server_version+"<br/><br/>";
	var text = document.createElement("DIV");
	text.style.marginBottom = "2px";
	content.appendChild(text);
	layout.changed(content);
	require("progress_bar.js", function() {
		var pb = new progress_bar(400,20);
		text.innerHTML = "Starting download...";
		pb.element.style.display = "none";
		content.appendChild(pb.element);
		layout.changed(text);
		ajax.post("http://127.0.0.1:8888/server_comm/update_sms",{server:location.host,version:server_version},function(error) {
			content.innerHTML = "An error occured while connecting to your computer: "+error;
			layout.changed(content);
		},function(xhr) {
			if (xhr.responseText == "OK") {
				synchronizeDatabases();
				return;
			}
			content.innerHTML = xhr.responseText;
			var try_again = document.createElement("BUTTON");
			try_again.className = "action";
			try_again.innerHTML = "Try Again";
			content.appendChild(try_again);
			layout.changed(content);
			try_again.onclick = function() { upgradeVersion(version); };
		});
		var progress = function() {
			ajax.post("http://127.0.0.1:8888/server_comm/update_sms_progress",{},function(error){},function(xhr){
				if (!text.parentNode) return;
				var s = xhr.responseText;
				if (s.substring(0,1) == "%") {
					s = s.substring(1);
					var i = s.indexOf('%');
					var s2 = s.substr(0,i);
					s = s.substr(i+1);
					if (pb) {
						i = s2.indexOf(',');
						pb.setTotal(parseInt(s2.substr(i+1)));
						pb.setPosition(parseInt(s2.substr(0,i)));
						pb.element.style.display = "";
					}
				} else
					pb.element.style.display = "none";
				text.innerHTML = s;
				layout.changed(content);
				setTimeout(progress,1000);
			});
		};
		setTimeout(progress, 1000);
	});
}

function synchronizeDatabases() {
	theme.css("progress_bar.css");
	content.innerHTML = "Synchronizing your computer with the Database.<br/>This may take several minutes...<br/><br/><div id='progress_text'></div><div id='progress_bar' style='display:none'></div>";
	layout.changed(content);
	service.json("selection","travel/get_copy_token",{},function(res) {
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
						pb.setTotal(parseInt(s2.substr(i+1)));
						pb.setPosition(parseInt(s2.substr(0,i)));
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
			campaign:<?php echo PNApplication::$instance->selection->getCampaignId();?>,
			synch_uid:"travel_copy_only"
		},function(error) {
			content.innerHTML = "An error occured during the synchronization: "+error;
			layout.changed(content);
		},function(xhr) {
			if (xhr.responseText != "OK") {
				content.innerHTML = "An error occured during the synchronization of your computer:<br/>"+xhr.responseText;
				layout.changed(content);
				return;
			}
			content.innerHTML = "Your computer is ready to travel.<br/>"+
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

</script>
<?php 
	}
}