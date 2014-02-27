<?php 

$sections = array(
	array(
		"name" => "Dashboard",
		"icon" => theme::$icons_32["dashboard"],
		"menu_provider" => null,
		"default_page" => "/dynamic/application/page/home"
	),
	array(
		"name" => "Selection",
		"icon" => "/static/selection/selection_32.png",
		"menu_provider" => "selection",
		"default_page" => "/dynamic/selection/page/selection_main_page"
	),
	array(
		"name" => "Education",
		"icon" => "/static/students/student_32.png",
		"menu_provider" => null,
		"default_page" => "/dynamic/training_education/page/tree?section=education&page=list#all_students"
	),
	array(
		"name" => "Training",
		"icon" => "/static/curriculum/curriculum_32.png",
		"menu_provider" => null,
		//"default_page" => "/dynamic/training_education/page/batches_classes"
		"default_page" => "/dynamic/training_education/page/tree?section=training&page=list#all_students"
	),
	array(
		"name" => "Administration",
		"icon" => "/static/administration/admin_32.png",
		"menu_provider" => "administration",
		"default_page" => "/dynamic/administration/page/dashboard"
	),
);

theme::css($this, "application.css");
$this->require_javascript("vertical_layout.js");
$this->require_javascript("horizontal_layout.js");
$this->require_javascript("horizontal_menu.js");
$this->require_javascript("calendar.js");

if (PNApplication::$instance->current_domain == "Dev") {
	$this->add_javascript("/static/development/debug_status.js");
	$this->onload("new debug_status('debug_status');");
}
?>
<div id='pn_application_container' style='width:100%;height:100%'>
	<div id='pn_application_header'>
		<div id='pn_application_section_title'></div>
		<div id='pn_application_left_controls' style='visibility:hidden;padding-right:3px'></div>
		<div id='pn_application_menu' layout='fill'>
			<div class='button'><img src='<?php echo theme::$icons_16["more_menu"];?>'/> More</div>
		</div>
		<div id='pn_application_right_controls' style='visibility:hidden'></div>
		<div id='pn_application_right_menu' style='margin-right:3px'>
			<div id='user_menu' style='display:inline-block;' onclick='user_menu();'>
				<img src='/dynamic/people/service/picture?people=<?php echo PNApplication::$instance->user_people->user_people_id;?>' style='width:30px;height:30px'/>
			</div>
		</div>
	</div>
	<iframe id='pn_application_content' name='pn_application_content' layout='fill'<?php if (isset($_GET["page"])) echo "src='".$_GET["page"]."'";?>>
	</iframe>
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
</div>
<div id='user_menu_content' class='context_menu' style='font-size: 11pt; font-weight: bold;color:#000060;border:1px solid #808080;border-top:0px;background-color:#ffffff;'>
	<div class='context_menu_item' onclick="document.getElementById('pn_application_content').src='/dynamic/people/page/profile?people=<?php echo PNApplication::$instance->user_people->user_people_id;?>'">
		<img src='/static/people/profile_32.png' style='vertical-align:middle' title='Your Profile'/>
	</div>
	<div class='context_menu_item' onclick="document.getElementById('pn_application_content').src='/dynamic/calendar/page/calendars'">
		<img src='/static/calendar/calendar_32.png' style='vertical-align:middle' title='Your Calendars'/>
	</div>
	<div id='logout' class='context_menu_item' onclick="location.href='/dynamic/application/page/logout'">
		<img src='/static/application/logout.png' style='vertical-align:middle' title='Exit'/>
	</div>
</div>
<script type='text/javascript'>
window.top.default_country_code = <?php $d = PNApplication::$instance->get_domain_descriptor(); echo json_encode($d["country_code"]).";"; ?>

if (!window.top.calendar_manager) {
	window.top.calendar_manager = new CalendarManager();
	window.top.CalendarsProviders.get(function(provider) {
		provider.getCalendars(function(calendars) {
			for (var i = 0; i < calendars.length; ++i)
				window.top.calendar_manager.addCalendar(calendars[i]);
		});
	});
}

