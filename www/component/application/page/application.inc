<?php 
theme::css($this, "application.css");

// mandatory part
$this->require_javascript("calendar.js");
?>
<script type='text/javascript'>
window.top.default_country_code = <?php $d = PNApplication::$instance->get_domain_descriptor(); echo json_encode($d["country_code"]).";"; ?>

if (!window.top.calendar_manager) {
	window.top.calendar_manager = new CalendarManager();
	window.top.CalendarsProviders.get(function(provider) {
		for (var i = 0; i < provider.calendars.length; ++i)
			window.top.calendar_manager.addCalendar(provider.calendars[i]);
		provider.on_calendar_added.add_listener(function(cal){
			window.top.calendar_manager.addCalendar(cal);
		});
		provider.on_calendar_removed.add_listener(function(cal){
			window.top.calendar_manager.removeCalendar(cal);
		});
	});
}
</script>
<?php
if (isset($_GET["page"])) {
	// only the frame
	echo "<iframe id='pn_application_content' name='pn_application_content' src='".$_GET["page"]."'></iframe>";
	return;
} 

$this->require_javascript("horizontal_layout.js");
$this->require_javascript("horizontal_menu.js");
?>
<table id='pn_application_container'>
	<tr><td>
	<div id='pn_application_header'>
		<div id='pn_application_section_title' onclick='sectionsMenu();'></div>
		<div id='pn_application_left_controls' style='visibility:hidden;padding-right:3px'></div>
		<div id='pn_application_menu' layout='fill'>
			<div class='button'><img src='<?php echo theme::$icons_16["more_menu"];?>'/> More</div>
		</div>
		<div id='pn_application_right_controls' style='visibility:hidden'></div>
		<div id='pn_application_right_menu' style='margin-right:3px'>
			<div id='user_picture' style='display:inline-block;' onclick='userMenu();'>
				<img src='/dynamic/people/service/picture?people=<?php echo PNApplication::$instance->user_people->user_people_id;?>' style='width:30px;height:30px'/>
			</div>
		</div>
	</div>
	</td></tr>
	<tr><td id='pn_application_content_area'>
		<iframe id='pn_application_content' name='pn_application_content'></iframe>
	</td></tr>
	<tr><td>
	<div id='pn_application_footer'>
		Copyright (c) 2013<?php $now = date("Y");if ($now <> "2013") echo "-".$now;?> - Passerelles numériques
		<span style='position:fixed;bottom:0px;right:3px;'>
			<span id='google_status'></span>
		</span>
		<?php if (PNApplication::$instance->current_domain == "Dev") { ?>
		<span style='position:fixed;bottom:0px;right:23px;'>
			<span id='debug_status'></span>
		</span>
		<?php } ?>

		<span style='position:fixed;bottom:0px;left:3px;' id='background_loading_application'>
		</span>
	</div>
	</td></tr>
</table>
<div id='user_menu' style='display:none'>
	<table>
	<tr>
		<td>
			<img src='/dynamic/people/service/picture?people=<?php echo PNApplication::$instance->user_people->user_people_id;?>' style='width:80px;height:80px'/>
		</td>
		<td>
			<a class='button_verysoft' href='/dynamic/people/page/profile?people=<?php echo PNApplication::$instance->user_people->user_people_id;?>' target='pn_application_content'>
				<img src='/static/people/profile_32.png'/>
				Your Profile
			</a><br/>
			<a class='button_verysoft' href='/dynamic/calendar/page/calendars' target='pn_application_content'>
				<img src='/static/calendar/calendar_32.png'/>
				Your Calendars
			</a><br/>
		</td>
	</tr>
	<tr>
		<td colspan=2>
			<a class='button_verysoft' href='/dynamic/application/page/logout'>
				<img src='/static/application/logout.gif'/>
				Log out and exit application
			</a>
		</td>
	</tr>
	</table>
