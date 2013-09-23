if (typeof require != 'undefined')
	require("color.js");
function DayColumnLayout() {
	
	this.events = [];

	this.layout = function(events, container, x, w, y, scale_time, scale_height) {
		for (var i = 0; i < this.events.length; ++i)
			container.removeChild(this.events[i]);
		this.events = [];
		
		for (var i = 0; i < events.length; ++i)
			this.add_event(events[i], container, x, w, y, scale_time, scale_height);
		
		this.layout_boxes(x, w, y, container.clientHeight-y);
	};
	
	this.add_event = function(event, container, x, w, y, scale_time, scale_height) {
		var min = event.start.getHours()*60+event.start.getMinutes();
		var y1 = Math.floor(min*scale_height/scale_time)+y;
		min = event.end.getHours()*60+event.end.getMinutes();
		var y2 = Math.floor(min*scale_height/scale_time)+y;
		var div = document.createElement("DIV");
		div.style.position = "absolute";
		require("color.js", function() {
			div.style.border = "1px solid "+color_string(color_darker(parse_hex_color(event.calendar.color), 0x60));
		});
		div.style.backgroundColor = "#"+event.calendar.color;
		div.style.top = y1+"px";
		div.style.height = (y2-y1-3)+"px";
		div.style.left = x+"px";
		div.style.width = (w-3)+"px";
		div.style.zIndex = 2;
		div.style.padding = "1px";
		var head = document.createElement("DIV");
		head.style.fontSize = "8pt";
		var time_str = this._2digits(event.start.getHours())+":"+this._2digits(event.start.getMinutes())+"-"+this._2digits(event.end.getHours())+":"+this._2digits(event.end.getMinutes());
		head.appendChild(document.createTextNode(time_str));
		div.appendChild(head);
		div.appendChild(document.createTextNode(event.title));
		div.style.overflow = "hidden";
		div.title = event.calendar.name+"\r\n"+time_str+"\r\n"+event.title;
		container.appendChild(div);
		this.events.push(div);
	};
	
	this.remove_events = function() {
		for (var i = 0; i < this.events.length; ++i)
			this.events[i].parentNode.removeChild(this.events[i]);
		this.events = [];
	};
	
	this._2digits = function(n) {
		var s = ""+n;
		while (s.length < 2) s = "0"+s;
		return s;
	};
	
	this.layout_boxes = function(cx, cw, cy, ch) {
		for (var i = 0; i < this.events.length; ++i)
			this.events[i].pos = {x:this.events[i].offsetLeft,w:this.events[i].offsetWidth};
		for (var y = cy; y < cy+ch; y++) {
			var boxes = this.get_boxes_at(y);
			if (boxes.length < 2) continue;
			var all_ok;
			do {
				all_ok = true;
				var space = new WidthAvailableSpace(cx,cw);
				for (var i = 0; i < boxes.length; ++i) {
					if (boxes[i].innerHTML == "Inside") {
						var toto = 1;
						toto++;
					}
					if (space.reserve(boxes[i].pos.x, boxes[i].pos.w)) continue;
					// not enough space
					var s = space.get();
					if (s != null) {
						// some space is available
						boxes[i].style.left = s.x+"px";
						boxes[i].style.width = (s.w-3)+"px";
						boxes[i].pos.x = s.x;
						boxes[i].pos.w = s.w;
						continue;
					}
					// no more space, reduce the previous ones
					for (var j = 0; j < i; j++) {
						var ratio = cw/boxes[j].offsetWidth;
						var new_w = Math.floor(cw/(ratio+1));
						boxes[j].style.width = (new_w-3)+"px";
						boxes[j].pos.w = new_w;
					}
					all_ok = false;
					break;
				}
			} while (!all_ok);
		}
	};
	
	this.get_boxes_at = function(y) {
		var boxes = [];
		for (var i = 0; i < this.events.length; ++i) {
			var box = this.events[i];
			if (box.offsetTop > y) continue;
			if (box.offsetTop+box.offsetHeight < y) continue;
			boxes.push(box);
		}
		return boxes;
	};
	
}

function WidthAvailableSpace(x,w) {
	this.ranges = [{x:x,w:w}];
	this.reserve = function(x,w) {
		for (var i = 0; i < this.ranges.length; ++i) {
			var r = this.ranges[i];
			if (x < r.x) continue; // area is before range
			if (r.x+r.w <= x) continue; // range is before area
			if (r.x >= x+w) continue; // range is after area
			if (x+w > r.x+r.w) continue; // area is after range
			if (r.x == x) {
				if (r.w == w) {
					// exact
					this.ranges.splice(i,1);
					return true;
				}
				// smaller
				r.x += w;
				r.w -= w;
				return true;
			}
			if (r.x+r.w == x+w) {
				// end match
				r.w -= w;
				return true;
			}
			// inside: split the range into 2
			var r2 = {x:r.x+r.w,w:(r.x+r.w)-(x+w)};
			r.w = x-r.x;
			this.ranges.splice(i+1,0,r2);
			return true;
		}
		return false;
	};
	this.get = function() {
		if (this.ranges.length == 0) return null;
		var r = this.ranges[0];
		this.ranges.splice(0,1);
		return r;
	};
}