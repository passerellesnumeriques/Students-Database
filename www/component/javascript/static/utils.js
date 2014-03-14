/** 
 * return true if this string starts with the given string
 */
String.prototype.startsWith=function(s){return this.length<s.length?false:this.substring(0,s.length)==s;};
/** 
 * return true if this string ends with the given string
 */
String.prototype.endsWith=function(s){return this.length<s.length?false:this.substring(this.length-s.length)==s;};
/** 
 * remove leading and trailing spaces, and return the result
 */
String.prototype.trim=function() {
	if (this.length == 0) return "";
	var start, end;
	for (start = 0; start < this.length; start++)
		if (!isSpace(this.charAt(start))) break;
	for (end = this.length; end > 0; end--)
		if (!isSpace(this.charAt(end-1))) break;
	return this.substring(start, end);
};

/** check if the given character is a space (space, tab, or line return)
 * @param {String} c the character
 * @returns {Boolean} if c is a space
 */
function isSpace(c) { return (c == ' ' || c == '\t' || c == '\r' || c == '\n'); }
/** check if the given character is a letter (small or capital)
 * @param {String} c the character
 * @returns {Boolean} true if the given character is a letter
 */
function isLetter(c) {
	var ord = c.charCodeAt(0);
	if (ord >= 'a'.charCodeAt(0) && ord <= 'z'.charCodeAt(0)) return true;
	if (ord >= 'A'.charCodeAt(0) && ord <= 'Z'.charCodeAt(0)) return true;
	return false;
}

/**
* Set a uniform case according to a given separator
* @parameter {String} separator separator to use between words
* @returns {String} the same string with a capitalized first letter
*/
String.prototype.firstLetterCapitalizedForSeparator = function(separator) {
	var text_split = this.split(separator);
	for(var i = 0; i < text_split.length; i++){
		text_split[i] = text_split[i].charAt(0).toUpperCase()+text_split[i].substring(1);
	}
	return text_split.join(separator);
};

/**
* Set a uniform case according to " ", "'" and "-"
* @returns the same string with a capitalized first letter, and other lowered
*/
String.prototype.uniformFirstLetterCapitalized = function(){
	var text = this.toLowerCase();
	var result = text.firstLetterCapitalizedForSeparator(" ");
	result = result.firstLetterCapitalizedForSeparator("-");
	result = result.firstLetterCapitalizedForSeparator("'");
	return result;
};

/**
 * Test if a string is not empty (in terms of visibility)
 * @returns {Boolean} true if the given string is not only made of space or is empty; else return false
 */
String.prototype.checkVisible = function(){
	var is_visible = false;
	var text_split = this.split("");
	for(var i = 0; i < text_split.length; i++){
		if(text_split[i] != "" && text_split[i] != " " && text_split[i] !='/r' && text_split[i] != '/n' && text_split[i] != '/t'){
			is_visible = true;
			break;
		}
	}
	return is_visible;
};

/** Check if the given element is in the array
 * @param {any} e the element to search 
 * @returns true if this array contains the given element
 */
Array.prototype.contains=function(e){for(var i=0;i<this.length;++i)if(this[i]==e)return true;return false;};
/** 
 * remove all occurences of the given element from this array, if any.
 * @param {any} e the element to remove
 */
Array.prototype.remove=function(e){for(var i=0;i<this.length;++i)if(this[i]==e){this.splice(i,1);i--;};};

/**
 * Clone an object structure 
 * @param {Object} o the object to clone
 * @param {Number} recursive_depth maximum depth of clone
 * @returns {Object} the clone
 */
function objectCopy(o, recursive_depth) {
	if (o == null) return null;
	if (typeof o == 'string') return ""+o;
	if (typeof o == 'number') return o;
	var c = new Object();
	for (var attr in o) {
		var value = o[attr];
		if (!recursive_depth) { c[attr] = value; continue; }
		if (value == null) { c[attr] = null; continue; }
		if (typeof value == 'object') {
			if (value instanceof Date || getObjectClassName(value) == "Date")
				c[attr] = new Date(value.getTime());
			else if (value instanceof Array || getObjectClassName(value) == "Array") {
				c[attr] = [];
				for (var i = 0; i < value.length; ++i)
					c[attr].push(valueCopy(value[i], recursive_depth-1));
			} else {
				c[attr] = objectCopy(value, recursive_depth-1);
			}
		} else
			c[attr] = value;
	}
	return c;
}
/**
 * Copy the given value
 * @param {any} value the value to copy
 * @param {Number} obj_depth maximum depth in objects to copy
 * @returns {any} the copy
 */
