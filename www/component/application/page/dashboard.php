<?php 
class page_dashboard extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->require_javascript("section.js");
		theme::css($this, "application.css");
?>
<div style='position:absolute;top:0px;left:0px;width:50%;'>
	<div id='general_news' icon='/static/news/news.png' title='General Updates' collapsable='true' style="margin:10px 10px 20px 10px;">
		<div style='padding:2px 3px 0px 3px;' id='general_news_container'>
			<img id='general_news_loading' src='/static/news/loading.gif'/>
		</div>
	</div>
	<div id='other_news' icon='/static/news/news.png' title='Other Updates' collapsable='true' style="margin:10px 10px 20px 10px;">
		<div style='padding:2px 3px 0px 3px;' id='other_news_container'>
			<img id='other_news_loading' src='/static/news/loading.gif'/>
		</div>
	</div>
</div>

<div style='position:absolute;top:0px;left:50%;width:50%;'>
	<div id='calendars' icon='/static/calendar/event.png' title='Your Calendars' collapsable='true' style="margin:10px 10px 20px 10px;">
		<div id='calendars_container' style='width:100%'><img src='<?php echo theme::$icons_16["loading"];?>'/></div>
	</div>
</div>
<script type='text/javascript'>
var calendars_section = section_from_html('calendars');
require("calendar_view.js");
require("calendar_view_week.js");
function init_calendars() {
	var icon_loading = document.createElement("IMG");
	icon_loading.style.verticalAlign = "middle";
	icon_loading.src = theme.icons_16.loading;
	icon_loading.style.visibility = 'hidden';
	icon_loading.counter = 0;
	calendars_section.addToolRight(icon_loading);
	var maximize = document.createElement("A");
	maximize.className = "button_verysoft";
	maximize.innerHTML = "<img src='"+theme.icons_16.window_maximize+"'/>";
	maximize.href = '#';
	maximize.onclick = function() {
		window.top.require("popup_window.js",function() {
			var p = new window.top.popup_window("Your Calendars", "/static/calendar/calendar_16.png", "");
			p.setContentFrame('/dynamic/calendar/page/calendars');
			p.showPercent(95,95);
		});
		return false;
	};
	calendars_section.addToolRight(maximize);
	window.top.calendar_manager.on_refresh.add_listener(function() {
		icon_loading.counter++;
		icon_loading.style.visibility = 'visible';
	});
	window.top.calendar_manager.on_refresh_done.add_listener(function() {
		if (--icon_loading.counter == 0)
			icon_loading.style.visibility = 'hidden';
	});
	require("calendar_view.js",function() {
		new CalendarView(window.top.calendar_manager, "week", 60, 'calendars_container', function() {
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
		var div = document.createElement("DIV");
		div.innerHTML = "<img src='"+cal.provider.getProviderIcon()+"' width=16px height=16px style='vertical-align:bottom'/> (";
		provider.span_nb = document.createElement("SPAN");
		provider.span_nb.innerHTML = "1";
		div.appendChild(provider.span_nb);
		div.appendChild(document.createTextNode(")"));
		div.title = cal.provider.getProviderName();
		div.className = "button";
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
			layout.invalidate(general_news_section.element);
		} else {
			//general_news_loading.style.position = "absolute";
			general_news_loading.style.visibility = "hidden";
			layout.invalidate(general_news_section.element);
		}
	});
	new news('other_news_container', [], [{name:"application"}], function(n) {
		var loading = document.getElementById('other_news_loading');
		loading.parentNode.removeChild(loading);
	}, function(starts) {
		if (starts) {
			other_news_loading.style.position = "static";
			other_news_loading.style.visibility = "visible";
			layout.invalidate(other_news_section.element);
		} else {
			//other_news_loading.style.position = "absolute";
			other_news_loading.style.visibility = "hidden";
			layout.invalidate(other_news_section.element);
		}
	});
});
init_calendars();
</script>
<?php
	}
	
}
?>