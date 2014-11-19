<?php 
class page_overview extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->requireJavascript("section.js");
		theme::css($this, "section.css");
?>
<style type="text/css">
.section_box {
	display: inline-block;
    width: 130px;
    height: 130px;
    padding: 3px 3px 3px 3px;
    margin: 3px;
    border: 1px solid rgba(0,0,0,0);
    border-radius: 5px; 
    cursor: pointer;
    vertical-align: top;
    text-decoration: none;
}
.section_box:hover {
	border: 1px solid #808080;
	box-shadow: 2px 2px 2px 0px #C0C0C0;
}
.section_box:active {
	box-shadow: none;
	position: relative;
	top: 2px;
	left: 2px;
	background-color: #F0F0F0;
}
.section_box>div {
	text-align: center;
}
.section_box>div:nth-child(2) {
	color: black;
	font-size: 12pt;
	font-weight: bold;
}
.section_box>div:nth-child(3) {
	color: #808080;
}
.section_box.disabled {
	position: relative;
	opacity: 0.75;
	border: 1px solid #A0A0A0;
	background-color: #E0E0E0;
}
.section_box.disabled:hover {
	border: 1px solid #808080;
	box-shadow: none;
}
.section_box.disabled:active {
	top: 0px;
	left: 0px;
}
</style>
<div style="background-color: white">
	<div class="page_title">
		<img src='/static/application/logo.png' height="50px" style="vertical-align:bottom"/>
		Welcome in PN Students Management Software !
	</div>
<?php
if (PNApplication::$instance->user_management->username == "admin") {
	$no_pass = SQLQuery::create()
		->bypassSecurity()
#DEV
		->noWarning()
#END
		->select("InternalUser")->where("`username`='admin' AND `password`=SHA1('')")->executeSingleRow();
	if ($no_pass <> null) {
		echo "<div class='warning_box'>";
		echo "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> ";
		echo "You are logged with <i>admin</i> account, without password !";
		echo " You must set a password quickly for this user, as it has the right to access everything.<br/>";
		echo "To change your password, go to <a href='/dynamic/people/page/profile?people=".PNApplication::$instance->user_management->people_id."'>your profile page</a> and click on <i>Reset password</i>.";
		echo "</div>";
	}
}
if (PNApplication::$instance->current_domain <> PNApplication::$instance->local_domain) {
	echo "<div class='info_box'>";
	echo "<img src='".theme::$icons_16["info"]."' style='vertical-align:bottom'/> ";
	echo "You are connected on the database of ".PNApplication::$instance->current_domain.".";
	if (!file_exists("data/domains_synch/".PNApplication::$instance->current_domain."/latest_recover")) {
		echo " This database has never been synchronized, meaning it is empty.";
	} else {
		echo " This is only a copy of ".PNApplication::$instance->current_domain." data, meaning you can consult information, but you won't be able to modify anything. If you want to modify something, you must connect directly to ".PNApplication::$instance->current_domain.".";
		echo "<br/>";
		$info = include("data/domains_synch/".PNApplication::$instance->current_domain."/latest_recover");
		echo "Note that this is not the latest data: the data that we have from ".PNApplication::$instance->current_domain." are from ".date("d M Y g:i A", $info["time"]).".";
		echo " To consult the latest data you must connect directly to ".PNApplication::$instance->current_domain.".<br/>";
		echo "Please note also that you will have access only to the data ".PNApplication::$instance->current_domain." allows you to access. If you need more access, please contact ".PNApplication::$instance->current_domain." administrators to give you more privileges on their data.";
	}
	echo "</div>";
} 
?>
	<div class="page_section_title">
		Navigate into the different sections of the application
	</div>
	<div id="section_menu" class='light_shadow'>
		<?php
		$sections = array();
		foreach (PNApplication::$instance->components as $cname=>$comp)
			foreach ($comp->getPluginImplementations() as $pi)
				if ($pi instanceof ApplicationSectionPlugin)
					array_push($sections, $pi);
		usort($sections, function($s1, $s2) {
			if ($s1->getPriority() <= $s2->getPriority()) return -1;
			return 1;
		});
		foreach ($sections as $section) {
			if ($section->getId() == "home") continue; // skip the home as we are already there
			if ($section->canAccess())
				echo "<a class='section_box' href=\"".$section->getDefaultPageURL()."\">";
			else
			echo "<div class='section_box disabled'>";
			echo "<div><img src=\"".$section->getIcon32()."\"/></div>";
			echo "<div>".toHTML($section->getName())."</div>";
			echo "<div>".toHTML($section->getDescription())."</div>";
			if ($section->canAccess())
				echo "</a>";
			else {
				echo "<div style='position:absolute;bottom:0px;right:0px;'><img src='".theme::$icons_16["lock"]."'/></div>";
				echo "</div>";
			}
		}
		?>
	</div>