function valueCopy(value, obj_depth) {
	if (value == null) return null;
	if (typeof value == 'object') {
		if (value instanceof Date || getObjectClassName(value) == "Date")
			return new Date(value.getTime());
		if (value instanceof Array || getObjectClassName(value) == "Array") {
			var a = [];
			for (var i = 0; i < value.length; ++i)
				a.push(valueCopy(value[i], obj_depth-1));
			return a;
		}
		return objectCopy(value, obj_depth);
	}
	return value;
}

var _generate_id_counter = 0;
/**
 * Generates an unique id. 
 * @returns {String} the generated id
 */
function generateID() {
	return "id"+(_generate_id_counter++);
}

/**
 * Return the absolute position of the left edge, relative to the given element or to the document
 * @param e the element to get the absolute position
 * @param relative the element from which we want the absolute position, or null to get the position in the document
 */
function absoluteLeft(e,relative) {
	var left = e.offsetLeft;
	try { 
		if (e.offsetParent && e.offsetParent != relative) {
			var p = e;
			do {
				p = p.parentNode;
				left -= p.scrollLeft;
			} while (p != e.offsetParent);
			left += absoluteLeft(e.offsetParent,relative); 
		}
	} catch (ex) {}
	return left;
}
/**
 * Return the absolute position of the top edge, relative to the given element or to the document
 * @param e the element to get the absolute position
 * @param relative the element from which we want the absolute position, or null to get the position in the document
 */
function absoluteTop(e,relative) {
	var top = e.offsetTop;
	try { 
		if (e.offsetParent && e.offsetParent != relative) {
			var p = e;
			do {
				p = p.parentNode;
				top -= p.scrollTop;
			} while (p != e.offsetParent);
			top += absoluteTop(e.offsetParent,relative); 
		}
	} catch (ex) {}
	return top;
}
/**
 * Return the first parent having a CSS attribute position:relative, or the document.body
 * @param e the html element
 */
function getAbsoluteParent(e) {
	var p = e.parentNode;
	do {
		if (getComputedStyle(p).position == 'relative')
			return p;
		p = p.parentNode;
	} while(p != null && p.nodeType == 1);
	return document.body;
}

function getAbsoluteCoordinatesRelativeToWindowTop(frame) {
	if (frame.parent == null || frame.parent == frame || frame.parent == window.top) return {x:0,y:0};
	var pos = getAbsoluteCoordinatesRelativeToWindowTop(frame.parent);
	pos.x += absoluteLeft(frame.frameElement);
	pos.y += absoluteTop(frame.frameElement);
	return pos;
}

/**
 * Return the list of html elements at the given position in the document
 * @param x
 * @param y
 * @returns {Array}
 */
function getElementsAt(x,y) {
	var list = [];
	var disp = [];
	do {
		var e = document.elementFromPoint(x,y);
		if (e == document || e == document.body || e == window || e.nodeName == "HTML" || e.nodeName == "BODY") break;
		if (e == null) break;
		list.push(e);
		disp.push(e.style.display);
		e.style.display = "none";
	} while (true);
	for (var i = 0; i < list.length; ++i)
		list[i].style.display = disp[i];
	return list;
}

/**
 * Scroll up the given element
 * @param element
 * @param {Number} scroll
 */
function scrollUp(element, scroll) {
	// try to set scrollTop
	var s = element.scrollTop;
	element.scrollTop = s - scroll;
	if (element.scrollTop != s) return; // it changed, so it worked
	// TODO
}
/**
 * Scroll down the given element
 * @param element
 * @param {Number} scroll
 */
function scrollDown(element, scroll) {
	scrollUp(element, -scroll);
}
/**
 * Scroll left the given element
 * @param element
 * @param {Number} scroll
 */
function scrollLeft(element, scroll) {
	// try to set scrollTop
	var s = element.scrollLeft;
	element.scrollLeft = s - scroll;
	if (element.scrollLeft != s) return; // it changed, so it worked
	// TODO
}
/**
 * Scroll right the given element
 * @param element
 * @param {Number} scroll
 */
function scrollRight(element, scroll) {
	scrollLeft(element, -scroll);
}

/**
 * Return the first parent of the given element, being scrollable 
 * @param element
 */
