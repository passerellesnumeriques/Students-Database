window.help_display_ready = true;

function helpPositionHorizontal(help_div_id, rel_pos, rel_id) {
	if (!window.help_display_ready) {
		setTimeout(function() { if (window.closing) return; helpPositionHorizontal(help_div_id, rel_pos, rel_id); }, 25);
		return;
	}
	var help_div = document.getElementById(help_div_id);
	var updater = function() {
		var rel = document.getElementById(rel_id);
		var pos = getFixedPosition(rel,true);
		if (rel_pos == "left")
			help_div.style.right = (getWindowWidth()-pos.x)+"px";
		else if (rel_pos == "center")
			help_div.style.left = Math.floor(pos.x+(rel.offsetWidth/2)-(help_div.offsetWidth/2))+"px";
		else
			help_div.style.left = (pos.x + rel.offsetWidth)+"px";
	};
	layout.listenInnerElementsChanged(document.body, updater);
	help_div.ondomremoved(function() {
		layout.unlistenInnerElementsChanged(document.body, updater);
		updater = null;
	});
	updater();
}
function helpPositionVertical(help_div_id, rel_pos, rel_id) {
	if (!window.help_display_ready) {
		setTimeout(function() { if (window.closing) return; helpPositionVertical(help_div_id, rel_pos, rel_id); }, 25);
		return;
	}
	var help_div = document.getElementById(help_div_id);
	var updater = function() {
		var rel = document.getElementById(rel_id);
		var pos = getFixedPosition(rel,true);
		if (rel_pos == "top")
			help_div.style.bottom = (getWindowHeight()-pos.y)+"px";
		else if (rel_pos == "center")
			help_div.style.top = Math.floor(pos.y+(rel.offsetHeight/2)-(help_div.offsetHeight/2))+"px";
		else if (rel_pos == "bottom")
			help_div.style.top = (pos.y + rel.offsetHeight)+"px";
		else if (rel_pos.startsWith("inside_top:"))
			help_div.style.top = (pos.x+parseInt(rel_pos.substring(11)))+"px";
	};
	layout.listenInnerElementsChanged(document.body, updater);
	help_div.ondomremoved(function() {
		layout.unlistenInnerElementsChanged(document.body, updater);
		updater = null;
	});
	updater();
}
function helpSystemArrow(from, to_selector, onover, force_connect) {
	if (!window.help_display_ready) {
		setTimeout(function() { if (window.closing) return; helpSystemArrow(from, to_selector, onover, force_connect); }, 25);
		return;
	}
	var to;
	if (to_selector.startsWith("@parent"))
		to = $(to_selector.substring(7), window.parent.document);
	else
		to = $(to_selector);
	if (to.length == 0) {
		//alert("Help system: cannot find element matching "+to_selector);
		return;
	}
	to = to.get(0);
	var arrow = null;
	var display_arrow = function() {
		if (arrow) return;
		arrow = drawing.connectElements(document.getElementById(from), to, drawing.CONNECTOR_NONE, drawing.CONNECTOR_ARROW, "rgba(60,60,100,0.66)", 2, force_connect);
	};
	var hide_arrow = function() {
		if (!arrow) return;
		arrow.parentNode.removeChild(arrow);
		arrow = null;
	};
	var elem = document.getElementById(from);
	if (onover) {
		elem.onmouseover = function() {
			display_arrow();
		};
		elem.onmouseout = function() {
			hide_arrow();
		};
	} else {
		setTimeout(display_arrow,1);
	}
	elem.ondomremoved(function() {
		hide_arrow();
	});
}

if (window == window.top) {
	window.top.help_system = {
		_avail_help: [],
		registerAvailableHelp: function(win, help_id) {
			for (var i = 0; i < this._avail_help.length; ++i)
				if (this._avail_help[i].win == win && this._avail_help[i].help_id == help_id) return;
			this._avail_help.push({win:win,help_id:help_id});
		},
		_window_closed: function(win) {
			for (var i = 0; i < this._avail_help.length; ++i)
				if (this._avail_help[i].win == win) {
					this._avail_help.splice(i,1);
					i--;
				}
		},
		activateHelp: function() {
			if (this._avail_help.length == 0) {
				info_dialog("Unfortunately, there is no help available on this page.<br/>Something is not clear ? Please <a href='/dynamic/application/page/send_feedback' target='application_frame' onclick='get_popup_window_from_element(this).close();'>write a feedback</a>");
				return;
			}
			var ids = [];
			for (var i = 0; i < this._avail_help.length; ++i)
				ids.push(this._avail_help[i].help_id);
			var t=this;
			service.json("help","show",{ids:ids},function(res) {
				var windows = [];
				for (var i = 0; i < t._avail_help.length; ++i)
					if (!windows.contains(t._avail_help[i].win))
						windows.push(t._avail_help[i].win);
				for (var i = 0; i < windows.length; ++i)
					if (!windows[i].closing && windows[i].location)
						windows[i].location.reload();
			});
		}
	};
	window.init_help_system = function() {
		if (!window.top.pnapplication) {
			setTimeout(window.init_help_system, 25);
			return;
		}
		window.init_help_system = null;
		window.top.pnapplication.onwindowclosed.add_listener(function(c) {
			window.top.help_system._window_closed(c.win);
		});
	};
} else if (!window.top.help_system)
	window.top.require("help_system.js");