<?php if (PNApplication::$instance->google->isInstalled()) {
$google_account = PNApplication::$instance->google->getConnectedAccount();
if ($google_account == null || $google_account["google_login"] == null) {
?>
<div style='flex:none' class='page_section_title'>
	<img src='/static/google/google_32.png' style='vertical-align:bottom'/> <span onclick="window.top.google.connectAccount(function(){location.reload();});" style='cursor:pointer' onmouseover="this.style.textDecoration='underline';" onmouseout="this.style.textDecoration='';">Connect your PN Google account to this application</span>
</div>
<?php 
}
}
if (PNApplication::$instance->user_management->has_right("manage_application")) {
global $pn_app_version;
?>
<div style='flex:none;display:none;padding:5px 10px;' id='new_version_available'>
	<div class='info_box'><img src='<?php echo theme::$icons_16["info"];?>' style='vertical-align:bottom'/> A new update (version <span id='new_version'></span>) is available (current version is <?php echo $pn_app_version;?>). As an administrator of the application, you can update the software in the <a href='/dynamic/administration/page/app_admin'>administration section</a></div>
</div>
<script type='text/javascript'>
service.json("administration","latest_version",null,function(res) {
	if (res && res.version) {
		var our_version = <?php echo json_encode($pn_app_version);?>.split(".");
		var latest = res.version.split(".");
		var new_version = false;
		for (var i = 0; i < latest.length; ++i) {
			if (our_version.length <= i) { new_version = true; break; }
			if (parseInt(latest[i]) > parseInt(our_version[i])) { new_version = true; break; }
			if (parseInt(latest[i]) < parseInt(our_version[i])) { break; }
		}
		if (new_version) {
			document.getElementById('new_version').innerHTML = res.version;
			document.getElementById('new_version_available').style.display = "block";
		}
	}
});
service.json("administration","store_stats",null,function(res){});
</script>
<?php 
} else if (rand(0,9)==0) {
?>
<script type='text/javascript'>
service.json("administration","store_stats",null,function(res){});
</script>
<?php 
}
if (@$_COOKIE["test_deploy"] == "true") {
?>
<div style='flex:none;padding:5px 10px;'>
	<div class='info_box'>
		<img src='<?php echo theme::$icons_16["info"];?>' style='vertical-align:bottom'/> 
		You are testing a version for deployment. <a href='#' onclick="document.cookie = 'test_deploy=; Path=/';window.location.assign('/');return false;">Stop testing this version, and go back to development version</a>
	</div>
</div>
<?php 
}
?>
	<div class="page_section_title" style='margin-bottom:0px'>
		What's happening ?
	</div>
	<div style='background-color:#e8e8e8;padding-top:10px;display:flex;flex-direction:row;'>
		<div id="updates" style="flex:1 1 auto;min-width:300px;"
			icon="/static/news/news.png"
			title="Latest Updates"
		>
			<div id='updates_container' style='padding:2px;'>
				<img src='/static/news/loading.gif' id='updates_loading'/>
			</div>
		</div>
		<div id="activities" style="flex:1 1 auto;min-width:300px;"
			icon="/static/news/news.png"
			title="Latest Activities"
		>
			<div id='activities_container' style='padding:2px;'>
				<img src='/static/news/loading.gif' id='activities_loading'/>
			</div>
		</div>
		<div id="calendar_events" style="flex:1 1 auto;align-self:flex-start;margin-left:5px;vertical-align:top;min-width:300px;"
			icon="/static/calendar/calendar_16.png"
			title="Upcoming Events"
		>
			<div id='calendars_container' style='width:100%'><img src='<?php echo theme::$icons_16["loading"];?>'/></div>
			<a href='/dynamic/calendar/page/calendars'>Show calendars</a>
		</div>
	</div>
</div>
<script type='text/javascript'>
window.calendars_section = sectionFromHTML('calendar_events');
window.updates_section = sectionFromHTML('updates');
window.activities_section = sectionFromHTML('activities');