function getScrollableContainer(element) {
	var parent = element.parentNode;
	do {
		if (parent == document.body) return parent;
		if (parent.scrollHeight != parent.clientHeight) return parent;
		if (parent.scrollWidth != parent.clientWidth) return parent;
		parent = parent.parentNode;
	} while (parent != null);
	return document.body;
}

/**
 * Scroll all necessary scrollable elements to make the given element visible in the screen.
 * @param element
 */
function scrollToSee(element) {
	var parent = getScrollableContainer(element);
	var x1 = absoluteLeft(element, parent);
	var y1 = absoluteTop(element, parent);
	var x2 = x1+element.offsetWidth;
	var y2 = y1+element.offsetHeight;
	if (y1 < parent.scrollTop) {
		// the element is before, we need to scroll up
		scrollUp(parent, parent.scrollTop-y1);
	} else if (y2 > parent.scrollTop+parent.clientHeight) {
		// the element is after, we need to scroll down
		scrollDown(parent, y2-(parent.scrollTop+parent.clientHeight));
	}
	if (x1 < parent.scrollLeft) {
		// the element is before, we need to scroll left
		scrollLeft(parent, parent.scrollLeft-x1);
	} else if (x2 > parent.scrollLeft+parent.clientWidth) {
		// the element is after, we need to scroll down
		scrollRight(parent, x2-(parent.scrollLeft+parent.clientWidth));
	}
	// TODO same with parent, which may not be visible...
/*
				var x = absoluteLeft(cell, container);
				if (x < container.scrollLeft)
					container.scrollLeft = x;
				else if (container.scrollLeft+container.clientWidth < x+cell.offsetWidth)
					container.scrollLeft = x+cell.offsetWidth-container.clientWidth;
				var y = absoluteTop(cell, container);
				if (y < container.scrollTop)
					container.scrollTop = y;
				else if (container.scrollTop+container.clientHeight < y+cell.offsetHeight)
					container.scrollTop = y+cell.offsetHeight-container.clientHeight;
 */	
}

function scrollTo(element) {
	var parent = getScrollableContainer(element);
	var x1 = absoluteLeft(element, parent);
	var y1 = absoluteTop(element, parent);
	var x2 = x1+element.offsetWidth;
	var y2 = y1+element.offsetHeight;
	if (y1 < parent.scrollTop) {
		// the element is before, we need to scroll up
		scrollUp(parent, parent.scrollTop-y1);
	} else if (y2 > parent.scrollTop+parent.clientHeight) {
		// the element is after, we need to scroll down
		scrollDown(parent, y2-(parent.scrollTop+parent.clientHeight));
	} else {
		scrollDown(parent, -(y1-(parent.scrollTop+parent.clientHeight)));
	}
	if (x1 < parent.scrollLeft) {
		// the element is before, we need to scroll left
		scrollLeft(parent, parent.scrollLeft-x1);
	} else if (x2 > parent.scrollLeft+parent.clientWidth) {
		// the element is after, we need to scroll down
		scrollRight(parent, x2-(parent.scrollLeft+parent.clientWidth));
	} else {
		scrollRight(parent, -(x1-(parent.scrollLeft+parent.clientWidth)));
	}
	// TODO same with parent, which may not be visible...
}

/** Represent an URL
 * @constructor
 * @param {String} s string containing the URL to be parsed
 * @property {String} protocol the protocol of the URL (i.e. http)
 * @property {String} host the hostname (i.e. www.google.com)
 * @property {Number} port the port number (i.e. 80)
 * @property {String} path the path of the resource pointed by this URL
 * @property {Object} params the parameters of the URL (i.e. path?param1=value1&param2=value2 will create an object with 2 attributes)
 * @property {String} hash the anchor
 */
