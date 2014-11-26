if (typeof require != 'undefined')
	require("color.js");
/**
 * Layout events on a day column
 * @param {CalendarManager} calendar_manager containing the list of calendars
 */
function DayColumnLayout(calendar_manager) {
	var t=this;
	/** List of events in the day */
	this.events = [];
	
	this._leftSpace = 0;
	this._rightSpace = 2;
	this._topSpace = 0;
	this._bottomSpace = 0;

	/** Layout and display the given events
	 * @param {Array} events the list of events
	 * @param {Element} container where to display
	 * @param {Number} x the x position of the day column in the container
	 * @param {Number} w the width of the day column in the container
	 * @param {Number} y the y position of the day column in the container
	 * @param {Number} scale_time in minutes, to be used together with scaleHeight
	 * @param {Number} scale_height number of pixels to display <code>scalTime</code> minutes
	 */
	this.layout = function(events, container, x, w, y, scale_time, scale_height) {
		t._layout_data = {events:events, container:container, x:x, w:w, y:y, scale_time:scale_time, scale_height: scale_height};
		if (!t._timeout) {
			t._timeout = setTimeout(function(){
				for (var i = 0; i < t.events.length; ++i)
					container.removeChild(t.events[i]);
				t.events = [];
				
				for (var i = 0; i < t._layout_data.events.length; ++i)
					t.addEvent(t._layout_data.events[i], t._layout_data.container, t._layout_data.x, t._layout_data.w, t._layout_data.y, t._layout_data.scale_time, t._layout_data.scale_height);
				
				t._layoutBoxes(t._layout_data.x, t._layout_data.w, t._layout_data.y, t._layout_data.container.clientHeight-t._layout_data.y);
				t._timeout = null;
			},10);
		}
	};
	
	/**
	 * Add a new event to be displayed
	 * @param {Object} event the event to display
	 * @param {Element} container where to display
	 * @param {Number} x the x position of the day column in the container
	 * @param {Number} w the width of the day column in the container
	 * @param {Number} y the y position of the day column in the container
	 * @param {Number} scale_time in minutes, to be used together with scaleHeight
	 * @param {Number} scale_height number of pixels to display <code>scalTime</code> minutes
	 */
	this.addEvent = function(event, container, x, w, y, scale_time, scale_height) {
		var cal = window.top.CalendarsProviders.getProvider(event.calendar_provider_id).getCalendar(event.calendar_id);
		if (!cal) return; // calendar has been removed
		var min = event.start.getHours()*60+event.start.getMinutes();
		var y1 = Math.floor(min*scale_height/scale_time)+y;
		if (event.end.getDate() != event.start.getDate() || event.end.getMonth() != event.start.getMonth() || event.end.getFullYear() != event.start.getFullYear())
			min = 24*60;
		else
			min = event.end.getHours()*60+event.end.getMinutes();
		var y2 = Math.floor(min*scale_height/scale_time)+y;
		var div = createEventDiv(event,cal);
		if (!div) return;
		div.style.position = "absolute";
		div.style.top = (y1+t._topSpace)+"px";
		div.style.height = (y2-y1-2-t._topSpace-t._bottomSpace)+"px";
		div.style.left = (x+t._leftSpace)+"px";
		div.style.width = (w-3-t._leftSpace-t._rightSpace)+"px";
		div.style.zIndex = 2;
		div.style.overflow = "hidden";
		container.appendChild(div);
		this.events.push(div);
	};
	
	/** Remove all events from the display */
	this.removeEvents = function() {
		for (var i = 0; i < this.events.length; ++i)
			this.events[i].parentNode.removeChild(this.events[i]);
		this.events = [];
	};
	
	/** Layout event's boxes so they do not overlap
	 * @param {Number} cx start x
	 * @param {Number} cw width
	 * @param {Number} cy start y
	 * @param {Number} ch height
	 */
	this._layoutBoxes = function(cx, cw, cy, ch) {
		for (var i = 0; i < this.events.length; ++i) {
			this.events[i].pos = {x:this.events[i].offsetLeft-t._leftSpace,w:this.events[i].offsetWidth-1+t._leftSpace+t._rightSpace};
			this.events[i].conflicts = [];
		}
		for (var y = cy; y < cy+ch; y++) {
			var boxes = this._getBoxesAt(y);
			if (boxes.length < 2) continue;
			var min_w = 10;
			if (cw/boxes.length < min_w) min_w = 5;
			if (cw/boxes.length < min_w) {
				// too much boxes
				for (var i = 0; i < boxes.length; ++i) {
					boxes[i].style.left = (cx+i*2)+"px";
					boxes[i].style.width = "3px";
					boxes[i].pos.x = (cx+i*2);
					boxes[i].pos.w = 3;
				}
			} else {
				var all_ok;
				do {
					all_ok = true;
					var space = new WidthAvailableSpace(cx,cw);
					for (var i = 0; i < boxes.length; ++i) {
						if (space.reserve(boxes[i].pos.x, boxes[i].pos.w)) continue;
						// not enough space
						var conflicts = [];
						for (var j = 0; j < boxes[i].conflicts; ++j)
							if (!boxes.contains(boxes[i].conflicts[j]))
								conflicts.push(boxes[i].conflicts[j]);
						var s = space.get(conflicts);
						while (s != null && s.w < min_w) s = space.get(conflicts);
						if (s != null) {
							// some space is available
							boxes[i].style.left = (s.x+t._leftSpace)+"px";
							boxes[i].style.width = (s.w-3-t._leftSpace-t._rightSpace)+"px";
							boxes[i].pos.x = s.x;
							boxes[i].pos.w = s.w;
							continue;
						}
						// no more space, reduce the biggest among the previous ones
						var lowest_ratio = cw*2, lowest_index = 0;
						for (var j = 0; j < i; j++) {
							var ratio = cw/boxes[j].pos.w;
							if (ratio < lowest_ratio) {
								lowest_ratio = ratio;
								lowest_index = j;
							}
						}
						// or among conflicts
						// TODO
						var new_w = Math.floor(cw/(lowest_ratio+1));
						boxes[lowest_index].style.width = (new_w-3-t._leftSpace-t._rightSpace)+"px";
						boxes[lowest_index].pos.w = new_w;
						all_ok = false;
						break;
					}
				} while (!all_ok);
				// add the conflicts
				for (var i = 0; i < boxes.length; ++i) {
					for (var j = 0; j < boxes.length; ++j) {
						if (j == i) continue;
						if (!boxes[i].conflicts.contains(boxes[j]))
							boxes[i].conflicts.push(boxes[j]);
					}
				}
			}
		}
	};
	
	/** Returns a list of boxes which are display at the given y vertical position
	 * @param {Number} y the vertical position
	 * @returns {Array} the list of boxes
	 */
	this._getBoxesAt = function(y) {
		var boxes = [];
		for (var i = 0; i < this.events.length; ++i) {
			var box = this.events[i];
			if (box.offsetTop-t._topSpace > y) continue;
			if (box.offsetTop+box.offsetHeight+t._bottomSpace < y) continue;
			boxes.push(box);
		}
		return boxes;
	};
	
}

