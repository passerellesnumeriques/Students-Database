/**
 * Some useful functions are added to the class String
 * @class String
 */
/** 
 * return true if this string starts with the given string
 * @memberOf String
 */
String.prototype.startsWith=function(s){return this.length<s.length?false:this.substring(0,s.length)==s;};
/** 
 * remove leading and trailing spaces, and return the result
 * @memberOf String
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

/** return true if the given parameter is a space character */
function isSpace(c) { return (c == ' ' || c == '\t' || c == '\r' || c == '\n'); }
/** return true if the given parameter is a letter (small or capital) */
function isLetter(c) {
	var ord = c.charCodeAt(0);
	if (ord >= 'a'.charCodeAt(0) && ord <= 'z'.charCodeAt(0)) return true;
	if (ord >= 'A'.charCodeAt(0) && ord <= 'Z'.charCodeAt(0)) return true;
	return false;
}

/**
* Set a uniform case according to a given separator
* @memberOf String
* @parameter {string} separator
* @returns the same string with a capitalized first letter
*/
String.prototype.firstLetterCapitalizedForSeparator = function(separator) {
	var text_split = this.split(separator);
	for(var i = 0; i < text_split.length; i++){
		var temp_split = text_split[i].split("");
		text_split[i] = text_split[i].charAt(0).toUpperCase()+text_split[i].substring(1);
	}
	return text_split.join(separator);
}

/**
* Set a uniform case according to " ", "'" and "-"
* @memberOf String
* @returns the same string with a capitalized first letter, and other lowered
*/
String.prototype.uniformFirstLetterCapitalized = function(){
	var text = this.toLowerCase();
	var result = text.firstLetterCapitalizedForSeparator(" ");
	result = result.firstLetterCapitalizedForSeparator("-");
	result = result.firstLetterCapitalizedForSeparator("'");
	return result;
}

/**
 * Test if a string is not empty (in terms of visibility)
 * @memberOf String
 * @returns true if the given string is not only made of space or is empty; else return false
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
}

/**
 * Some useful functions are added to the class Array
 * @class Array
 */
/** 
 * return true if this array contains the given element
 * @memberOf Array
 */
Array.prototype.contains=function(e){for(var i=0;i<this.length;++i)if(this[i]==e)return true;return false;};
/** 
 * remove all occurences of the given element from this array, if any.
 * @memberOf Array
 */
Array.prototype.remove=function(e){for(var i=0;i<this.length;++i)if(this[i]==e){this.splice(i,1);i--;};};

/**
 * Clone an object structure 
 * @param o the object to clone
 * @param recursive_depth maximum depth of clone
 * @returns {Object} the clone
 */
function object_copy(o, recursive_depth) {
	var c = new Object();
	for (var attr in o) {
		var value = o[attr];
		if (!recursive_depth) { c[attr] = value; continue; }
		if (typeof value == 'object') {
			if (value instanceof Date)
				c[attr] = new Date(value.getTime());
			else {
				c[attr] = object_copy(value, recursive_depth-1);
			}
		} else
			c[attr] = value;
	}
	return c;
}

var _generate_id_counter = 0;
/**
 * Generates an unique id. 
 * @returns {String} the generated id
 */
function generate_id() {
	return "id"+(_generate_id_counter++);
}

/**
 * Return the absolute position of the left edge, relative to the given element or to the document
 * @param e the element to get the absolute position
 * @param relative the element from which we want the absolute position, or null to get the position in the document
 */
function absoluteLeft(e,relative) {
	var left = e.offsetLeft;
	try { if (e.offsetParent && e.offsetParent != relative) left += absoluteLeft(e.offsetParent,relative); } catch (ex) {}
	return left;
}
/**
 * Return the absolute position of the top edge, relative to the given element or to the document
 * @param e the element to get the absolute position
 * @param relative the element from which we want the absolute position, or null to get the position in the document
 */
function absoluteTop(e,relative) {
	var top = e.offsetTop;
	try { if (e.offsetParent && e.offsetParent != relative) top += absoluteTop(e.offsetParent,relative); } catch (ex) {}
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
		this.protocol = window.location.protocol.substr(0,window.location.protocol.length-1);
		this.host = window.location.hostname;
		this.port = window.location.port;
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
	/**
	 * Trigger the event: call all listeners with the given data as parameter
	 * @param data
	 */
	this.fire = function(data) { for (var i = 0; i < this.listeners.length; ++i) this.listeners[i](data); };
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
	if (div) return;
	div = document.createElement('DIV');
	div.id = "lock_screen";
	div.style.backgroundColor = "#808080";
	setOpacity(div, 0.5);
	div.style.position = "fixed";
	div.style.top = "0px";
	div.style.left = "0px";
	div.style.width = getWindowWidth()+"px";
	div.style.height = getWindowHeight()+"px";
	if (onclick)
		div.onclick = onclick;
	if (content) {
		var table = document.createElement("TABLE"); div.appendChild(table);
		table.style.width = "100%";
		table.style.height = "100%";
		var tr = document.createElement("TR"); table.appendChild(tr);
		var td = document.createElement("TD"); tr.appendChild(td);
		td.style.verticalAlign = 'middle';
		td.style.textAlign = 'center';
		if (typeof content == 'string')
			td.innerHTML = content;
		else
			td.appendChild(content);
	}
	if (typeof animation != 'undefined')
		div.anim = animation.fadeIn(div,200,null,10,50);
	return document.body.appendChild(div);
}
/**
 * Remove the given element, previously created by using the function lock_screen
 * @param div
 */
function unlock_screen(div) {
	if (!div) div = document.getElementById('lock_screen');
	if (!div) return;
	if (typeof animation != 'undefined') {
		if (div.anim) animation.stop(div.anim);
		animation.fadeOut(div,200,function(){
			if (div.parentNode == document.body)
				document.body.removeChild(div);				
		},50,0);
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