</div>
<script type='text/javascript'>
sections = [<?php
require_once("component/application/ApplicationPlugin.inc");
$sections = array();
foreach (PNApplication::$instance->components as $c) {
	foreach ($c->getPluginImplementations() as $pi) {
		if (!($pi instanceof ApplicationPlugin)) continue;
		array_push($sections, $pi);
	}
}
function sort_sections($s1,$s2) {
	return $s1->getPriority() - $s2->getPriority();
}
usort($sections, "sort_sections");
$first = true;
foreach ($sections as $sec) {
	if ($first) $first = false; else echo ",";
	echo "{";
	echo "name:".json_encode($sec->getName());
	echo ",icon16:".json_encode($sec->getIcon16());
	echo ",icon32:".json_encode($sec->getIcon32());
	echo ",page:".json_encode($sec->getPageURL());
	echo ",info_text:".json_encode($sec->getInfoText());
	echo ",menu_provider:".json_encode($sec->getMenuProviderService());
	echo "}";
}
?>];

function sectionBox(sec) {
	var link = document.createElement("A");
	link.href = "/?section="+encodeURIComponent(sec.name);
	var div = document.createElement("DIV");
	div.style.display = "inline-block";
	div.className = "pn_application_section_box";
	var icon = document.createElement("IMG");
	icon.src = sec.icon32;
	div.appendChild(icon);
	var div_name = document.createElement("DIV");
	div_name.appendChild(document.createTextNode(sec.name));
	div.appendChild(div_name);
	var div_info = document.createElement("DIV");
	if (typeof sec.info_text != 'undefined') div_info.appendChild(document.createTextNode(sec.info_text));
	div.appendChild(div_info);
	link.onclick = function() {
		selectSection(sec);
		return false;
	};
	link.appendChild(div);
	return link;
}

function sectionsMenu() {
	var menu = document.createElement("DIV");
	for (var i = 0; i < sections.length; ++i) {
		if (i > 0 && (i%3)==0) menu.appendChild(document.createElement("BR"));
		var box = sectionBox(sections[i]);
		menu.appendChild(box);
	}
	menu.id = "pn_application_sections_menu";
	if (typeof animation != 'undefined') setOpacity(menu,0);
	document.body.appendChild(menu);
	if (typeof animation != 'undefined') animation.fadeIn(menu,300);
	var listener = function() {
		window.top.pnapplication.unregisterOnclick(listener);
		if (typeof animation != 'undefined') animation.fadeOut(menu,300,function() { document.body.removeChild(menu); });
		else document.body.removeChild(menu);		
	};
	setTimeout(function() { window.top.pnapplication.registerOnclick(window, listener); },1);
}

function userMenu() {
	var menu = document.getElementById('user_menu');
	if (typeof animation != 'undefined') animation.fadeIn(menu,300);
	else setOpacity(menu, 1);
	menu.style.display = "";
	var listener = function() {
		window.top.pnapplication.unregisterOnclick(listener);
		if (typeof animation != 'undefined') animation.fadeOut(menu,300,function() { menu.style.display = "none"; });
		else menu.style.display = "none";
	};
	setTimeout(function() { window.top.pnapplication.registerOnclick(window, listener); },1);
}

new horizontal_layout('pn_application_header');
var section_menu = new horizontal_menu('pn_application_menu');
require("vertical_align.js",function() {
	new vertical_align('pn_application_left_controls', 'middle');
	new vertical_align('pn_application_right_controls', 'middle');
});

function selectSection(sec) {
	document.getElementById('pn_application_content').src = sec.page;
	document.getElementById('pn_application_section_title').innerHTML = "<img src='"+sec.icon32+"'/>"+sec.name;
	populateMenu(sec.menu_provider);
	fireLayoutEventFor(document.getElementById('pn_application_header'));
}

function populateMenu(service_url) {
	resetAllMenus();
	if (service_url)
		ajax.call("GET", service_url, null, null, function(error){
			// TODO
		}, function(xhr) {
			eval('populating_menu = true;'+xhr.responseText+'populating_menu = false;');
		});
}

