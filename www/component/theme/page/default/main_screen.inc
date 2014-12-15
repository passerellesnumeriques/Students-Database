<?php
require_once("component/application/ApplicationSectionPlugin.inc");
$this->requireJavascript("vertical_scrolling.js");
$this->requireJavascript("horizontal_scrolling.js");
global $pn_app_version;
?>
<style type="text/css">
#application_container {
	background-color:#009DE1;
	z-index: 3;
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
#application_title_label {
	position: absolute;
	right: 0px;
	top: 10px;
	transform: rotate(-30deg);
	color: #CC6600;
	text-shadow: #a0e0c0 0.1em 0.1em 0.1em;
	font-weight: bold;
}
#application_left_menu {
	padding-top: 10px;
	white-space: nowrap;
	color: white;
	font-weight: bold;
	font-family: Verdana;
}

#application_header {
	height:27px;
}
#application_frame {
	border-radius: 8px;
	z-index: 1;
	border: 1px solid #ffffff;
	width: 100%;
}
#application_footer {
	text-align: center;
	font-size: 8pt;
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
.application_menu_item>a {
	text-decoration: none;
	color: white;
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
    border-bottom: 7px solid #ffffff;
    border-right: 7px solid transparent;
    border-bottom-color: #ffffff;
    margin-left: -3px;
    left: 50%;
    bottom: 0px;
    content: '';
}
#application_right_menu #user_picture {
	width: 25px;
	height: 25px;
    border: 1px solid rgba(0,0,0,0);
    cursor: pointer;
    border-radius: 5px;
    vertical-align: top;
    display: inline-block;
}
#application_right_menu #user_picture:hover {
    background-color: rgba(255,255,255,0.4);
    border: 1px solid rgba(255,255,255,0.6);
}
#application_right_menu #user_picture img {
	border-radius: 5px;
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
<div id="application_container" style="width:100%;height:100%;display:flex;flex-direction:column">
	<div style="flex:1 1 100%;display:flex;flex-direction:row;">
		<div style="flex:none;display:flex;flex-direction:column;">
			<div id='application_title' style="flex:none">
				<img src='/static/application/logo.png'/>
				<div id="application_title_text">Students<br/>Management</div>
<?php
#DEV
echo "<div id='application_title_label'>Dev</div>";
#END
#CHANNEL_BETA
#echo "<div id='application_title_label'>Beta</div>";
#END 
#SELECTION_TRAVEL
#echo "<div id='application_title_label'>Travel</div>";
#END
?>
			</div>
			<div id="application_left_menu_scroll" style="flex:1 1 100%;overflow:hidden;padding-right:5px;margin-right:-5px;z-index:2;">
			</div>
		</div>
		<div style="flex:1 1 100%;display:flex;flex-direction:column;">
			<div id="application_header" style="flex:none;display:flex;flex-direction:row;">
				<div id="application_menu_scroll" style="flex:1 1 100%;">
					<?php
					$sections = array();
					foreach (PNApplication::$instance->components as $cname=>$comp)
						foreach ($comp->getPluginImplementations() as $pi)
							if ($pi instanceof ApplicationSectionPlugin)
								if ($pi->canAccess())
									array_push($sections, $pi);
					usort($sections, function($s1, $s2) {
						if ($s1->getPriority() <= $s2->getPriority()) return -1;
						return 1;
					});
					foreach ($sections as $section) {
						echo "<div class='application_menu_item'";
						echo " section='".$section->getId()."'";
						echo " components='".implode(",",$section->getComponentsForURL())."'";
						echo " menu=\"".$section->getMenuURL()."\"";
						echo " page=\"".$section->getDefaultPageURL()."\"";
						echo ">";
						echo "<a href='/?section=".$section->getId()."' target='_blank' onclick='return false;'>";
						echo "<img src=\"".$section->getIcon16White()."\"/>";
						echo toHTML($section->getName());
						echo "</a>";
						echo "</div>";
					}
					?>
				</div>
				<div id="application_right_menu" style="flex:none;">
					<input type='text' id='application_search' onchange="postFrame('/dynamic/search/page/search',{q:this.value},'application_frame');this.value='';"/>
					<a href='/dynamic/people/page/profile?people=<?php echo PNApplication::$instance->user_management->people_id;?>' target='application_frame'><div id='user_picture'></div></a>
					<!-- <button class="flat" id='notifications'><img src='/static/application/menu/notifications.png'/></button>  -->
					<a href='/dynamic/calendar/page/calendars' target='application_frame'><button class="flat" id='calendars'><img src='/static/calendar/calendar_white.png'/></button></a>
					<?php
#SELECTION_TRAVEL
#echo "<!-- ";
#END 
					?>
					<button class="flat" id='domain_connection' onclick='domainConnectionMenu(this);'><img src='/static/data_model/database_white.png'/></button>
					<?php
