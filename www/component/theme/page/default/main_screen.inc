<?php 
$this->requireJavascript("frame_header.js");
theme::css($this, "frame_header.css");
?>
<div id='pn_application_container'>
</div>
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
header = new frame_header('pn_application_container','pn_application_content',35,'application','bottom');
var footer = header.addFooter();
footer.style.position = "relative";
footer.innerHTML = "Copyright (c) 2013<?php $now = date("Y");if ($now <> "2013") echo "-".$now;?> - Passerelles numériques";
var span_google_status = document.createElement("SPAN");
span_google_status.style.position = "absolute";
span_google_status.style.bottom = "0px";
span_google_status.style.right = "3px";
footer.appendChild(span_google_status);
<?php if (PNApplication::$instance->current_domain == "Dev") { ?>
var span_debug_status = document.createElement("SPAN");
span_debug_status.style.position = "absolute";
span_debug_status.style.bottom = "0px";
span_debug_status.style.right = "23px";
footer.appendChild(span_debug_status);
<?php } ?>
var span_background_loading = document.createElement("SPAN"); 
span_background_loading.style.position = "absolute";
span_background_loading.style.bottom = "0px";
span_background_loading.style.left = "3px";
footer.appendChild(span_background_loading);


user_picture = document.createElement("IMG");
user_picture.src = "/dynamic/people/service/picture?people=<?php echo PNApplication::$instance->user_people->user_people_id;?>";
user_picture.id = 'user_picture';
user_picture.onclick = function() { userMenu(); };
tooltip(user_picture, "Your menu: profile, calendars...");
header.addRightControl(user_picture);

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
	var listener = function() {
		window.top.pnapplication.unregisterOnclick(listener);
		if (typeof animation != 'undefined') animation.fadeOut(menu,300,function() { document.body.removeChild(menu); });
		else document.body.removeChild(menu);		
	};
	for (var i = 0; i < sections.length; ++i) {
		if (i > 0 && (i%3)==0) menu.appendChild(document.createElement("BR"));
		var box = sectionBox(sections[i]);
		box.click = box.onclick;
		box.onclick = function() {
			listener();
			return this.click();
		};
		menu.appendChild(box);
	}
	menu.id = "pn_application_sections_menu";
	if (typeof animation != 'undefined') setOpacity(menu,0);
	document.body.appendChild(menu);
	if (typeof animation != 'undefined') animation.fadeIn(menu,300);
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

function selectSection(sec) {
	header.frame.src = sec.page;
	header.setTitle(sec.icon32, sec.name);
	populateMenu(sec.menu_provider);
	header.header_title.onclick = function() {
		sectionsMenu();
	};
	tooltip(header.header_title, "Application Menu");
	layout.invalidate(header.container);
}

function populateMenu(service_url) {
	if (service_url)
		ajax.call("GET", service_url, null, null, function(error){
			// TODO
		}, function(xhr) {
			eval('window.populating_menu = true;'+xhr.responseText+';window.populating_menu = false;');
		});
}

require("google_status.js",function() {
	new google_status(span_google_status);
});
<?php if (PNApplication::$instance->current_domain == "Dev") { ?>
require("debug_status.js",function() {
	new debug_status(span_debug_status);
});
<?php } ?>

function resetAllMenus() {
	resetLeftControls();
	resetMenu();
	resetRightControls();
}
function resetMenu() {
	header.resetMenu();
}
function resetLeftControls() {
	header.resetLeftControls();
}
function resetRightControls() {
	header.resetRightControls();
	header.addRightControl(user_picture);
}
function addMenuItem(icon, text, info, url, start_url, button_type) {
	return header.addMenu(generateID(), icon, text, url, info, start_url, button_type);
}
function addMenuControl(control) {
	header.addMenuControl(control);
}
function addRightControl(control, tooltip_text) {
	header.addRightControl(control, tooltip_text);
}
function addLeftControl(control, tooltip_text) {
	header.addLeftControl(control, tooltip_text);
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
	var loading = new load_static_resources(span_background_loading);
	loading._slow_when_user_active = true;
	loading._slow_when_services_active = true;
});
require("animation.js");
require("context_menu.js");

</script>
