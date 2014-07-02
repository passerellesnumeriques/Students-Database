<?php
$this->requireJavascript("vertical_layout.js");
$this->requireJavascript("horizontal_layout.js");
$this->onload("new vertical_layout('application_container');");
$this->onload("new horizontal_layout('application_horizontal');");
$this->onload("new vertical_layout('application_left_side',true);");
$this->onload("new vertical_layout('application_right_side');");
$this->onload("new horizontal_layout('application_header');");
$this->requireJavascript("fill_height_layout.js");
?>
<style type="text/css">
#application_container {
	width: 100%;
	height: 100%;
	background-color:#009DE1;
	z-index: 3;
}

/* Left Side */
#application_left_side {
	height: 100%;
	overflow-x: visible;
}
#application_title {
	white-space: nowrap;
	height: 49px;
	border-bottom: 1px solid #50d0f0;
	border-bottom-right-radius: 10px;
	padding-right: 5px;
	position: relative;
}
#application_title>img {
	height: 45px;
	margin:1px 1px 1px 2px;
	vertical-align:top;
}
#application_title_text {
	display:inline-block;
	font-size:12pt;
	font-family:Verdana;
	font-style: oblique;
	color:black;
	text-shadow: #50d0f0 0.1em 0.1em 0.1em;
	font-weight:bold;
	padding-top: 5px;
}
#application_left_menu {
	padding-top: 10px;
	white-space: nowrap;
	color: white;
	font-weight: bold;
	font-family: Verdana;
}

/* Right Side */
#application_right_side {
	height: 100%;
}
#application_header {
	height:27px;
}
#application_page {
	background-color: #e8e8e8;
	vertical-align: top;
	width: 100%;
	border-bottom-right-radius: 5px;
}
#application_frame {
	border-radius: 5px;
	z-index: 1;
}
#application_footer {
	height: 16px;
	text-align: center;
	font-size: 9pt;
	font-family: Verdana;
	color: white;
}

#application_menu {
	text-align: center;
}
.application_menu_item {
	display:inline-block;
	height:24px;
	color: white;
	font-family:Verdana;
	font-weight:bold;
	padding: 3px 2px 0px 2px;
	cursor: pointer;
}
.application_menu_item>img {
	vertical-align:bottom;
	margin-right: 1px;
}
.application_menu_item:hover {
	background-color: rgba(255,255,255,0.3);
}
.application_menu_item.selected {
	background-color: #22bbea;
	position: relative;
}
.application_menu_item.selected:before {
    position: absolute;
    display: inline-block;
    border-left: 7px solid transparent;
    border-bottom: 7px solid #e8e8e8;
    border-right: 7px solid transparent;
    border-bottom-color: #e8e8e8;
    margin-left: -3px;
    left: 50%;
    bottom: 0px;
    content: '';
}
#application_right_menu #user_picture {
	width: 25px;
	height:25px;
    border: 1px solid rgba(0,0,0,0);
    cursor: pointer;
    border-radius: 5px;
    vertical-align: top;
}
#application_right_menu #user_picture:hover {
    background-color: rgba(255,255,255,0.4);
    border: 1px solid rgba(255,255,255,0.6);
}
#application_right_menu>img {
	vertical-align:middle;
	margin-right: 3px;
}
#application_search {
	border-radius: 7px;
	background-color: white;
	color: black;
	border: 1px solid transparent;
	width: 100px;
	margin-top: 4px;
	vertical-align: top;
	background: #ffffff url('/static/application/menu/search.png') no-repeat 3px 1px;
	padding-left: 20px;
	font-size: 9pt;
}