require("google_status.js",function() {
	new google_status('google_status');
});
<?php if (PNApplication::$instance->current_domain == "Dev") { ?>
require("debug_status.js",function() {
	new debug_status('debug_status');
});
<?php } ?>

function resetAllMenus() {
	resetLeftControls();
	resetMenu();
	resetRightControls();
}
function resetMenu() {
	for (var i = 0; i < section_menu.items.length; ++i)
		if (section_menu.items[i].element._tooltip)
			document.body.removeChild(section_menu.items[i].element._tooltip);
	section_menu.removeAll();
}
function resetLeftControls() {
	var div = document.getElementById('pn_application_left_controls');
	div.style.visibility = 'hidden';
	while (div.childNodes.length > 0) div.removeChild(div.childNodes[0]);
}
function resetRightControls() {
	var div = document.getElementById('pn_application_right_controls');
	div.style.visibility = 'hidden';
	while (div.childNodes.length > 0) div.removeChild(div.childNodes[0]);
}
function addMenuItem(icon, text, info, url) {
	var item = document.createElement("A");
	item.className = "pn_application_menu_item";
	var img = document.createElement("IMG");
	img.src = icon;
	item.appendChild(img);
	var span = document.createElement("SPAN");
	span.appendChild(document.createTextNode(text));
	item.appendChild(span);
	item.href = url;
	item.target = "pn_application_content";
	section_menu.addItem(item);
	fireLayoutEventFor(document.getElementById('pn_application_header'));
	item.onmouseover = function() {
		if (this._tooltip) return;
		for (var i = 0; i < section_menu.items.length; ++i)
			if (section_menu.items[i].element._tooltip) {
				document.body.removeChild(section_menu.items[i].element._tooltip);
				section_menu.items[i].element._tooltip = null;
			}
		if (info == null || info.length == 0) return;
		this._tooltip = document.createElement("DIV");
		this._tooltip.className = "pn_application_menu_item_tooltip";
		this._tooltip.style.position = "fixed";
		this._tooltip.style.top = "32px";
		this._tooltip.style.left = absoluteLeft(this)+"px";
		this._tooltip.appendChild(document.createTextNode(info));
		document.body.appendChild(this._tooltip);
	};
	item.onmouseout = function() {
		if (!item._tooltip) return;
		document.body.removeChild(item._tooltip);
		item._tooltip = null;
	};
	window.onmouseout = item.onmouseout;
	return item;
}
function addMenuControl(control) {
	if (typeof control == 'string') { var e = document.createElement("DIV"); e.style.display = "inline-block"; e.innerHTML = control; control = e; }
	section_menu.addItem(control);
	fireLayoutEventFor(document.getElementById('pn_application_header'));
}
function addRightControl(control) {
	if (typeof control == 'string') { var e = document.createElement("DIV"); e.style.display = "inline-block"; e.innerHTML = control; control = e; }
	var div = document.getElementById('pn_application_right_controls');
	div.appendChild(control);
	div.style.visibility = 'visible';
	setOpacity(div, 100);
	fireLayoutEventFor(document.getElementById('pn_application_header'));
}
function addLeftControl(control) {
	if (typeof control == 'string') { var e = document.createElement("DIV"); e.style.display = "inline-block"; e.innerHTML = control; control = e; }
	var div = document.getElementById('pn_application_left_controls');
	div.appendChild(control);
	div.style.visibility = 'visible';
	setOpacity(div, 100);
	fireLayoutEventFor(document.getElementById('pn_application_header'));
}

selectSection(sections[<?php
$sel_index = 0;
if (isset($_GET["section"]))
	for ($i = 0; $i < count($sections); $i++)
		if (strtolower($sections[$i]->getName()) == strtolower($_GET["section"])) { $sel_index = $i; break; }
echo $sel_index;  
?>]);

// background loading of the application
require("load_static.js",function(){
	new load_static_resources('background_loading_application');
});
require("animation.js");
require("context_menu.js");

</script>