<?php
$this->require_javascript("vertical_layout.js");
$this->require_javascript("horizontal_layout.js");
$this->onload("new vertical_layout('application_container');");
$this->onload("new horizontal_layout('application_horizontal');");
$this->onload("new vertical_layout('application_left_side',true);");
$this->onload("new vertical_layout('application_right_side');");
$this->onload("new horizontal_layout('application_header');");
$this->require_javascript("fill_height_layout.js");
//$this->onload("new fill_height_layout('application_table');");
//$this->onload("new fill_height_layout('application_frame_container');");
?>
<style type="text/css">
#application_container {
	width: 100%;
	height: 100%;
	background-color:#009DE1;
}

/* Left Side */
#application_left_side {
	height: 100%;
}
#application_title {
	white-space: nowrap;
	height: 49px;
	border-bottom: 1px solid #22bbea;
	border-bottom-right-radius: 10px;
	padding-right: 5px;
}
#application_title>img {
	height: 45px;
	margin:1px 1px 1px 2px;
	vertical-align:top;
}
#application_title>div {
	display:inline-block;
	font-size:12pt;
	font-family:Verdana;
	color:black;
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
}
#application_footer {
	height: 15px;
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
.application_left_menu_item_arrow {
    display: inline-block;
    border-top: 10px solid transparent;
    border-left: 5px solid #ff9933;
    border-bottom: 10px solid transparent;
    border-left-color: #ff9933;
}

button.flat {
	cursor: pointer;
	border: 1px solid rgba(0,0,0,0);
	padding: 2px;
	border-radius: 3px;
	box-shadow: 1px 1px 1px rgba(170,170,170,0);
	display: inline-block;
	margin: 1px 2px 1px 2px;
	background: transparent;
    background-color: transparent;
}
button.flat:hover {
	border: 1px solid rgba(128,128,128,0.4);
	box-shadow: 1px 1px 1px rgba(170,170,170,0.6);
	background-color: rgba(255,255,255,0.2);
}
button.flat:hover:disabled {
    border: 1px solid rgba(128,128,128,0.2);
    box-shadow: 0px 0px 0px rgba(170,170,170,0);
    background-color: rgba(255,255,255,0.1);
}
button.flat:active {
	box-shadow: 0px 0px 0px;
	position: relative;
	top: 1px;
	left: 1px;
}
button.flat:disabled:active {
    top: 0px;
    left: 0px;
}
button.flat a {
	text-decoration: none;
	color: black;
}
button>img {
    vertical-align: bottom;
}


</style>
<div id="application_container">
	<div id="application_horizontal" layout="fill">
		<div id='application_left_side'>
			<div id='application_title'>
				<img src='/static/application/logo.png'/>
				<div>Students<br/>Management</div>
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
						<img src='/static/application/menu/selection.png'/>
						Selection
					</div>
					<div class="application_menu_item" 
						section="training"
						components="training,transcripts,curriculum"
					>
						<img src='/static/application/menu/training.png'/>
						Training
					</div>
					<div class="application_menu_item" 
						section="education"
						components="education,health,discipline"
					>
						<img src='/static/application/menu/education.png'/>
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
						components="administration"
					>
						<img src='/static/application/menu/administration.png'/>
						Admin
					</div>
				</div>
				<div id="application_right_menu">
					<input type='text' id='application_search'/>
					<a href='/dynamic/people/page/profile?people=<?php echo PNApplication::$instance->user_people->user_people_id;?>' target='application_frame'><img id='user_picture'/></a>
					<button class="flat"><img id='notifications' src='/static/application/menu/notifications.png'/></button>
					<button class="flat"><a href='/dynamic/calendar/page/calendars' target='application_frame'><img id='calendars' src='/static/calendar/calendar_white.png'/></a></button>
					<button class="flat"><a href='/dynamic/application/page/logout' target='pn_application_frame'><img id='logout' src='/static/application/logout_white.png'/></a></button>
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
		// TODO remove this
		var win = getIFrameWindow(this);
		var head = win.document.getElementsByTagName("HEAD")[0];
		for (var i = 0; i < head.childNodes.length; ++i) {
			var e = head.childNodes[i];
			if (e.nodeName != "LINK") continue;
			if (!e.href || e.href.length == 0) continue;
			var u = new URL(e.href);
			if (u.path == "/static/theme/default/style/section.css" ||
				u.path == "/static/theme/default/style/global.css"
				) {
				head.removeChild(e);
				i--;
			}
		}
		win.add_stylesheet("/static/theme/default/style/section.css");
		win.add_stylesheet("/static/theme/new_design/style/section.css");
		win.add_stylesheet("/static/theme/new_design/style/global.css");
	});
	var head = document.getElementsByTagName("HEAD")[0];
	for (var i = 0; i < head.childNodes.length; ++i) {
		var e = head.childNodes[i];
		if (e.nodeName != "LINK") continue;
		if (!e.href || e.href.length == 0) continue;
		var u = new URL(e.href);
		if (u.path == "/static/theme/default/style/global.css") {
			head.removeChild(e);
			i--;
		}
	}
	add_stylesheet("/static/theme/new_design/style/global.css");
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

function sectionClicked(section) {
	highlightSection(section);
	if (section.hasAttribute("menu"))
		loadMenu(section.getAttribute("menu"));
	if (section.hasAttribute("page"))
		loadPage(section.getAttribute("page"));
}

function loadMenu(url) {
	var menu_container = document.getElementById('application_left_menu');
	menu_container.innerHTML = "<img src='"+theme.icons_16.loading+"'/>";
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
						createLeftMenuArrow(this);
					};
				}
			}
			layout.invalidate(menu_container);
			var frame = document.getElementById('application_frame');
			var win = getIFrameWindow(frame);
			if (win && win.location)
				pageLoaded(win.location.href);
		}
	);
}

function loadPage(url) {
	document.getElementById('application_frame').src = url;
}

function pageLoaded(url) {
	removeLeftMenuArrow();
	var u = new URL(url);
	var items = document.getElementsByClassName('application_left_menu_item');
	for (var i = 0; i < items.length; ++i) {
		var item = items[i];
		if (item.hasAttribute("href")) {
			var iu = new URL(item.getAttribute("href"));
			item.className = "application_left_menu_item"+(u.path == iu.path ? " selected" : "");
			if (u.path == iu.path)
				createLeftMenuArrow(item);
		}
	}
}

var left_menu_arrow = null;
function removeLeftMenuArrow() {
	if (!left_menu_arrow) return;
	document.body.removeChild(left_menu_arrow);
	left_menu_arrow = null;
}
function createLeftMenuArrow(item) {
	removeLeftMenuArrow();
	left_menu_arrow = document.createElement("DIV");
	left_menu_arrow.className = "application_left_menu_item_arrow";
	left_menu_arrow.style.position = "absolute";
	left_menu_arrow.style.top = absoluteTop(item)+"px";
	left_menu_arrow.style.left = (absoluteLeft(item)+item.offsetWidth)+"px";
	document.body.appendChild(left_menu_arrow);
}

initPage();

<?php 
if (isset($_GET["page"])) {
	// TODO
} else if (isset($_GET["section"])) {
	echo "sectionClicked(getSection(".json_encode($_GET["section"])."));";
} else {
	echo "sectionClicked(getSection('home'));";
}
?>
</script>