.application_left_menu_item {
	padding: 1px 5px 1px 5px;
	height: 18px;
	position: relative;
	border-left: 5px solid transparent;
	cursor: pointer;
	display: block;
	color: white;
	font-weight: bold;
	font-family: Verdana;
	text-decoration: none;
}
.application_left_menu_item>img {
	vertical-align: bottom;
}
.application_left_menu_item:hover {
	background-color: rgba(255,255,255,0.3);
	border-left: 5px solid #0080C0;
}
.application_left_menu_item.selected {
	background-color: #ff9933;
	border-left: 5px solid #CC6600;
}
.application_left_menu_item.selected:after {
    position: absolute;
    display: inline-block;
    border-top: 10px solid transparent;
    border-left: 5px solid #ff9933;
    border-bottom: 10px solid transparent;
    border-left-color: #ff9933;
    right: -5px;
    top: 0px;
    content: '';
    z-index: 80;
}
.application_left_menu_separator {
	height: 1px;
	width: 90%;
	background-color: white;
	margin: 5px 0px 5px 5%;
}
</style>
<div id="application_container">
	<div id="application_horizontal" layout="fill">
		<div id='application_left_side'>
			<div id='application_title'>
				<img src='/static/application/logo.png'/>
				<div id="application_title_text">Students<br/>Management</div>
			</div>
			<div id="application_left_menu" layout="fill">
			</div>
		</div>
		<div id='application_right_side' layout="fill">
			<div id="application_header">
				<div id="application_menu" layout="fill">
					<div class="application_menu_item" 
						section="home" 
						components="application,calendar,people"
						menu="/dynamic/application/service/home_menu"
						page="/dynamic/application/page/overview"
					>
						<img src='/static/application/home_white.png'/>
						Home
					</div>
					<div class="application_menu_item" 
						section="selection"
						components="selection"
						menu="/dynamic/selection/service/menu"
						page="/dynamic/selection/page/selection_main_page"
					>
						<img src='/static/selection/selection_white.png'/>
						Selection
					</div>
					<div class="application_menu_item" 
						section="training"
						components="training,transcripts,curriculum"
						menu="/dynamic/training/service/menu"
						page="/dynamic/curriculum/page/tree_frame#/dynamic/students/page/list"
					>
						<img src='/static/training/training_white.png'/>
						Training
					</div>
					<div class="application_menu_item" 
						section="education"
						components="education,health,discipline"
					>
						<img src='/static/education/education_white.png'/>
						Education
					</div>
					<div class="application_menu_item" 
						section="internship"
					>
						<img src='/static/application/menu/internship.png'/>
						Internship
					</div>
					<div class="application_menu_item" 
						section="alumni"
					>
						<img src='/static/application/menu/alumni.png'/>
						Alumni
					</div>
					<div class="application_menu_item" 
						section="administration"
						components="administration,geography,user_management"
						menu="/dynamic/administration/service/menu"
						page="/dynamic/administration/page/dashboard"
					>
						<img src='/static/administration/administration_white.png'/>
						Admin
					</div>
				</div>
				<div id="application_right_menu">
					<input type='text' id='application_search'/>
					<a href='/dynamic/people/page/profile?people=<?php echo PNApplication::$instance->user_people->user_people_id;?>' target='application_frame'><img id='user_picture'/></a>
					<button class="flat"><img id='notifications' src='/static/application/menu/notifications.png'/></button>
					<a href='/dynamic/calendar/page/calendars' target='application_frame'><button class="flat"><img id='calendars' src='/static/calendar/calendar_white.png'/></button></a>
					<a href='/dynamic/application/page/logout' target='pn_application_frame'><button class="flat"><img id='logout' src='/static/application/logout_white.png'/></button></a>
				</div>
			</div>
			<div id="application_frame_container" style="overflow:hidden" layout="fill">
				<iframe id="application_frame" name="application_frame" style="border:none;width:100%;height:100%;"></iframe>
			</div>
		</div>
	</div>
	<div id="application_footer">
			Copyright (c) 2013<?php $now = date("Y");if ($now <> "2013") echo "-".$now;?> - Passerelles numériques
	</div>
</div>
<script type='text/javascript'>
user_picture = document.getElementById('user_picture');
user_picture.src = "/dynamic/people/service/picture?people=<?php echo PNApplication::$instance->user_people->user_people_id;?>";
tooltip(user_picture, "Your profile");

tooltip(document.getElementById('notifications'), "See notifications");
tooltip(document.getElementById('calendars'), "Your calendars");
tooltip(document.getElementById('logout'), "Logout and exit application");

