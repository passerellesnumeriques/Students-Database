<?php
class page_reset_db extends Page {
	
	public function getRequiredRights() {
		return array();
	}
	
	protected function execute() {
?>
<div id='reset_db'>
Do you want to save the current database first ?<br/>
<button onclick='backup();'>Yes</button> <button onclick='reset();'>No</button>
</div>
<script type='text/javascript'>
var container = document.getElementById('reset_db'); 
function backup() {
	container.innerHTML = "Backuping database...";
	service.json("development","backup",{},function(res) {
		if (!res) { container.innerHTML = "Error during backup"; return; }
		reset();
	});
}
function reset() {
	container.innerHTML = "Reset database...";
	service.json("development","create_db",{domain:<?php echo json_encode(PNApplication::$instance->local_domain);?>},function(res) {
		if (!res) { container.innerHTML = "Error while resetting database"; return; }
		reset_storage();
	});
}
function reset_storage() {
	container.innerHTML = "Removing stored files...";
	service.json("development","reset_storage",{domain:<?php echo json_encode(PNApplication::$instance->local_domain);?>},function(res) {
		if (!res) { container.innerHTML = "Error while removing stored files"; return; }
		init_data();
	});
}
function init_data() {
	container.innerHTML = "Creating initial data...";
	service.json("development","init_data",{domain:<?php echo json_encode(PNApplication::$instance->local_domain);?>},function(res) {
		if (!res) { container.innerHTML = "Error while creating initial data"; return; }
		get_backups();
	});
}
function get_backups() {
	container.innerHTML = "Database reset.<br/>Retrieving list of available backups...";
	service.json("development","get_backups",{},function(res) {
		if (!res) res = [];
		show_backups(res);
	});
}
function show_backups(list) {
	container.innerHTML = "You can restore data from a previous backup (it may fail if the backup is not compatible with current data model):";
	var ul = document.createElement("UL");
	container.appendChild(ul);
	for (var i = 0; i < list.length; ++i) {
		var li = document.createElement("LI");
		var link = document.createElement("A");
		link.href = "#";
		link.innerHTML = new Date(parseInt(list[i].time)*1000).toLocaleString()+" (v."+list[i].version+")";
		li.appendChild(link);
		ul.appendChild(li);
		link._version = list[i].version;
		link._time = list[i].time;
		link.onclick = function() {
			var locker = lockScreen(null, "Recovering data from backup...");
			service.json("development","recover",{version:this._version,time:this._time},function(res) {
				unlockScreen(locker);
			});
			return false;
		};
	}
	container.appendChild(document.createTextNode("Or you can insert just some test data: "));
	var link = document.createElement("A");
	link.innerHTML = "Yes";
	link.href = "#";
	container.appendChild(link);
	link.onclick = function() {
		var link = this;
		var locker = lockScreen(null, "Inserting test data");
		service.json("development","test_data",{domain:<?php echo json_encode(PNApplication::$instance->local_domain);?>,password:""},function(res) {
			link.parentNode.insertBefore(document.createTextNode("Test data inserted"), link);
			link.parentNode.removeChild(link);
			link.onclick = function() { return false; };
			unlockScreen(locker);
		});
		return false;
	};
}
</script>
<?php
	}
	
}
?>