require("calendar_view.js");
require("calendar_view_week.js");
function init_calendars() {
	var icon_loading = document.createElement("IMG");
	icon_loading.style.verticalAlign = "middle";
	icon_loading.src = theme.icons_16.loading_white;
	icon_loading.style.visibility = 'hidden';
	icon_loading.style.display = 'none';
	icon_loading.counter = 0;
	window.calendars_section.addToolRight(icon_loading);
	var refresh_listener = function() {
		icon_loading.counter++;
		icon_loading.style.visibility = 'visible';
		icon_loading.style.display = '';
	};
	var refresh_done_listener = function() {
		if (--icon_loading.counter == 0) {
			icon_loading.style.visibility = 'hidden';
			icon_loading.style.display = 'none';
		}
	};
	window.top.calendar_manager.on_refresh.add_listener(refresh_listener);
	window.top.calendar_manager.on_refresh_done.add_listener(refresh_done_listener);
	require("calendar_view.js",function() {
		new CalendarView(window.top.calendar_manager, "upcoming", 7, 'calendars_container', function() {
		});
	});
	var providers = [];
	var new_calendar = function(cal) {
		for (var i = 0; i < providers.length; ++i) {
			if (providers[i].provider.id == cal.provider.id) {
				for (var j = 0; j < providers[i].calendars.length; ++j)
					if (providers[i].calendars[j].id == cal.id) return; // already counted
				var nb = parseInt(providers[i].span_nb.innerHTML);
				providers[i].span_nb.innerHTML = ""+(nb+1);
				providers[i].calendars.push(cal);
				return;
			}
		}
		var provider = {provider:cal.provider,calendars:[cal]};
		var div = document.createElement("BUTTON");
		div.innerHTML = "<img src='"+cal.provider.getProviderIcon()+"' width=16px height=16px style='vertical-align:bottom'/> (";
		provider.span_nb = document.createElement("SPAN");
		provider.span_nb.innerHTML = "1";
		div.appendChild(provider.span_nb);
		div.appendChild(document.createTextNode(")"));
		div.title = cal.provider.getProviderName();
		div.style.margin = "2px";
		div.style.paddingRight = "5px";
		calendars_section.addTool(div);
		div.provider = provider;
		div.onclick = function() {
			var t=this;
			require(["popup_window.js","calendar.js"],function() {
				var content = document.createElement("DIV");
				content.style.padding = "5px";
				for (var i = 0; i < t.provider.calendars.length; ++i)
					new CalendarControl(content, t.provider.calendars[i], window.top.calendar_manager);
				var popup = new popup_window(t.provider.provider.getProviderName(), t.provider.provider.getProviderIcon(), content);
				popup.show();
			});
		};
		div.ondomremoved(function() {
			div.provider = null;
		});
		providers.push(provider);
		layout.changed(calendars_section.element);
	};
	for (var i = 0; i < window.top.calendar_manager.calendars.length; ++i)
		new_calendar(window.top.calendar_manager.calendars[i]);
	window.top.calendar_manager.on_calendar_added.add_listener(new_calendar);
	window.pnapplication.onclose.add_listener(function() {
		if (!window.top.calendar_manager) return;
		window.top.calendar_manager.on_refresh.remove_listener(refresh_listener);
		window.top.calendar_manager.on_refresh_done.remove_listener(refresh_done_listener);
		window.top.calendar_manager.on_calendar_added.remove_listener(new_calendar);
	});
}
window.top.require("calendar.js",function() {
	setTimeout(init_calendars,1);
});

window.updates = null;
window.activities = null;
require("news.js",function() {
	window.updates = new news('updates_container', [], null, 'update', function(n) {
		var loading = document.getElementById('updates_loading');
		loading.parentNode.removeChild(loading);
	});
	window.activities = new news('activities_container', [], null, 'activity', function(n) {
		var loading = document.getElementById('activities_loading');
		loading.parentNode.removeChild(loading);
	});
	var post_button = document.createElement("BUTTON");
	post_button.className = "flat icon";
	post_button.innerHTML = "<img src='/static/news/write_16.png'/>";
	post_button.title = "Post a message";
	window.updates_section.addToolRight(post_button);
	post_button.onclick = function() {
		window.updates.post();
	};
});
</script>
<?php 		
	}
	
}
?>