function URL(s) {
	var i = s.indexOf("://");
	if (i > 0) {
		this.protocol = s.substr(0, i).toLowerCase();
		s = s.substr(i+3);
		i = s.indexOf("/");
		this.host = s.substr(0,i);
		s = s.substr(i);
		i = this.host.indexOf(":");
		if (i > 0) {
			this.port = this.host.substr(i+1);
			this.host = this.host.substr(0,i);
		} else
			this.port = null;
	} else {
		if (window) {
			this.protocol = window.location.protocol.substr(0,window.location.protocol.length-1);
			this.host = window.location.hostname;
			this.port = window.location.port;
		} else {
			this.protocol = "";
			this.host = "";
			this.port = "";
		}
	}
	i = s.indexOf('#');
	if (i > 0) {
		this.hash = s.substr(i+1);
		s = s.substr(0,i);
	}
	i = s.indexOf('?');
	this.params = new Object();
	if (i > 0) {
		this.path = s.substr(0,i);
		s = s.substr(i+1);
		while (s.length > 0 && (i = s.indexOf('&')) >= 0) {
			var p = s.substr(0, i);
			s = s.substr(i+1);
			i = p.indexOf('=');
			if (i > 0)
				this.params[decodeURIComponent(p.substr(0,i))] = decodeURIComponent(p.substr(i+1));
			else
				this.params[decodeURIComponent(p)] = "";
		}
		if (s.length > 0) {
			i = s.indexOf('=');
			if (i > 0)
				this.params[decodeURIComponent(s.substr(0,i))] = decodeURIComponent(s.substr(i+1));
			else
				this.params[decodeURIComponent(s)] = "";
		}
	} else
		this.path = s;
	
	// resolve .. in path
	if (this.path.substr(0,1) != "/" && window.location.pathname) {
		s = window.location.pathname;
		i = s.lastIndexOf('/');
		s = s.substr(0,i+1);
		this.path = s + this.path;
	}
	while ((i = this.path.indexOf('/../')) > 0) {
		var j = this.path.substr(0,i).lastIndexOf('/');
		if (j < 0) break;
		this.path = this.path.substr(0,j+1)+this.path.substr(i+4);
	}
	
	this.host = this.host.toLowerCase();
	this.path = this.path.toLowerCase();
	
	/** create a string representing the URL
	 * @method URL#toString
	 */
	this.toString = function() {
		var s;
		if (this.protocol) {
			s = this.protocol+"://"+this.host;
			if (this.port) s += ":"+this.port;
		} else
			s = "";
		s += this.path;
		var first = true;
		for (var name in this.params) {
			if (first) { s += "?"; first = false; } else s += "&";
			s += encodeURIComponent(name) + "=" + encodeURIComponent(this.params[name]);
		}
		if (this.hash)
			s += "#"+this.hash;
		return s;
	};
}

/** Event
 * @constructor
 */
function Custom_Event() {
	this.listeners = [];
	/**
	 * Add a listener to this event
	 * @param listener
	 */
	this.add_listener = function(listener) { this.listeners.push(listener); };
	this.remove_listener = function(listener) { this.listeners.remove(listener); };
	/**
	 * Trigger the event: call all listeners with the given data as parameter
	 * @param data
	 */
	this.fire = function(data) {
		var list = [];
		for (var i = 0; i < this.listeners.length; ++i) list.push(this.listeners[i]);
		for (var i = 0; i < list.length; ++i) 
			try { list[i](data); } 
			catch (e) {
				log_exception(e, "occured in event listener: "+list[i]);
			}
	};
}

function log_exception(e, additional_message) {
	var msg = e.message;
	if (typeof e.fileName != 'undefined') {
		msg += " ("+e.fileName;
		if (typeof e.lineNumber != 'undefined') msg += ":"+e.lineNumber;
		msg += ")";
	}
	if (additional_message)
		msg += " "+additional_message;
	window.top.console.error(msg);
	var stack = null;
	if (e.stack)
		stack = e.stack;
	else if(e.stacktrace)
		stack = e.stacktrace;
	else {
		var s = "";
	    var currentFunction = arguments.callee.caller;
	    while (currentFunction) {
	      var fn = currentFunction.toString();
	      var fname = fn.substring(0, fn.indexOf('{'));;
	      s += fname+"\r\n";
	      currentFunction = currentFunction.caller;
	    }
	    stack = s;
	}
	if (stack)
		window.top.console.error("Stack trace:"+stack);
}

/**
 * Default implementation of error_dialog is using alert
 * @param {String} message error message
 */
function error_dialog(message) {
	alert(message);
}

/**
 * Lock the screen by adding a semi-transparent element on top of the window
 * @param onclick called when the user click on the element on top of the window
 * @param content html code or html element to be put in the center of the element
 * @returns the element on top of the window created by this function
 */