/**
 * Store a list of horizontal space ranges available to display boxes
 * @param {Number} x starting available point
 * @param {Number} w starting width
 */
function WidthAvailableSpace(x,w) {
	/** List of ranges */
	this.ranges = [{x:x,w:w}];
	/** Ask to take a given range
	 * @param {Number} x the x position of the requested range
	 * @param {Number} w width of the requested range
	 * @returns true if the given range is available. In this case it has been removed from the list of available ranges.
	 */
	this.reserve = function(x,w) {
		for (var i = 0; i < this.ranges.length; ++i) {
			var r = this.ranges[i];
//			if (x < r.x) continue; // area is before range
//			if (r.x+r.w <= x) continue; // range is before area
//			if (r.x >= x+w) continue; // range is after area
//			if (x+w > r.x+r.w) continue; // area is after range
			if (r.x == x) {
				if (r.w == w) {
					// exact
					this.ranges.splice(i,1);
					return true;
				}
				if (w < r.w) {
					// smaller
					r.x += w;
					r.w -= w;
					return true;
				}
				// bigger
				if (this.reserve(r.x+r.w,x+w-(r.x-r.w))) {
					this.ranges.splice(this.ranges.indexOf(r),1);
					return true;
				}
				return false;
			}
			if (r.x+r.w == x+w) {
				// end match
				if (x > r.x) {
					// reserve the end of the range
					r.w -= w;
					return true;
				}
				// bigger
				if (this.reserve(x,r.x-x)) {
					this.ranges.splice(this.ranges.indexOf(r),1);
					return true;
				}
				return false;
			}
			if (x > r.x && x+w < r.x+r.w) {
				// inside: split the range into 2
				var r2 = {x:x+w,w:(r.x+r.w)-(x+w)};
				r.w = x-r.x;
				this.ranges.splice(i+1,0,r2);
				return true;
			}
			if (x < r.x && x+w > r.x+r.w) {
				// bigger
				if (!this.reserve(x,r.x-x)) return false; // cannot reserve the area before
				if (!this.reserve(r.x+r.w,x+w-(r.x-r.w))) return false; // cannot reserve the area after
				this.ranges.splice(this.ranges.indexOf(r),1);
				return true;
			}
			if (x < r.x && x+w > r.x && x+w < r.x+r.w) {
				// start before, ends inside
				if (!this.reserve(x,r.x-x)) return false; // cannot reserve the area before
				return this.reserve(r.x, x+w-r.x);
			}
			if (x >= r.x && x < r.x+r.w && x+w > r.x+r.w) {
				// start inside, ends after
				if (!this.reserve(r.x+r.w,x+w-(r.x-r.w))) return false; // cannot reserve the area after
				return this.reserve(x, r.x+r.w-x);
			}
		}
		return false;
	};
	/** Remove a range
	 * @param {Number} x the x position
	 * @param {Number} w the width
	 */
	this.remove = function(x,w) {
		for (var i = 0; i < this.ranges.length; ++i) {
			var r = this.ranges[i];
			if (r.x == x) {
				if (r.w == w) {
					// exact
					this.ranges.splice(i,1);
					return;
				}
				if (w < r.w) {
					// smaller
					r.x += w;
					r.w -= w;
					return;
				}
				// bigger
				this.ranges.splice(i,1);
				this.remove(r.x+r.w,x+w-(r.x-r.w));
				return;
			}
			if (r.x+r.w == x+w) {
				// end match
				if (x > r.x) {
					// reserve the end of the range
					r.w -= w;
					return;
				}
				// bigger
				this.ranges.splice(i,1);
				this.remove(x,r.x-x);
				return;
			}
			if (x > r.x && x+w < r.x+r.w) {
				// inside: split the range into 2
				var r2 = {x:x+w,w:(r.x+r.w)-(x+w)};
				r.w = x-r.x;
				this.ranges.splice(i+1,0,r2);
				return;
			}
			if (x < r.x && x+w > r.x+r.w) {
				// bigger
				this.ranges.splice(i,1);
				this.remove(x,r.x-x);
				this.remove(r.x+r.w,x+w-(r.x-r.w));
				return;
			}
			if (x < r.x && x+w > r.x && x+w < r.x+r.w) {
				// start before, ends inside
				this.remove(x,r.x-x);
				this.remove(r.x, x+w-r.x);
				return;
			}
			if (x >= r.x && x < r.x+r.w && x+w > r.x+r.w) {
				// start inside, ends after
				this.remove(r.x+r.w,x+w-(r.x-r.w));
				this.remove(x, r.x+r.w-x);
				return;
			}
		}
	};
	/**
	 * Return a possible available range, taking into account the given boxes which are in conflict
	 * @param {Array} conflicts list of boxes in conflict
	 * @returns {Array} a range {x,w}
	 */
	this.get = function(conflicts) {
		if (this.ranges.length == 0) return null;
		for (var i = 0; i < this.ranges.length; ++i) {
			var r = this.ranges[i];
			var ok = true;
			for (var j = 0; j < conflicts.length; ++j) {
				// start inside
				if (conflicts[j].pos.x >= r.x && conflicts[j].pos.x < r.x+r.w) { ok = false; break; }
				// end inside
				if (conflicts[j].pos.x+conflicts[j].pos.w-1 >= r.x && conflicts[j].pos.x+conflicts[j].pos.w <= r.x+r.w) { ok = false; break; }
				// start before and end after
				if (conflicts[j].pos.x <= r.x && conflicts[j].pos.x+conflicts[j].pos.w >= r.x+r.w) { ok = false; break; }
			}
			if (ok) {
				this.ranges.splice(i,1);
				return r;
			}
		}
		// no range ok, let's take a part of a range
		for (var i = 0; i < this.ranges.length; ++i) {
			var r = this.ranges[i];
			var rr = new WidthAvailableSpace(r.x,r.w);
			for (var j = 0; j < conflicts.length; ++j)
				rr.remove(conflicts[j].x, conflicts[j].w);
			if (rr.ranges.length > 0) {
				this.ranges.splice(i,1);
				for (var j = 1; j < rr.ranges.length; ++j)
					this.ranges.push(rr.ranges[j]);
				return rr.ranges[0];
			}
		}
	};
}