#SELECTION_TRAVEL
#echo "--> ";
#END 
					?>
					<button class="flat" id='activate_help' onclick='activateHelp(this);'><img src='<?php echo theme::$icons_16["help_white"];?>'/></button>
					<?php
#SELECTION_TRAVEL
#echo "<!-- ";
#END 
					?>
					<a href='/dynamic/application/page/logout' target='pn_application_frame'><button class="flat" id='logout'><img src='/static/application/logout_white.png'/></button></a>
					<?php
#SELECTION_TRAVEL
#echo "--> ";
#END 
					?>
					</div>
			</div>
			<iframe id="application_frame" name="application_frame" style="flex:1 1 100%;"></iframe>
		</div>
	</div>
	<div id="application_footer" style="flex:none;height:16px;">
			Students Management Software (<?php echo $pn_app_version; ?>)
			 - © 2013<?php $now = date("Y");if ($now <> "2013") echo "-".$now;?>
			 - <a href='http://www.passerellesnumeriques.org' target='_blank' style='color:white;text-decoration:none' onmouseover="this.style.textDecoration='underline';" onmouseout="this.style.textDecoration='none';">Passerelles numériques</a>
			 - <a href='/dynamic/application/page/send_feedback' target='application_frame' style='color:white' title='Send a feedback (issue, suggestion, idea...) about this application'>Write a feedback</a>
	</div>
</div>
<script type='text/javascript' defer>
user_picture = document.getElementById('user_picture');
require("profile_picture.js",function() {
	new profile_picture(user_picture, 25, 25, "center", "middle").loadPeopleObject(window.top.my_people);
});
tooltip(user_picture, "Your profile");

//tooltip(document.getElementById('notifications'), "See notifications");
tooltip(document.getElementById('calendars'), "Your calendars");
tooltip(document.getElementById('activate_help'), "Display again help messages on the current page");
<?php
#SELECTION_TRAVEL
#if (false) {
#END
?>
tooltip(document.getElementById('domain_connection'), "Connect to other centers' database");
tooltip(document.getElementById('logout'), "Logout and exit application");
<?php
#SELECTION_TRAVEL
#}
#END
?>

function initPage() {
	var hs = new horizontal_scrolling('application_menu_scroll','#009DE1','white');
	hs.element.id = 'application_menu';
	var vs = new vertical_scrolling('application_left_menu_scroll','#009DE1','white');
	vs.element.id = 'application_left_menu';
	var menu = document.getElementById('application_menu');
	for (var i = 0; i < menu.childNodes.length; ++i) {
		var s = menu.childNodes[i];
		if (s.nodeType != 1) continue;
		s.onclick = function() { sectionClicked(this); };
	}
	var frame = document.getElementById('application_frame');
	listenEvent(frame,'load',function() {
		var w=getIFrameWindow(this);
		pageLoaded(w.location.href);
		listenEvent(w,'hashchange',function() {
			pageLoaded(w.location.href);
		});
	});
}

function domainConnectionMenu(button) {
	require("context_menu.js",function() {
		var menu = new context_menu();
		var div;
		<?php
		foreach (PNApplication::$instance->getDomains() as $domain=>$descr) {
			echo "div = document.createElement('DIV');";
			if ($domain == PNApplication::$instance->current_domain)
				echo "div.style.fontWeight = 'bold';";
			else {
				echo "div.className = 'context_menu_item';";
				echo "div.onclick = function() { connectToDomain('$domain'); };";
			}
			echo "div.style.whiteSpace = 'nowrap';";
			echo "div.innerHTML = \"<img src='/static/geography/flags/".$descr["geography"]["country_code"].".png' style='vertical-align:bottom'/> $domain\";";
			echo "menu.addItem(div, ".($domain == PNApplication::$instance->current_domain ? "true" : "false").");";
		}
		?>
		menu.showBelowElement(button);
	});
}
function connectToDomain(domain) {
	var locker = lock_screen(null, "Connection to domain "+domain+"...");
	service.json("application","set_domain",{domain:domain},function(res) {
		if (!res) { unlock_screen(locker); return; };
		window.top.location.reload();
	});
}

function activateHelp(button) {
	window.top.require("help_system.js", function() {
		window.top.help_system.activateHelp();
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
	menu_container.removeAllChildren();
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
			menu_container.removeAllChildren();
			menu_container.innerHTML = "<img src='"+theme.icons_16.error+"'/> "+error;
		},
		function(xhr){
			menu_container.removeAllChildren();
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
						var this_size = getStyleSizes(item, []);
						var next_size = getStyleSizes(items[i+1], []);
						if (next_size.paddingLeft > this_size.paddingLeft) {
							item._click_item = items[i+1];
							item.onclick = function() {
								window.frames[this._click_item.getAttribute("target")].frameElement.src = this._click_item.getAttribute("href");
							};
						}
					}
				}
			}
			layout.changed(menu_container);
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
	layout.changed(frame);
}