setBorderRadius(document.getElementById('user_menu_content'), 0, 0, 0, 0, 5, 5, 5, 5);
setBorderRadius(document.getElementById('logout'), 0, 0, 0, 0, 5, 5, 5, 5);
function user_menu() {
	require("context_menu.js",function() {
		var menu = new context_menu('user_menu_content');
		var user_picture = document.getElementById('user_menu');
		menu.showBelowElement(user_picture);
		if (typeof animation != 'undefined') {
			var e = document.getElementById('user_menu_content');
			e.style.zIndex = 1;
			var end = e.offsetTop;
			animation.create(e, 0, end, 200, function(value) {
				e.style.top = Math.floor(value)+'px';
			});
		}
	});
}

var sections = [<?php
$first = true;
foreach ($sections as $s) {
	if ($first) $first = false; else echo ",";
	echo "{";
	$f = true;
	foreach ($s as $name=>$value) {
		if ($f) $f = false; else echo ",";
		echo $name.":".json_encode($value);
	}
	echo "}";
}
?>];
var section_menu = null;
var selected_section_index = 0;
function initLayout() {
	new vertical_layout('pn_application_container');
	new horizontal_layout('pn_application_header');
	section_menu = new horizontal_menu('pn_application_menu');
}
initLayout();
var sections_bar = document.createElement("DIV");
sections_bar.style.borderRight = "1px solid #FFFFFF";
sections_bar.style.backgroundColor = "rgba(255,255,255,0.4)";
setBorderRadius(sections_bar, 0, 0, 0, 0, 0, 0, 10, 10);
sections_bar.padding = "3px 6px 2px 3px";
sections_bar.style.whiteSpace = "nowrap";
sections_bar.style.overflow = "hidden";
sections_bar.style.position = "fixed";
sections_bar.style.top = "0px";
sections_bar.style.left = "0px";
sections_bar.style.height = "35px";
sections_bar.style.cursor = 'pointer';
for (var i = 0; i < sections.length; ++i) {
	var item = document.createElement("DIV");
	item.style.display = "inline-block";
	item.style.padding = "0px 3px 0px 2px";
	var icon = document.createElement("IMG");
	icon.src = sections[i].icon;
	icon.style.verticalAlign = "middle";
	icon.style.marginRight = "4px";
	icon.onload = function() {
		if (sections_bar.childNodes.length <= selected_section_index) return;
		sections_bar.style.width = sections_bar.childNodes[selected_section_index].offsetWidth+'px';
		document.getElementById('pn_application_section_title').style.width = sections_bar.childNodes[selected_section_index].offsetWidth+'px'; 
	};
	var title = document.createElement("DIV");
	title.style.display = "inline-block";
	title.style.color = "#4040A0";
	title.style.fontSize = "12pt";
	title.style.fontWeight = "bold";
	title.appendChild(document.createTextNode(sections[i].name));
	item.appendChild(icon);
	item.appendChild(title);
	item.index = i;
	sections_bar.appendChild(item);
}
document.body.appendChild(sections_bar);

var bar_expanded = false;
function expandBar() {
	bar_expanded = true;
	var e = document.getElementById('show_the_menu_to_the_user');
	if (e && e.parentNode) e.parentNode.removeChild(e);
	require("animation.js",function() {
		animation.create(sections_bar, sections_bar.offsetLeft, 0, 500, function(value, element) {
			element.style.left = Math.floor(value)+'px';
		});
		var w = 0;
		for (var i = 0; i < sections_bar.childNodes.length; ++i) w += sections_bar.childNodes[i].offsetWidth;
		animation.create(sections_bar, sections_bar.offsetWidth, w, 500, function(value, element) {
			element.style.width = Math.floor(value)+'px';
		});
		for (var i = 0; i < sections_bar.childNodes.length; ++i) {
			sections_bar.childNodes[i].onclick = function() {
				selectSection(this.index);
			};
		}
		sections_bar.onclick = null;
		animation.fadeOut(document.getElementById('pn_application_left_controls'), 300);
		animation.fadeOut(document.getElementById('pn_application_menu'), 300);
		animation.fadeOut(document.getElementById('pn_application_right_controls'), 300);
	});
}
function selectSection(index, onlyCollapseBar) {
	selected_section_index = index;
	bar_expanded = false;
	if (!onlyCollapseBar) {
		populateMenu();
		document.getElementById('pn_application_content').src = sections[index].default_page;
	}
	if (typeof animation != 'undefined')
		animation.create(sections_bar, 0, -sections_bar.childNodes[index].offsetLeft, 500, function(value, element) {
			element.style.left = Math.floor(value)+'px';
		});
	else
		sections_bar.style.left = (-sections_bar.childNodes[index].offsetLeft)+'px';
	if (typeof animation != 'undefined')
		animation.create(sections_bar, sections_bar.offsetWidth, sections_bar.childNodes[index].offsetLeft+sections_bar.childNodes[index].offsetWidth, 500, function(value,element) {
			element.style.width = Math.floor(value)+'px';
		});
	else
		sections_bar.style.width = (sections_bar.childNodes[index].offsetLeft+sections_bar.childNodes[index].offsetWidth)+'px';
	for (var i = 0; i < sections_bar.childNodes.length; ++i)
		sections_bar.childNodes[i].onclick = expandBar;
	document.getElementById('pn_application_section_title').setAttribute("layout", sections_bar.childNodes[index].offsetWidth+10);
	layout.invalidate(document.getElementById('pn_application_header'));
	if (typeof animation != 'undefined') {
		var div = document.getElementById('pn_application_left_controls');
		if (div.childNodes.length > 0) animation.fadeIn(div, 800);
		animation.fadeIn(document.getElementById('pn_application_menu'), 800);
		var div = document.getElementById('pn_application_right_controls');
		if (div.childNodes.length > 0) animation.fadeIn(div, 800);
	}
}
sections_bar.onclick = expandBar;

