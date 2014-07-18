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
    width: 129px;
    height: 125px;
    padding: 3px 1px 3px 1px;
    margin: 3px;
    border: 1px solid rgba(0,0,0,0);
    border-radius: 5px; 
    cursor: pointer;
    vertical-align: top;
    text-decoration: none;
}
.section_box:hover {
	border: 1px solid #808080;
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
</style>
<div style="background-color: white">
	<div class="page_title">
		<img src='/static/application/logo.png' height="50px" style="vertical-align:bottom"/>
		Welcome in PN Students Management Software !
	</div>
	<div class="page_section_title">
		Navigate into the different sections of the application
	</div>
	<div id="section_menu">
		<a class="section_box" href='/dynamic/selection/page/selection_main_page'>
			<div><img src='/static/selection/selection_32.png'/></div>
			<div>Selection</div>
			<div>Access to the different steps of the selection process</div>
		</a>
		<a class="section_box" href='/dynamic/curriculum/page/tree_frame#/dynamic/students/page/list'>
			<div><img src='/static/curriculum/curriculum_32.png'/></div>
			<div>Training</div>
			<div>Consult the curriculum, students list by batch and class, grades...</div>
		</a>
		<a class="section_box">
			<div><img src='/static/education/education_32.png'/></div>
			<div>Education</div>
			<div>Students information and life in PN: discipline, health, finance, housing...</div>
		</a>
		<a class="section_box">
			<div><img src='/static/internship/internship_32.png'/></div>
			<div>Internship</div>
			<div>Companies information, internships follow-up...</div>
		</a>
		<a class="section_box">
			<div><img src='/static/students/student_32.png'/></div>
			<div>Alumni</div>
			<div>Alumni current situation and contacts</div>
		</a>
		<a class="section_box" href='/dynamic/administration/page/dashboard'>
			<div><img src='/static/administration/admin_32.png'/></div>
			<div>Administration</div>
			<div>Manage users and access rights, staffs, geographic data...</div>
		</a>
	</div>
<?php
$google_id = PNApplication::$instance->google->getConnectedAccount();
if ($google_id == null) {
?>
<div style='flex:none' class='page_section_title'>
	<img src='/static/google/google_32.png' style='vertical-align:bottom'/> <span onclick="window.top.google.connectAccount();" style='cursor:pointer' onmouseover="this.style.textDecoration='underline';" onmouseout="this.style.textDecoration='';">Connect your PN Google account to this application</span>
</div>
<?php 
}
?>
	<div class="page_section_title" style='margin-bottom:0px'>
		What's happening ?
	</div>
	<div style='background-color:#e8e8e8;padding-top:10px;display:flex;flex-direction:row;'>
		<div id="updates" style="display:inline-block;flex:1 1 auto;margin-left:10px"
			icon="/static/news/news.png"
			title="Latest Updates"
		>
			<div>
				<div class='page_section_title3'>General updates</div>
				<div id='general_news_container'>
					<img src='/static/news/loading.gif' id='general_news_loading'/>
				</div>
				<div class='page_section_title3'>Other updates</div>
				<div id='other_news_container'>
					<img src='/static/news/loading.gif' id='other_news_loading'/>
				</div>
			</div>
		</div>
		<div id="calendar_events" style="display:inline-block;flex:1 1 auto;margin-left:10px;vertical-align:top"
			icon="/static/calendar/calendar_16.png"
			title="Upcoming Events"
		>
			<div id='calendars_container' style='width:100%'><img src='<?php echo theme::$icons_16["loading"];?>'/></div>
			<a href='/dynamic/calendar/page/calendars'>Show calendars</a>
		</div>
	</div>
</div>
<script type='text/javascript'>
var calendars_section = sectionFromHTML('calendar_events');
var updates_section = sectionFromHTML('updates');

require("calendar_view.js");
require("calendar_view_week.js");
function init_calendars() {
	var icon_loading = document.createElement("IMG");
	icon_loading.style.verticalAlign = "middle";
	icon_loading.src = theme.icons_16.loading_white;
	icon_loading.style.visibility = 'hidden';
	icon_loading.counter = 0;
	calendars_section.addToolRight(icon_loading);
	window.top.calendar_manager.on_refresh.add_listener(function() {
		icon_loading.counter++;
		icon_loading.style.visibility = 'visible';
	});
	window.top.calendar_manager.on_refresh_done.add_listener(function() {
		if (--icon_loading.counter == 0)
			icon_loading.style.visibility = 'hidden';
	});
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
					new CalendarControl(content, t.provider.calendars[i]);
				var popup = new popup_window(t.provider.provider.getProviderName(), t.provider.provider.getProviderIcon(), content);
				popup.show();
			});
		};
		providers.push(provider);
		layout.invalidate(calendars_section.element);
	};
	for (var i = 0; i < window.top.calendar_manager.calendars.length; ++i)
		new_calendar(window.top.calendar_manager.calendars[i]);
	window.top.calendar_manager.on_calendar_added.add_listener(new_calendar);
	window.pnapplication.onclose.add_listener(function() {
		window.top.calendar_manager.on_calendar_added.remove_listener(new_calendar);
	});
}
init_calendars();

require("news.js",function() {
	new news('general_news_container', [{name:"application"}], null, function(n) {
		var loading = document.getElementById('general_news_loading');
		loading.parentNode.removeChild(loading);
	});
	new news('other_news_container', [], [{name:"application"}], function(n) {
		var loading = document.getElementById('other_news_loading');
		loading.parentNode.removeChild(loading);
	});
});

</script>
<?php 		
	}
	
}
?>