function pageLoaded(url) {
	var u = new URL(url);
	// determine section from url
	var menu = document.getElementById('application_menu');
	var section = null;
	if (u.params && u.params.section) {
		for (var i = 0; i < menu.childNodes.length; ++i) {
			var s = menu.childNodes[i];
			if (s.nodeType != 1) continue;
			if (!s.hasAttribute("section")) continue;
			if (s.getAttribute("section").toLowerCase() == u.params.section) {
				section = s;
				break;
			}
		}
	}
	if (section == null) {
		// determine based on component
		var component;
		if (u.hash && u.hash.startsWith("/dynamic/"))
			component = u.hash;
		else
			component = u.path;
		var i = component.indexOf('/',1);
		if (i > 0) {
			component = component.substring(i+1);
			i = component.indexOf('/');
			if (i > 0) component = component.substring(0,i);
			for (var i = 0; i < menu.childNodes.length; ++i) {
				var s = menu.childNodes[i];
				if (s.nodeType != 1) continue;
				if (!s.hasAttribute("components")) continue;
				var components = s.getAttribute("components").split(",");
				for (var j = 0; j < components.length && section == null; ++j)
					if (component == components[j]) section = s;
			}
		}
	}
	if (section != null) {
		if (!hasClassName(section, "selected")) {
			highlightSection(section);
			if (section.hasAttribute("menu"))
				loadMenu(section.getAttribute("menu"));
		}
	}
	var items = document.getElementsByClassName('application_left_menu_item');
	var oks = [];
	for (var i = 0; i < items.length; ++i) {
		var item = items[i];
		if (item.hasAttribute("href")) {
			var iu = new URL(item.getAttribute("href"));
			if (u.path == iu.path) oks.push(item);
			else if (item.hasAttribute("alternate_hrefs")) {
				var hrefs = item.getAttribute("alternate_hrefs").split(";");
				for (var j = 0; j < hrefs.length; ++j)
					if (new URL(hrefs[j]).path == u.path) { oks.push(item); break; }
			}
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
				if (typeof iu.params[name] == 'undefined' || iu.params[name] != u.params[name]) { ok = false; break; }
			if (ok) oks2.push(item);
		}
		if (oks2.length > 0) oks = oks2;
	}
	if (oks.length > 0) {
		var oks2 = [];
		for (var i = 0; i < oks.length; ++i) {
			var item = oks[i];
			var iu = new URL(item.getAttribute("href"));
			var ok = true;
			for (var name in iu.params)
				if (typeof u.params[name] == 'undefined' || u.params[name] != iu.params[name]) { ok = false; break; }
			if (ok) oks2.push(item);
		}
		if (oks2.length > 0) oks = oks2;
	}
	if (oks.length > 0)
		oks[0].className = "application_left_menu_item selected";
	document.getElementById('application_frame')._loading_application_page = false;
	layout.changed(document.body);
}

initPage();

<?php 
#SELECTION_TRAVEL
#echo "sectionClicked(getSection('selection'));";
#if (false) {
#END
if (isset($_GET["section"])) {
	echo "sectionClicked(getSection(".json_encode($_GET["section"])."),".(isset($_GET["page"]) ? json_encode($_GET["page"]) : "null").");";
} else {
	echo "sectionClicked(getSection('home'));";
}
#SELECTION_TRAVEL
#}
#END
?>

/* Status in footer */
function initFooter() {
	var footer = document.getElementById('application_footer');
	// App
	/*
	var span_app_status = document.createElement("DIV");
	span_app_status.style.position = "absolute";
	span_app_status.style.bottom = "0px";
	span_app_status.style.right = "3px";
	span_app_status.title = "Connection to PN Server";
	footer.appendChild(span_app_status);
	require("app_status.js",function() {
		new app_status(span_app_status);
	});
	*/
	// Google
	<?php if (PNApplication::$instance->google->isInstalled()) { ?>
	var span_google_status = document.createElement("SPAN");
	span_google_status.style.position = "absolute";
	span_google_status.style.bottom = "0px";
	span_google_status.style.right = "3px";
	span_google_status.title = "Connection to Google";
	footer.appendChild(span_google_status);
	require("google_status.js",function() {
		new google_status(span_google_status);
	});
	<?php
	}
#DEV
	?>
	var span_debug_status = document.createElement("SPAN");
	span_debug_status.style.position = "absolute";
	span_debug_status.style.bottom = "0px";
	span_debug_status.style.right = "23px";
	footer.appendChild(span_debug_status);
	require("debug_status.js",function() {
		new debug_status(span_debug_status);
	});
<?php
#END 
	global $need_app_loading;
	if ($need_app_loading) { 
	?>
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
	<?php } ?>
}
initFooter();

setTimeout(function() { window.top.require("help_system.js"); }, 1000);
</script>