// when clicking outside of expanded bar, collapse it
listenEvent(window, 'click', function(ev) {
	if (!bar_expanded) return;
	var e = getCompatibleMouseEvent(ev);
	if (e.x > sections_bar.offsetWidth) selectSection(selected_section_index, true);
});

function populateMenu() {
	resetMenu();
	resetLeftControls();
	resetRightControls();
	if (sections[selected_section_index].menu_provider == null) return;
	service.customOutput(sections[selected_section_index].menu_provider, "application_menu_builder", {},function(code){
		if (code == null) {
			// TODO
			return;
		}
		eval(code); // TODO catch exceptions
	});
}

<?php if (!isset($_GET["page"])) { ?>
selectSection(selected_section_index);
<?php } ?>

require("animation.js");
require("vertical_align.js",function() {
	new vertical_align('pn_application_left_controls', 'middle');
	new vertical_align('pn_application_right_controls', 'middle');
});
require("google_status.js",function() {
	new google_status('google_status');
});

function resetMenu() {
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
function addMenuItem(icon, text, url) {
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
	item.onclick = function(ev) {
		if (!this.target) return true;
		var frame = findFrame(this.target);
		if (!frame) {
			// missing, reload the default page first
			frame = document.getElementById('pn_application_content');
			var target = this.target;
			frame.onload = function() {
				frame.onload = null;
				var f = findFrame(target);
				if (!f) {
					// cannot find it..
					frame.src = url;
					return;
				}
				f.src = url;
			};
			frame.src = sections[selected_section_index].default_page;
			stopEventPropagation(ev);
			return false;
		}
		return true;
	};
	layout.invalidate(document.getElementById('pn_application_header'));
	return item;
}
function addMenuControl(control) {
	if (typeof control == 'string') { var e = document.createElement("DIV"); e.style.display = "inline-block"; e.innerHTML = control; control = e; }
	section_menu.addItem(control);
	layout.invalidate(document.getElementById('pn_application_header'));
}
function addRightControl(control) {
	if (typeof control == 'string') { var e = document.createElement("DIV"); e.style.display = "inline-block"; e.innerHTML = control; control = e; }
	var div = document.getElementById('pn_application_right_controls');
	div.appendChild(control);
	div.style.visibility = 'visible';
	setOpacity(div, 100);
	layout.invalidate(document.getElementById('pn_application_header'));
}
function addLeftControl(control) {
	if (typeof control == 'string') { var e = document.createElement("DIV"); e.style.display = "inline-block"; e.innerHTML = control; control = e; }
	var div = document.getElementById('pn_application_left_controls');
	div.appendChild(control);
	div.style.visibility = 'visible';
	setOpacity(div, 100);
	layout.invalidate(document.getElementById('pn_application_header'));
}

// at the beginning, show all sections to user
setTimeout(expandBar, 2000);

// background loading of the application
require("load_static.js",function(){
	new load_static_resources('background_loading_application');
});

</script>