function initPage() {
	var menu = document.getElementById('application_menu');
	for (var i = 0; i < menu.childNodes.length; ++i) {
		var s = menu.childNodes[i];
		if (s.nodeType != 1) continue;
		s.onclick = function() { sectionClicked(this); };
	}
	var frame = document.getElementById('application_frame');
	listenEvent(frame,'load',function() {
		pageLoaded(getIFrameWindow(this).location.href);
	});
}

function getSection(section_name) {
	section_name = section_name.toLowerCase();
	var section = null;
	var menu = document.getElementById('application_menu');
	for (var i = 0; i < menu.childNodes.length; ++i) {
		var s = menu.childNodes[i];
		if (s.nodeType != 1) continue;
		if (section == null || s.getAttribute("section") == section_name) section = s;
	}
	return section;
}

function highlightSection(section) {
	var menu = document.getElementById('application_menu');
	for (var i = 0; i < menu.childNodes.length; ++i) {
		var s = menu.childNodes[i];
		s.className = "application_menu_item"+(s == section ? " selected" : "");
	}
}

function selectSection(section) {
	highlightSection(section_name);
}

function sectionClicked(section, page) {
	document.getElementById('application_frame')._loading_application_page = true;
	highlightSection(section);
	if (section.hasAttribute("menu"))
		loadMenu(section.getAttribute("menu"));
	if (page)
		loadPage(page);
	else if (section.hasAttribute("page"))
		loadPage(section.getAttribute("page"));
}

var menu_url = null;
function reloadMenu() {
	loadMenu(menu_url);
}

function loadMenu(url) {
	menu_url = url;
	var menu_container = document.getElementById('application_left_menu');
	menu_container.innerHTML = "<div style='text-align:center'><img src='"+theme.icons_16.loading_white+"'/></div>";
	var head = document.getElementsByTagName("HEAD")[0];
	for (var i = 0; i < head.childNodes.length; ++i) {
		if (head.childNodes[i].nodeType != 1) continue;
		if (head.childNodes[i].nodeName != "SCRIPT") continue;
		if (!head.childNodes[i]._is_menu) continue;
		head.removeChild(head.childNodes[i]);
		i--;
	}
	ajax.call("GET", url, null, null, 
		function(error){
			menu_container.innerHTML = "<img src='"+theme.icons_16.error+"'/> "+error;
		},
		function(xhr){
			menu_container.innerHTML = xhr.responseText;
			var scripts = menu_container.getElementsByTagName("SCRIPT");
			var list = [];
			for (var i = 0; i < scripts.length; ++i) list.push(scripts[i]);
			for (var i = 0; i < list.length; ++i) list[i].parentNode.removeChild(list[i]);
			for (var i = 0; i < list.length; ++i) {
				var s = document.createElement("SCRIPT");
				s.type = "text/javascript";
				s.textContent = list[i].textContent;
				s._is_menu = true;
				head.appendChild(s);
			}
			var items = document.getElementsByClassName('application_left_menu_item');
			for (var i = 0; i < items.length; ++i) {
				var item = items[i];
				if (item.hasAttribute("href") && !item.hasAttribute("target")) {
					item.setAttribute("target", "application_frame");
					item.onclick = function() {
						for (var j = 0; j < items.length; ++j) items[j].className = "application_left_menu_item";
						this.className = "application_left_menu_item selected";
					};
				} else if (!item.hasAttribute("href")) {
					// check if we have a sub-menu
					if (i < items.length-1 && items[i+1].hasAttribute("href")) {
						var this_size = getComputedStyleSizes(item);
						var next_size = getComputedStyleSizes(items[i+1]);
						if (parseInt(next_size.paddingLeft) > parseInt(this_size.paddingLeft)) {
							item._click_item = items[i+1];
							item.onclick = function() {
								window.frames[this._click_item.getAttribute("target")].frameElement.src = this._click_item.getAttribute("href");
							};
						}
					}
				}
			}
			layout.invalidate(menu_container);
			var frame = document.getElementById('application_frame');
			var win = getIFrameWindow(frame);
			if (win && win.location && !frame._loading_application_page)
				pageLoaded(win.location.href);
		}
	);
}

function loadPage(url) {
	var frame = document.getElementById('application_frame'); 
	frame._loading_application_page = true;
	var win = getIFrameWindow(frame);
	if (new URL(frame.src).toString() != new URL(url).toString() || !win || !win.location || new URL(win.location.href).toString() != new URL(url).toString())
		frame.src = url;
	else
		win.location.reload();
	layout.invalidate(frame);
}

