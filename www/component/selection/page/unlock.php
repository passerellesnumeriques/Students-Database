<?php 
require_once("SelectionPage.inc");
class page_unlock extends SelectionPage {
	public function getRequiredRights() { return array("manage_selection_campaign"); }
	public function executeSelectionPage(){
		$reason = $this->component->getFrozenReason();
		if ($reason == "Campaign Finished") {
?>
<div style='background-color:white;padding:10px'>
	The campaign is currently locked because it is already finshed.<br/>
	Are you sure you want to unlock it ?
	<button class='action green' onclick="unlock();">Yes</button>
	<button class='action' onclick="closePopup();">No</button>
</div>
<script type='text/javascript'>
function unlock() {
	var popup = window.parent.getPopupFromFrame(window);
	popup.freeze(null, "Unlocking selection campaign...");
	service.json("selection","unlock_campaign",{},function(res) {
		if (!res) { popup.unfreeze(); return; }
		var win = window.top.frames["pn_application_frame"];
		win.sectionClicked(win.getSection('selection'));
		popup.close();
	});
}
function closePopup() {
	window.parent.getPopupFromFrame(window).close();
}
</script>
<?php 						
		} else if ($reason == "Selection Team Travelling") {
			$travel = SQLQuery::create()->bypassSecurity()->select("TravelVersion")->executeSingleRow();
			$user_id = $travel["user"];
			$synch_uid = $travel["uid"];
?>
<div style='background-color:white;padding:10px'>
<?php 
$people_id = PNApplication::$instance->user_management->getPeopleFromUser($user_id);
$people = PNApplication::$instance->people->getPeople($people_id, true);
$name = toHTML($people["first_name"]." ".$people["last_name"]);
if ($user_id <> PNApplication::$instance->user_management->user_id) {
	$he = ($people["sex"] == "M" ? "he" : "she");
	$him = ($people["sex"] == "M" ? "him" : "her");
	$his = ($people["sex"] == "M" ? "his" : "her");
	echo "This campaign is currently locked by $name because $he is travelling with the software.<br/>";
	echo "Only $him can unlock this campaign so $he can update the database with $his modifications.<br/><br/>";
	echo "Only in case of emergency (like the travelling version has been lost), you should unlock this campaign.<br/>";
	echo "But if you do so, $name won't be able to synchronize with the server,<br/>";
	echo "meaning all the modifications $he did on $his computer will be lost.<br/><br/>";
	echo "<button class='action red' onclick='unlock();'>Unlock anyway</button>";
} else {
	echo "<b>Welcome back $name !</b><br/>";
	echo "<br/>";
	echo "You probably want to unlock this campaign because you are back, so we need to get the modifications you made<br/>";
	echo "on the database, from your computer to the server.<br/>";
	echo "<br/>";
	echo "<div id='progress'>Connecting to your computer</div>";
} 
?>
</div>
<script type='text/javascript'>
function unlock() {
	var popup = window.parent.getPopupFromFrame(window);
	popup.freeze(null, "Unlocking selection campaign...");
	service.json("selection","unlock_campaign",{},function(res) {
		if (!res) { popup.unfreeze(); return; }
		var win = window.top.frames["pn_application_frame"];
		win.sectionClicked(win.getSection('selection'));
		popup.close();
	});
}
function connectToComputer() {
	var content = document.getElementById('progress');
	content.innerHTML = "Connecting to your computer";
	layout.changed(content);
	ajax.post("http://127.0.0.1:8888/server_comm/check_install",{},function(error) {
		content.innerHTML = 
			"We cannot find the Travelling version of this software on your computer.<br/>"+
			"<br/>"+
			"If you are not using the computer with which you travelled, you must do this operation<br/>"+
			"using the computer containing the software you used during your travel.<br/>"+
			"<br/>"+
			"If you are currently on that computer, probably the software is not launched.<br/>"+
			"In this case, execute the file <b>start.bat</b> located in the directory C:\\SelectionToolTravel to start the software.<br/>"+
			"Then <button class='action' onclick='connectToComputer();'>Try Again</button><br/>"+
			"<br/>"+
			"If you lost your computer, or you don't want to synchronize, you can unlock the campaign<br/>"+
			"without synchronization. In this case, all modifications that you made on your computer<br/>"+
			"will be lost!<br/>"+
			"<button class='action red' onclick='unlock();'>Unlock campaign without synchronization</button>";
		layout.changed(content);
	},function(xhr) {
		var version = xhr.responseText;
		ajax.post("http://127.0.0.1:8888/server_comm/check_synch",{},function(error) {
			content.innerHTML = "An error occured while connecting to your computer<br/>"+
				"<button class='action' onclick='connectToComputer();'>Try Again</button><br/>";
			layout.changed(content);
		},function(xhr) {
			var synch_uid = xhr.responseText;
			if (synch_uid != <?php echo json_encode($synch_uid);?>) {
				content.innerHTML =
					"The software on this computer is not the one used when you locked the campaign.<br/>"+
					"You must use the same computer.";
				layout.changed(content);
				return;
			}
			var server_version = <?php global $pn_app_version; echo json_encode($pn_app_version);?>;
			if (version != server_version) {
				content.innerHTML = 
					"The server has been updated to version "+server_version+" during your travel and your computer is using version "+version+"<br/>"+
					"We need to update your computer to version "+server_version+" before we can synchronize the database<br/>"+
					"<br/>"+
					"<button class='action' onclick=\"upgrade('"+version+"','"+server_version+"');\">Upgrade my computer</button><br/>"+
					"<br/>"+
					"If you don't want to synchronize your computer and only want to unlock this campaign<br/>"+
					"<button class='action red' onclick='unlock();'>Unlock campaign without synchronization</button><br/>"+
					"<span style='font-weight:bold;color:red'>but note that all the modifications you made on your computer will be lost!</span>"
					;
				layout.changed(content);
			} else {
				content.innerHTML =	
					"<button class='action' onclick='synch();'>Synchronize database with my computer</button><br/>"+
					"<br/>"+
					"<button class='action red' onclick='unlock();'>Unlock campaign without synchronization</button><br/>"+
					"<span style='font-weight:bold;color:red'>(all the modifications you made on your computer will be lost)</span>";
				layout.changed(content);
			}
		});
	});
}
function upgrade(computer_version, server_version) {
	var content = document.getElementById('progress');
	content.innerHTML = "Upgrade of Students Management Software on your computer in progress<br/>It may take few minutes...<br/><br/>";
	layout.changed(content);
	require("progress_bar.js",function() {
		var text = document.createElement("DIV");
		var pb = new progress_bar(250,20);
		content.appendChild(text);
		content.appendChild(pb.element);
		pb.element.style.display = "none";
		pb.element.style.marginTop = "2px";
		text.innerHTML = "Starting download";
		layout.changed(text);
		ajax.post("http://127.0.0.1:8888/server_comm/update_sms",{server:location.host,version:server_version,migrate_from:computer_version},function(error) {
			content.innerHTML = "An error occured while connecting to your computer: "+error;
			layout.changed(content);
		},function(xhr) {
			if (xhr.responseText == "OK") {
				connectToComputer();
				return;
			}
			if (xhr.responseText.substr(0,3) == "OK:") {
				var new_version = xhr.responseText.substr(3);
				if (new_version == server_version)
					connectToComputer();
				else
					upgrade(new_version, server_version);
				return;
			}
			content.innerHTML = xhr.responseText;
			var try_again = document.createElement("BUTTON");
			try_again.className = "action";
			try_again.innerHTML = "Try Again";
			content.appendChild(try_again);
			layout.changed(content);
			try_again.onclick = function() { upgrade(computer_version, server_version); };
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
function synch() {
	var content = document.getElementById('progress');
	content.innerHTML = "";
	require("progress_bar.js",function() {
		var text = document.createElement("DIV");
		var pb = new progress_bar(250,20);
		content.appendChild(text);
		content.appendChild(pb.element);
		pb.element.style.display = "none";
		pb.element.style.marginTop = "2px";
		text.innerHTML = "Initializing transfer...";
		layout.changed(content);
		service.json("selection","travel/init_synch",{},function(res) {
			if (!res) { popup.close(); return; }
			ajax.post("http://127.0.0.1:8888/server_comm/database_diff",{synch_key:res.synch_key,server:location.host},function(error) {
				alert(error);
			},function(xhr) {
				if (xhr.responseText != "OK") {
					content.innerHTML = xhr.responseText;
					layout.changed(content);
					return;
				}
				content.innerHTML = "Database successfully synchronized!";
				layout.changed(content);
				unlock();
			});
			var progress = function() {
				ajax.post("http://127.0.0.1:8888/server_comm/database_diff_progress",{},function(error){},function(xhr){
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
			setTimeout(progress, 500);
		});
	});
}
<?php if ($user_id == PNApplication::$instance->user_management->user_id) echo "connectToComputer();"; ?>
</script>
<?php 						
		}
	}
}