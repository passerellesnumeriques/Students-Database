<?php 
class page_home extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/section/section.js");
?>
<div style='position:absolute;width:50%;height:100%;overflow-y:auto;top:0px;left:0px;'>
	<div id='general_news' icon='/static/news/news.png' title='General Updates' collapsable='true' style="margin:5px 5px 0px 5px;">
		<div style='padding:2px 3px 0px 3px;' id='general_news_container'>
			<img id='general_news_loading' src='/static/news/loading.gif'/>
		</div>
	</div>
	<div id='other_news' icon='/static/news/news.png' title='Other Updates' collapsable='true' style="margin:5px 5px 0px 5px;">
		<div style='padding:2px 3px 0px 3px;' id='other_news_container'>
			<img id='other_news_loading' src='/static/news/loading.gif'/>
		</div>
	</div>
</div>
<div style='position:absolute;width:50%;top:0px;left:50%'>
	<div id='calendars' icon='/static/calendar/event.png' title='Your Calendars' collapsable='true' style="margin:5px 5px 0px 5px;">
		<div id='calendars_container' style="height:300px;"><img src='<?php echo theme::$icons_16["loading"];?>'/></div>
	</div>
</div>
<script type='text/javascript'>
var calendars_section = section_from_html('calendars');
require("calendar.js");
require("calendar_view.js");
require("calendar_view_week.js");
require("calendar.js",function() {
	var manager = new CalendarManager();
	var icon_loading = document.createElement("IMG");
	icon_loading.style.verticalAlign = "middle";
	icon_loading.src = theme.icons_16.loading;
	icon_loading.style.visibility = 'hidden';
	icon_loading.counter = 0;
	calendars_section.addToolRight(icon_loading);
	calendars_section.addToolRight("<a class='button_verysoft' href='/dynamic/calendar/page/calendars'><img src='"+theme.icons_16.window_maximize+"'/></a>");
	manager.on_refresh.add_listener(function() {
		icon_loading.counter++;
		icon_loading.style.visibility = 'visible';
	});
	manager.on_refresh_done.add_listener(function() {
		if (--icon_loading.counter == 0)
			icon_loading.style.visibility = 'hidden';
	});
	require("calendar_view.js",function() {
		new CalendarView(manager, "week", 60, 'calendars_container', function() {
		});
	});
	window.top.CalendarsProviders.get(function(provider) {
		provider.getCalendars(function(calendars) {
			var div = document.createElement("DIV");
			div.innerHTML = "<img src='"+provider.getProviderIcon()+"' width=16px height=16px style='vertical-align:bottom'/> "+provider.getProviderName()+" ("+calendars.length+")";
			div.className = "button";
			div.style.margin = "2px";
			div.style.paddingRight = "5px";
			calendars_section.addTool(div);
			fireLayoutEventFor(calendars_section.element);
			div.onclick = function() {
				require("popup_window.js",function() {
					var content = document.createElement("DIV");
					content.style.padding = "5px";
					for (var i = 0; i < calendars.length; ++i)
						new CalendarControl(content, calendars[i]);
					var popup = new popup_window(provider.getProviderName(), provider.getProviderIcon(), content);
					popup.show();
				});
			};
			for (var i = 0; i < calendars.length; ++i)
				manager.addCalendar(calendars[i]);
		});
	});
});

var general_news_section = section_from_html('general_news');
var general_news_loading = document.createElement("IMG");
general_news_loading.src = "/static/news/loading.gif";
general_news_loading.style.position = "absolute";
general_news_loading.style.visibility = "hidden";
general_news_loading.style.verticalAlign = "bottom";
general_news_section.addTool(general_news_loading);

var other_news_section = section_from_html('other_news');
var other_news_loading = document.createElement("IMG");
other_news_loading.src = "/static/news/loading.gif";
other_news_loading.style.position = "absolute";
other_news_loading.style.visibility = "hidden";
other_news_loading.style.verticalAlign = "bottom";
other_news_section.addTool(other_news_loading);

require("news.js",function() {
	new news('general_news_container', [{name:"application"}], null, function(n) {
		var loading = document.getElementById('general_news_loading');
		loading.parentNode.removeChild(loading);
	}, function(starts) {
		if (starts) {
			general_news_loading.style.position = "static";
			general_news_loading.style.visibility = "visible";
			fireLayoutEventFor(general_news_section.element);
		} else {
			general_news_loading.style.position = "absolute";
			general_news_loading.style.visibility = "hidden";
			fireLayoutEventFor(general_news_section.element);
		}
	});
	new news('other_news_container', [], [{name:"application"}], function(n) {
		var loading = document.getElementById('other_news_loading');
		loading.parentNode.removeChild(loading);
	}, function(starts) {
		if (starts) {
			other_news_loading.style.position = "static";
			other_news_loading.style.visibility = "visible";
			fireLayoutEventFor(other_news_section.element);
		} else {
			other_news_loading.style.position = "absolute";
			other_news_loading.style.visibility = "hidden";
			fireLayoutEventFor(other_news_section.element);
		}
	});
});
</script>
<?php
	}
	
}
?>