function lock_screen(onclick, content) {
	var div = document.getElementById('lock_screen');
	if (div) {
		div.usage_counter++;
		return div;
	}
	div = document.createElement('DIV');
	div.usage_counter = 1;
	div.id = "lock_screen";
	div.style.backgroundColor = "rgba(128,128,128,0.5)";
	div.style.position = "fixed";
	div.style.top = "0px";
	div.style.left = "0px";
	div.style.width = getWindowWidth()+"px";
	div.style.height = getWindowHeight()+"px";
	div.style.zIndex = 10;
	if (onclick)
		div.onclick = onclick;
	if (content)
		set_lock_screen_content(div, content);
	if (typeof animation != 'undefined')
		div.anim = animation.fadeIn(div,200,null,10,100);
	div.listener = function() {
		div.style.width = getWindowWidth()+"px";
		div.style.height = getWindowHeight()+"px";
	};
	listenEvent(window, 'resize', div.listener);
	return document.body.appendChild(div);
}
function set_lock_screen_content(div, content) {
	while (div.childNodes.length > 0) div.removeChild(div.childNodes[0]);
	var table = document.createElement("TABLE"); div.appendChild(table);
	table.style.width = "100%";
	table.style.height = "100%";
	var tr = document.createElement("TR"); table.appendChild(tr);
	var td = document.createElement("TD"); tr.appendChild(td);
	td.style.verticalAlign = 'middle';
	td.style.textAlign = 'center';
	var d = document.createElement("DIV");
	d.className = 'lock_screen_content';
	if (typeof content == 'string')
		d.innerHTML = content;
	else
		d.appendChild(content);
	td.appendChild(d);
}
/**
 * Remove the given element, previously created by using the function lock_screen
 * @param div
 */
function unlock_screen(div) {
	if (!div) div = document.getElementById('lock_screen');
	if (!div) return;
	if (typeof div.usage_counter != 'undefined') {
		div.usage_counter--;
		if (div.usage_counter > 0) return;
	}
	unlistenEvent(window, 'resize', div.listener);
	if (typeof animation != 'undefined') {
		div.id = '';
		if (div.anim) animation.stop(div.anim);
		animation.fadeOut(div,200,function(){
			if (div.parentNode == document.body)
				document.body.removeChild(div);				
		},100,0);
	} else if (div.parentNode == document.body)
		document.body.removeChild(div);
}

function debug_object_to_string(o, indent) {
	if (!indent) indent = "";
	if (typeof o == 'object') {
		if (o instanceof Date)
			return o.toString();
		var s = "{\r\n";
		for (var name in o) {
			s += indent+"    "+name+":"+debug_object_to_string(o[name], indent+"    ")+",\r\n";
		}
		s += "}";
		return s;
	}
	return ""+o;
}

function parseSQLDate(s) {
	var d = new Date();
	d.setHours(0,0,0,0);
	var a = s.split("-");
	if (a.length == 3)
		d.setFullYear(parseInt(a[0]), parseInt(a[1])-1, parseInt(a[2]));
	return d;
};
function _2digits(n) {
	var s = ""+n;
	while (s.length < 2) s = "0"+s;
	return s;
};
function dateToSQL(d) {
	if (d == null) return null;
	return d.getFullYear()+"-"+_2digits(d.getMonth()+1)+"-"+_2digits(d.getDate());
};
function _2Hex(val) {
	return HexDigit(Math.floor(val/16))+HexDigit(val%16);
}
function HexDigit(val) {
	if (val < 10) return ""+val;
	return String.fromCharCode("A".charCodeAt(0)+(val-10));
}

function getDateString(d) {
	return _2digits(d.getDate())+" "+getMonthName(d.getMonth()+1)+" "+d.getFullyear();
}

function getMonthName(month) {
	switch(month) {
	case 1: return "January";
	case 2: return "February";
	case 3: return "March";
	case 4: return "April";
	case 5: return "May";
	case 6: return "June";
	case 7: return "July";
	case 8: return "August";
	case 9: return "September";
	case 10: return "October";
	case 11: return "November";
	case 12: return "December";
	}
}
function getMonthShortName(month) {
	switch(month) {
	case 1: return "Jan";
	case 2: return "Feb";
	case 3: return "Mar";
	case 4: return "Apr";
	case 5: return "May";
	case 6: return "Jun";
	case 7: return "Jul";
	case 8: return "Aug";
	case 9: return "Sep";
	case 10: return "Oct";
	case 11: return "Nov";
	case 12: return "Dec";
	}
}
function getDayName(d) {
	switch (d) {
	case 0: return "Monday";
	case 1: return "Tuesday";
	case 2: return "Wednesday";
	case 3: return "Thursday";
	case 4: return "Friday";
	case 5: return "Saturday";
	case 6: return "Sunday";
	}
}
function getDayShortName(d) {
	switch (d) {
	case 0: return "Mon";
	case 1: return "Tue";
	case 2: return "Wed";
	case 3: return "Thu";
	case 4: return "Fri";
	case 5: return "Sat";
	case 6: return "Sun";
	}
}
function getDayLetter(d) {
	switch (d) {
	case 0: return "M";
	case 1: return "T";
	case 2: return "W";
	case 3: return "T";
	case 4: return "F";
	case 5: return "S";
	case 6: return "S";
	}
}