function pageLoaded(url) {
	var u = new URL(url);
	var component = u.path;
	var i = component.indexOf('/',1);
	if (i > 0) {
		component = component.substring(i+1);
		i = component.indexOf('/');
		if (i > 0) component = component.substring(0,i);
		var menu = document.getElementById('application_menu');
		for (var i = 0; i < menu.childNodes.length; ++i) {
			var s = menu.childNodes[i];
			if (s.nodeType != 1) continue;
			if (!s.hasAttribute("components")) continue;
			var components = s.getAttribute("components").split(",");
			var found = false;
			for (var j = 0; j < components.length; ++j)
				if (component == components[j]) {
					if (s.className.indexOf("selected") < 0) {
						highlightSection(s);
						if (s.hasAttribute("menu"))
							loadMenu(s.getAttribute("menu"));
					}
					found = true;
				}
			if (found) break;
		}
	} else
		component = null;
	var items = document.getElementsByClassName('application_left_menu_item');
	var oks = [];
	for (var i = 0; i < items.length; ++i) {
		var item = items[i];
		if (item.hasAttribute("href")) {
			var iu = new URL(item.getAttribute("href"));
			if (u.path == iu.path) oks.push(item);
			item.className = "application_left_menu_item";
			var is_ok = u.path == iu.path;
			if (is_ok && iu.hash) is_ok = iu.hash == u.hash;
		}
	}
	if (oks.length > 1 && u.hash) {
		var oks2 = [];
		for (var i = 0; i < oks.length; ++i) {
			var item = oks[i];
			var iu = new URL(item.getAttribute("href"));
			if (iu.hash == u.hash) oks2.push(item);
		}
		if (oks2.length > 0) oks = oks2;
	}
	if (oks.length > 1 && u.params.length > 0) {
		var oks2 = [];
		for (var i = 0; i < oks.length; ++i) {
			var item = oks[i];
			var iu = new URL(item.getAttribute("href"));
			var ok = true;
			for (var name in u.params)
				if (typeof iu.params[name] == 'undefined' || iu.params["name"] != u.params["name"]) { ok = false; break; }
			if (ok) oks2.push(item);
		}
		if (oks2.length > 0) oks = oks2;
	}
	if (oks.length > 0)
		oks[0].className = "application_left_menu_item selected";
	document.getElementById('application_frame')._loading_application_page = false;
	layout.invalidate(document.body);
}

initPage();

<?php 
if (isset($_GET["section"])) {
	echo "sectionClicked(getSection(".json_encode($_GET["section"])."),".(isset($_GET["page"]) ? json_encode($_GET["page"]) : "null").");";
} else {
	echo "sectionClicked(getSection('home'));";
}
?>

/* Status in footer */
function initFooter() {
	var footer = document.getElementById('application_footer');
	// Google
	var span_google_status = document.createElement("SPAN");
	span_google_status.style.position = "absolute";
	span_google_status.style.bottom = "0px";
	span_google_status.style.right = "3px";
	footer.appendChild(span_google_status);
	require("google_status.js",function() {
		new google_status(span_google_status);
	});
	<?php if (PNApplication::$instance->current_domain == "Dev") { ?>
	var span_debug_status = document.createElement("SPAN");
	span_debug_status.style.position = "absolute";
	span_debug_status.style.bottom = "0px";
	span_debug_status.style.right = "23px";
	footer.appendChild(span_debug_status);
	require("debug_status.js",function() {
		new debug_status(span_debug_status);
	});
	<?php } ?>
	var span_background_loading = document.createElement("SPAN"); 
	span_background_loading.style.position = "absolute";
	span_background_loading.style.bottom = "0px";
	span_background_loading.style.left = "3px";
	footer.appendChild(span_background_loading);
	require("load_static.js",function(){
		var loading = new load_static_resources(span_background_loading, '#ffffff', '#ffffff', '#ffffff', '#404040');
		loading._slow_when_user_active = true;
		loading._slow_when_services_active = true;
	});
}
initFooter();
</script>