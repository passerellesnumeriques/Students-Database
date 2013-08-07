dnd = {
	configure_drag_element: function(element,clone_when_start,move_handler,data_getter) {
		if (element.onmousedown)
			element._onmousedown = element.onmousedown;
		element.dnd = {
			clone_when_start: clone_when_start,
			move_handler: move_handler,
			data_getter: data_getter,
			start_x: -1, start_y: -1, cur_x: -1, cur_y: -1
		};
		element.onmousedown = function(evt) {
			evt = getCompatibleMouseEvent(evt);
			var e = this;
			e.dnd.start_x = e.dnd.cur_x = evt.x;
			e.dnd.start_y = e.dnd.cur_y = evt.y;
			e.dnd.mousedown = true;
			e.dnd.mousemove = false;
			dnd._drag.push(e);
			return false;
		};
	},
	configure_drag_area: function(area_element, element_from_area_getter, clone_when_start, move_handler, data_getter) {
		var area = {
			area_element: area_element,
			element_from_area_getter: element_from_area_getter,
			clone_when_start: clone_when_start,
			move_handler: move_handler,
			data_getter: data_getter
		};
		dnd._drag_area.push(area);
	},
	configure_drop_element: function(element, accept, drop) {
		element.drop = {
			accept: accept,
			drop: drop,
			element: element
		};
	},
	configure_drop_area: function(area, accept, drop) {
		dnd._drop_area.push({
			element: area,
			accept: accept,
			drop: drop
		});
	},
	_drag: [],
	_drag_area: [],
	_drop_area: [],
	_mousedown: function(evt) {
		evt = getCompatibleMouseEvent(evt);
		if (evt.button != 0) return true;
		if (dnd._drag_area.length == 0) return true;
		for (var i = 0; i < dnd._drag_area.length; ++i) {
			var area = dnd._drag_area[i];
			var area_x1 = absoluteLeft(area.area_element);
			if (evt.x < area_x1) continue;
			var area_y1 = absoluteTop(area.area_element);
			if (evt.y < area_y1) continue;
			var area_x2 = area_x1 + area.area_element.offsetWidth;
			if (evt.x > area_x2) continue;
			var area_y2 = area_y1 + area.area_element.offsetHeight;
			if (evt.y > area_y2) continue;
			var e = area.element_from_area_getter(evt.x,evt.y);
			if (e == null) continue;
			e.dnd = {
				clone_when_start: area.clone_when_start,
				move_handler: area.move_handler,
				data_getter: area.data_getter,
				start_x: evt.x, start_y: evt.y, cur_x: evt.x, cur_y: evt.y,
				mousedown: true, mousemove: false
			};
			dnd._drag.push(e);
			return false;
		}
		return true;
	},
	_mousemove: function(orig_evt) {
		if (dnd._drag.length == 0) return true;
		evt = getCompatibleMouseEvent(orig_evt);
		for (var i = 0; i < dnd._drag.length; ++i) {
			var e = dnd._drag[i];
			if (!e.dnd.mousedown) continue;
			if (!e.dnd.mousemove) {
				if (evt.x == e.dnd.start_x && evt.y == e.dnd.start_y) continue; // this is a fake move: ignore it
				if (e.dnd.clone_when_start) {
					var ec = e.cloneNode(true);
					for (var n in e)
						if (!ec[n]) try { ec[n] = e[n]; } catch (ex) {};
					e = ec;
					dnd._drag[i] = e;
					e.style.position = "fixed";
					document.body.appendChild(e);
				} else {
					e.style.position = "fixed";
				}
				e.dnd.mousemove = true;
				if (e.dnd.data_getter) {
					e.dnd.data = e.dnd.data_getter(e);
					e.dnd.icon = document.createElement("IMG");
					e.dnd.icon.style.position = "absolute";
					document.body.appendChild(e.dnd.icon);
				}
			}
			if (e.dnd.move_handler != null) {
				e.dnd.move_handler(e, e.dnd.start_x, e.dnd.start_y, e.dnd.cur_x, e.dnd.cur_y, evt.x, evt.y);
				e.dnd.cur_x = evt.x;
				e.dnd.cur_y = evt.y;
			} else {
				e.dnd.cur_x = evt.x;
				e.dnd.cur_y = evt.y;
				e.style.left = evt.x+"px";
				e.style.top = evt.y+"px";
			}
			if (e.dnd.icon) {
				var target = dnd._get_target(evt.x, evt.y);
				var ic;
				if (target == null) ic = theme.icons_16.forbidden;
				else {
					ic = target.accept(e.dnd.data, evt.x, evt.y);
					if (ic == null) ic = theme.icons_16.forbidden;
				}
				if (ic.length > 0) {
					e.dnd.icon.src = ic;
					e.dnd.icon.style.visibility = "visible";
					e.dnd.icon.style.left = (evt.x-8)+"px";
					e.dnd.icon.style.top = (evt.y-8)+"px";
				} else {
					e.dnd.icon.style.visibility = "hidden";
				}
			}
			stopEventPropagation(orig_evt);
			return false;
		}
		return true;
	},
	_mouseup: function(evt) {
		if (dnd._drag.length == 0) return true;
		evt = getCompatibleMouseEvent(evt);
		for (var i = 0; i < dnd._drag.length; ++i) {
			var e = dnd._drag[i];
			if (!e.dnd.mousedown) continue;
			if (!e.dnd.mousemove) continue;
			if (e.dnd.move_handler != null) {
				e.dnd.move_handler(e, e.dnd.start_x, e.dnd.start_y, e.dnd.cur_x, e.dnd.cur_y, -1, -1);
			}
			e.dnd.mousedown = false;
			e.dnd.mousemove = false;
			
			if (e.dnd.icon) {
				var target = dnd._get_target(evt.x, evt.y);
				if (target != null && target.accept(e.dnd.data, evt.x, evt.y) != null) {
					target.drop(e.dnd.data, evt.x, evt.y, target.element, e);
				}
				document.body.removeChild(e.dnd.icon);
			}
			if (e.dnd.clone_when_start)
				e.parentNode.removeChild(e);
		}
		dnd._drag = [];
		return false;
	},
	_get_target: function(x,y) {
		var l = getElementsAt(x,y);
		for (var i = 0; i < l.length; ++i)
			if (l[i].drop) return l[i].drop;
		for (var i = 0; i < dnd._drop_area.length; ++i) {
			var a = dnd._drop_area[i];
			if (a.element.parentNode == null) {
				dnd._drop_area.slice(i, 1);
				i--;
				continue;
			}
			var ax = absoluteLeft(a.element);
			if (x < ax) continue;
			var ay = absoluteTop(a.element);
			if (y < ay) continue;
			var aw = a.element.offsetWidth;
			if (x > ax+aw) continue;
			var ah = a.element.offsetHeight;
			if (y > ay+ah) continue;
			return a;
		}
		return null;
	}
};
if (typeof listenEvent != 'undefined') {
	listenEvent(window,'mousedown',dnd._mousedown);
	listenEvent(window,'mousemove',dnd._mousemove);
	listenEvent(window,'mouseup',dnd._mouseup);
}