/**
 * Set the common style to the tables with header
 * @param table the table to set
 * @param th_header
 * @param thead_color the rgb color to display into the header
 */
function setCommonStyleTable(table,th_header,thead_color){
	table.style.borderSpacing = "0";
	table.style.marginLeft = "5px";
	table.style.marginBottom = "3px";
	setBorderRadius(table, 5, 5, 5, 5, 5, 5, 5, 5);
	table.style.border = "1px solid";
	th_header.style.textAlign = "left";
	th_header.style.padding = "2px 5px 2px 5px";
	th_header.style.backgroundColor = thead_color;
	th_header.style.width = "100%";
	setBorderRadius(th_header, 5, 5, 5, 5, 0, 0, 0, 0);
}

/**
 * Add an "s" or not to the given word, in case the given figure is greater than 1
 * @param {String} word the word to set
 * @param {Number} figure
 * @returns {String} the given word with the good spelling
 */
function getGoodSpelling(word, figure){
	if(figure == null)
		figure = 0;
	figure = parseFloat(figure);
	if(figure > 1 && typeof(word) == "string")
		word += "s";
	return word;
}

/**
 * Get the size of an object (number of attributes)
 * @param {Object} object
 * @returns {Number} size of the object
 */
function getObjectSize(object){
	s = 0;
	for(a in object){
		s++;
	}
	return s;
}

function waitFrameReady(win, test, onready, timeout) {
	if (typeof timeout == 'undefined') timeout = 30000;
	if (timeout < 50) return;
	if (!test(win)) { setTimeout(function() { waitFrameReady(win, test, onready, timeout-50); }, 50); return; }
	onready(win);
}

if (typeof window.top._current_tooltip == 'undefined')
	window.top._current_tooltip = null;
function createTooltip(element, content) {
	if (!content) return;
	if (typeof content == 'string') {
		var div = document.createElement("DIV");
		div.innerHTML = content;
		content = div;
	}
	content.style.position = "absolute";
	var x = absoluteLeft(element);
	var w = element.offsetWidth;
	var ww = getWindowWidth();
	if (x <= ww/2) {
		content.className = "tooltip";
		if (w < 44) {
			x = x-22+Math.floor(w/2);
			if (x < 0) x = 0;
		}
		content.style.left = x+"px";
	} else {
		content.className = "tooltip_right";
		x = (ww-(x+w));
		if (w < 44) {
			x = x-22+Math.floor(w/2);
			if (x >= ww) x = ww-1;
		}
		content.style.right = x+"px";
	}
	content.style.top = (absoluteTop(element)+element.offsetHeight)+"px";
	removeTooltip();
	content.style.visibility = 'hidden';
	setOpacity(content, 0);
	animation.fadeIn(content, 200);
	document.body.appendChild(content);
	element._tooltip = window.top._current_tooltip = content;
	content._element = element;
	element._tooltip_timeout = setTimeout(function (){
		if (window.top._current_tooltip && window.top._current_tooltip == element._tooltip)
			removeTooltip();
	},10000);
	element._listener = function() {
		if (window.top._current_tooltip && window.top._current_tooltip == element._tooltip)
			removeTooltip();
	};
	listenEvent(window,'mouseout',element._listener);
}
function removeTooltip() {
	if (!window.top._current_tooltip) return;
	if (window.top._current_tooltip.parentNode) {
		window.top._current_tooltip.parentNode.removeChild(window.top._current_tooltip);
	}
	unlistenEvent(getWindowFromDocument(window.top._current_tooltip._element.ownerDocument),'mouseout',window.top._current_tooltip._element._listener);
	window.top._current_tooltip._element._tooltip = null;
	window.top._current_tooltip = null;
}
function tooltip(element, content) {
	element.onmouseover = function() {
		createTooltip(element, content);
	};
	element.onmouseout = function() {
		if (this._tooltip && this._tooltip == window.top._current_tooltip)
			removeTooltip();
		this._tooltip = null;
	};
}