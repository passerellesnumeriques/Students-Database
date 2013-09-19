/**
 * Object to detect which browser and version is used 
 */
browser = {
	agent_infos: [],
	/** version of Internet Explorer, or 0 */
	IE: 0,
	/** version of Chrome, or 0 */
	Chrome: 0,
	/** version of WebKit, or 0 */
	WebKit: 0,
	/** version of Safari-compatible, or 0 */
	Safari: 0,
	/** version of Safari, or 0 */
	SafariBrowser: 0,
	/** version of FireFox, or 0 */
	FireFox: 0,
	/** version of Opera-compatible, or 0 */
	Opera: 0, 
	/** version of Opera, or 0 */
	OperaBrowser: 0,
	/** version of Presto, or 0 */
	Presto: 0,
	Version: 0,
	detect: function() {
		var s = navigator.userAgent;
		do {
			var i = s.indexOf('/');
			if (i < 0) break;
			var name = s.substring(0, i).trim();
			s = s.substring(i+1);
			i = s.indexOf(' ');
			if (i < 0) i = s.length;
			var version = s.substring(0, i).trim();
			s = s.substring(i+1);
			var infos = [];
			if (s.length > 0 && s.charAt(0) == '(') {
				i = s.indexOf(')', 1);
				if (i > 0) {
					var ss = s.substring(1, i).trim();
					s = s.substring(i+1);
					while (ss.length > 0) {
						i = ss.indexOf(';');
						if (i < 0) i = ss.length;
						infos.push(ss.substring(0, i).trim());
						ss = ss.substring(i+1).trim();
					}
				}
			}
			this.agent_infos.push({
				name: name,
				version: version,
				infos: infos
			});
		} while (s.length > 0)
		this.fill();
	},
	fill: function() {
		for (var i = 0; i < this.agent_infos.length; ++i) {
			var a = this.agent_infos[i];
			switch (a.name.toLowerCase()) {
			case "mozilla":
				for (var j = 0; j < a.infos.length; ++j) {
					var s = a.infos[j];
					if (s.substr(0,5).toLowerCase() != "msie ") continue;
					this.IE = parseFloat(s.substring(5));
				}
				break;
			case "chrome": this.Chrome = parseFloat(a.version); break;
			case "applewebkit": this.WebKit = parseFloat(a.version); break;
			case "safari": this.Safari = parseFloat(a.version); break;
			case "firefox": this.FireFox = parseFloat(a.version); break;
			case "opera": this.Opera = parseFloat(a.version); break;
			case "presto": this.Presto = parseFloat(a.version); break;
			case "version": this.Version = parseFloat(a.version); break;
			}
		}
		if (this.Safari > 0 && this.Version > 0) this.SafariBrowser = this.Version;
		if (this.Opera > 0) { if (this.Version > 0) this.OperaBrowser = this.Version; else this.OperaBrowser = this.Opera; }
	}
};
browser.detect();

// needed, if utils not yet loaded...
if (!Array.prototype.contains)
	Array.prototype.contains=function(e){for(var i=0;i<this.length;++i)if(this[i]==e)return true;return false;};

/** @class document */

/** If document.getElementById does not exist, it is added  
 * @method document.getElementById
 */
if (typeof document.getElementById != "function")
	document.getElementById = function(id) { return document.all[id]; };
/** If document.getElementsByClassName does not exist, it is added  
 * @method document.getElementsByClassName
 */
if (typeof document.getElementsByClassName!='function') {
    document.getElementsByClassName = function() {
        var elms = document.getElementsByTagName('*');
        var ei = new Array();
        for (var i=0;i<elms.length;i++) {
            if (elms[i].getAttribute('class')) {
                ecl = elms[i].getAttribute('class').split(' ');
                for (var j=0;j<ecl.length;j++) {
                    if (ecl[j].toLowerCase() == arguments[0].toLowerCase()) {
                        ei.push(elms[i]);
                    }
                }
            } else if (elms[i].className) {
                ecl = elms[i].className.split(' ');
                for (var j=0;j<ecl.length;j++) {
                    if (ecl[j].toLowerCase() == arguments[0].toLowerCase()) {
                        ei.push(elms[i]);
                    }
                }
            }
        }
        return ei;
    };
}
/** return the document object of the given frame */
getIFrameDocument = function(frame) {
	if (frame.contentDocument) return frame.contentDocument;
	if (frame.document) return frame.document;
	return frame.contentWindow.document;
};
/** return the window object of the given frame */
getIFrameWindow = function(frame) {
	if (frame.contentWindow) return frame.contentWindow;
	return frame.contentDocument.window;
};

/** define it if this function is not available in the current browser
 * @method getComputedStyle
 */
if (typeof getComputedStyle == "undefined") {
	getComputedStyle = function(e,n) {
		return e.currentStyle;
	};
}
/** If this class is not available in the current browser, it creates it
 * @class XMLHttpRequest
 */
if (typeof XMLHttpRequest == "undefined")
	XMLHttpRequest = function () {
	    try { return new ActiveXObject("Msxml2.XMLHTTP.6.0"); }
	      catch (e) {}
	    try { return new ActiveXObject("Msxml2.XMLHTTP.3.0"); }
	      catch (e) {}
	    try { return new ActiveXObject("Microsoft.XMLHTTP"); }
	      catch (e) {}
	    //Microsoft.XMLHTTP points to Msxml2.XMLHTTP and is redundant
	    throw new Error("This browser does not support XMLHttpRequest.");
	};

/**
 * Set the opacity of an element (if the browser has a way to support it)
 * @param element
 * @param opacity from 0 to 1
 */
function setOpacity(element, opacity) {
	element.style.opacity = opacity;
	element.style.MozOpacity = opacity;
	element.style.KhtmlOpacity = opacity;
	opacity = Math.round(opacity*100);
	element.style.filter = "alpha(opacity="+opacity+");";
	element.style.MsFilter = "progid:DXImageTransform.Microsoft.Alpha(Opacity="+opacity+")";	
}
/**
 * Set box-shadow if the browser has a way to support it 
 * @param elem
 * @param a
 * @param b
 * @param c
 * @param d
 * @param color
 */
function setBoxShadow(elem,a,b,c,d,color) { 
	elem.style.boxShadow = a+"px "+b+"px "+c+"px "+d+"px "+color;
	elem.style.MozBoxShadow = a+"px "+b+"px "+c+"px "+d+"px "+color;
	elem.style.WebkitBoxShadow = a+"px "+b+"px "+c+"px "+d+"px "+color;
}
/**
 * Set a border radius if the browser has a way to support it
 * @param elem
 * @param topleft_width
 * @param topleft_height
 * @param topright_width
 * @param topright_height
 * @param bottomleft_width
 * @param bottomleft_height
 * @param bottomright_width
 * @param bottomright_height
 */
function setBorderRadius(elem, 
		topleft_width, topleft_height, 
		topright_width, topright_height, 
		bottomleft_width, bottomleft_height, 
		bottomright_width, bottomright_height
		) {
	elem.style.borderTopLeftRadius = topleft_width+"px "+topleft_height+"px"; 
	elem.style.borderTopRightRadius = topright_width+"px "+topright_height+"px"; 
	elem.style.borderBottomLeftRadius = bottomleft_width+"px "+bottomleft_height+"px"; 
	elem.style.borderBottomRightRadius = bottomright_width+"px "+bottomright_height+"px"; 
	elem.style.MozBorderRadiusTopleft = topleft_width+"px "+topleft_height+"px"; 
	elem.style.MozBorderRadiusTopright = topright_width+"px "+topright_height+"px"; 
	elem.style.MozBorderRadiusBottomleft = bottomleft_width+"px "+bottomleft_height+"px"; 
	elem.style.MozBorderRadiusBottomright = bottomright_width+"px "+bottomright_height+"px"; 
	elem.style.WebkitBorderTopLeftRadius = topleft_width+"px "+topleft_height+"px"; 
	elem.style.WebkitBorderTopRightRadius = topright_width+"px "+topright_height+"px"; 
	elem.style.WebkitBorderBottomLeftRadius = bottomleft_width+"px "+bottomleft_height+"px"; 
	elem.style.WebkitBorderBottomRightRadius = bottomright_width+"px "+bottomright_height+"px"; 
}
/**
 * Set a background gradient if the browser has a way to support it
 * @param element
 * @param orientation one of: horizontal, vertical, diagonal-topleft, diagonal-bottomleft, radial
 * @param stops list of objects with 2 attributes: <code>pos</code> between 0 and 100, and <code>color</code> the string defining the color
 */
function setBackgroundGradient(element, orientation, stops) {
	var start_pos;
	switch (orientation) {
	case "horizontal": start_pos = "left"; break;
	case "vertical": start_pos = "top"; break;
	case "diagonal-topleft": start_pos = "-45deg"; break;
	case "diagonal-bottomleft": start_pos = "45deg"; break;
	case "radial": start_pos = "center"; break;
	}
	if (browser.IE >= 6 && browser.IE <= 9) {
		var gt = orientation == "vertical" ? 0 : 1; // fallback to horizontal if diagonal or radial
		element.style.filter = "progid:DXImageTransform.Microsoft.gradient(startColorstr='"+stops[0].color+"',endColorstr='"+stops[stops.length-1].color+"',GradientType="+gt+")";
	} else if (browser.IE >= 10) {
		var b = "-ms-"+(orientation == "radial" ? "radial" : "linear")+"-gradient("+start_pos;
		for (var i = 0; i < stops.length; ++i)
			b += ","+stops[i].color+" "+stops[i].pos+"%";
		b += ")";
		element.style.background = b;
	} else if (browser.Chrome >= 10 || browser.SafariBrowser >= 5.1) {
		var b = "-webkit-"+(orientation == "radial" ? "radial" : "linear")+"-gradient("+start_pos;
		for (var i = 0; i < stops.length; ++i)
			b += ","+stops[i].color+" "+stops[i].pos+"%";
		b += ")";
		element.style.background = b;
	} else if (browser.Chrome > 0 || browser.SafariBrowser >= 4) {
		if (orientation == "radial") {
			var b = "-webkit-gradient(radial, center center, 0px, center center, 100%";
			for (var i = 0; i < stops.length; ++i)
				b += ",color-stop("+stops[i].pos+"%,"+stops[i].color+")";
			b += ")";
			element.style.background = b;
		} else {
			var b = "-webkit-gradient(linear,";
			switch (orientation) {
			case "horizontal": b += "left top, right top"; break;
			case "vertical": b += "left top, left bottom"; break;
			case "diagonal-topleft": b += "left top, right bottom"; break;
			case "diagonal-bottomleft": b += "left bottom, right top"; break;
			}
			for (var i = 0; i < stops.length; ++i)
				b += ",color-stop("+stops[i].pos+"%,"+stops[i].color+")";
			b += ")";
			element.style.background = b;
		}
	} else if (browser.FireFox >= 3.6) {
		var b;
		if (orientation == "radial")
			b = "-moz-radial-gradient(center, ellipse cover";
		else
			b = "-moz-linear-gradient("+start_pos;
		for (var i = 0; i < stops.length; ++i)
			b += ","+stops[i].color+" "+stops[i].pos+"%";
		b += ")";
		element.style.background = b;
	} else if (browser.Opera >= 10) {
		var b;
		if (orientation == "radial")
			b = "-o-radial-gradient(center, ellipse cover";
		else
			b = "-o-linear-gradient("+start_pos;
		for (var i = 0; i < stops.length; ++i)
			b += ","+stops[i].color+" "+stops[i].pos+"%";
		b += ")";
		element.style.background = b;
	} else {
		// default
		element.style.background = stops[0].color;
	}
	// TODO W3C ???
}
	
/**
 * Return an object that will contain the same information whatever browser is used: {x,y,button}
 */
getCompatibleMouseEvent = function(e) {
	ev = {};
	if (browser.IE == 0 || browser.IE >= 9) { ev.x = e.clientX; ev.y = e.clientY; }
	else { ev.x = window.event.clientX+document.documentElement.scrollLeft; ev.y = window.event.clientY+document.documentElement.scrollTop; }
	if (browser.IE == 0) ev.button = e.button;
	else switch (window.event.button) { case 1: ev.button = 0; break; case 4: ev.button = 1; break; case 2: ev.button = 2; break; } 
	return ev;
};
/**
 * Return a key event, whatever browser is used
 */
getCompatibleKeyEvent = function(e) {
	if (browser.IE == 0 || browser.IE >= 9) return e;
	return window.event;
};

/** Return the height of the window in pixels
 * @method getWindowHeight
 */
/** Return the width of the window in pixels
 * @method getWindowWidth
 */
if (!browser.IE >= 9) {
	getWindowHeight = function() { return window.innerHeight; };
	getWindowWidth = function() { return window.innerWidth; };
} else if (browser.IE >= 7) {
	getWindowHeight = function() { return document.documentElement.scrollHeight; };
	getWindowWidth = function() { return document.documentElement.scrollWidth; };
} else {
	getWindowHeight = function() { return document.body.clientHeight; };
	getWindowWidth = function() { return document.body.clientWidth; };
}
/** Stop propagation of the given/current event
 * @method stopEventPropagation
 * @param evt
 */
if (browser.IE == 0) {
	stopEventPropagation = function(evt) {
		evt.stopPropagation();
		evt.preventDefault();
		return false;
	};
} else {
	stopEventPropagation = function(evt) {
		window.event.cancelBubble = true;
		window.event.returnValue = false;
		return false;
	};
}

/**
 * Attach a listener to the given event type on the given element
 * @param elem the HTML element
 * @param type the type of event ('click' for onclick, 'mousedown', 'mousemove'...)
 * @param handler the listener to be called when the event occur
 */
function listenEvent(elem, type, handler) {
	if (elem == window && !document.createEvent) elem = document;
	if (elem.addEventListener)
	     elem.addEventListener(type,handler,false);
	 else if (elem.attachEvent)
	     elem.attachEvent('on'+type,handler); 
}
/**
 * Detach a listener
 * @param elem
 * @param type
 * @param handler
 */
function unlistenEvent(elem, type, handler) {
	if (elem == window && !document.createEvent) elem = document;
	if (elem.removeEventListener)
		elem.removeEventListener(type,handler,false);
	else
	     elem.detachEvent('on'+type,handler); 
}
/**
 * Trigger an event
 * @param elem
 * @param type
 * @param attributes
 */
function triggerEvent(elem, type, attributes) {
	var event;
	if (document.createEvent) {
		event = document.createEvent("HTMLEvents");
		event.initEvent(type, true, true);
	} else {
		event = document.createEventObject();
		event.eventType = type;
	}
	event.eventName = type;
	if (attributes) for (var attr in attributes) event[attr] = attributes[attr];
	if (document.createEvent) {
		elem.dispatchEvent(event);
	} else {
		if (elem == window) elem = document;
		elem.fireEvent("on" + type, event);
	}
}

var _scripts_loaded = [];
/**
 * Dynamically load a javascript into the page. If it was already loaded, it will not load it again, but will call <code>onload</code> immediately
 * @param {String} url
 * @param {function} onload called once the javascript is loaded
 */
function add_javascript(url, onload) {
	var p = new URL(url).path;
	if (_scripts_loaded.contains(p)) {
		if (onload) onload();
		return;
	}
	if (document.readyState != "complete") {
		// delay the load, as we may not have yet all the scripts in the head
		setTimeout(function(){add_javascript(url,onload);},1);
		return;
	}
	var head = document.getElementsByTagName("HEAD")[0];
	for (var i = 0; i < head.childNodes.length; ++i) {
		var e = head.childNodes[i];
		if (e.nodeName != "SCRIPT") continue;
		if (!e.src || e.src.length == 0) continue;
		var u = new URL(e.src);
		if (u.path == p) {
			// we found a script there
			if (e.data) {
				if (onload)
					e.data.add_listener(onload);
				return;
			}
			// didn't use this way...
			if (e._loaded) {
				// but marked as already loaded
				_scripts_loaded.push(p);
				if (onload) onload();
				return;
			}
			e.data = new Custom_Event();
			if (onload) e.data.add_listener(onload);
			if (e.onload) e.data.add_listener(e.onload);
			e.onload = function() { _scripts_loaded.push(p); this.data.fire(); };
			return;
		}
	}
	// this is a new script
	var s = document.createElement("SCRIPT");
	s.data = new Custom_Event();
	if (onload) s.data.add_listener(onload);
	s.type = "text/javascript";
	s.onload = function() { _scripts_loaded.push(p); this._loaded = true; this.data.fire(); };
	s.onreadystatechange = function() { if (this.readyState == 'loaded' || this.readyState == 'complete') { _scripts_loaded.push(p); this._loaded = true; this.data.fire(); this.onreadystatechange = null; } };
	head.appendChild(s);
	s.src = new URL(url).toString();
}
/**
 * Indicate a javascript is already loaded. This is automatically called by add_javascript, but may be useful in case some scripts are loaded in a different way
 */
function javascript_loaded(url) {
	url = new URL(url);
	if (!_scripts_loaded.contains(url.path))
		_scripts_loaded.push(url.path);
}

/**
 * Dynamically load a stylesheet in the page.
 */
function add_stylesheet(url) {
	if (typeof url == 'string') url = new URL(url);
	if (document.readyState != "complete") {
		// delay the load, as we may not have yet all the css in the head
		setTimeout(function(){add_stylesheet(url);},1);
		return;
	}
	var head = document.getElementsByTagName("HEAD")[0];
	for (var i = 0; i < head.childNodes.length; ++i) {
		var e = head.childNodes[i];
		if (e.nodeName != "LINK") continue;
		if (!e.href || e.href.length == 0) continue;
		var u = new URL(e.href);
		if (u.path == url.path) {
			// we found it
			return;
		}
	}
	var s = document.createElement("LINK");
	s.rel = "stylesheet";
	s.type = "text/css";
	s.href = url.toString();
	s.onload = function() { triggerEvent(window,'resize'); };
	document.getElementsByTagName("HEAD")[0].appendChild(s);
}

/**
 * Return the URL of the script ending by the given filename, or null if it cannot be found
 * @param script_filename
 */
function get_script_path(script_filename) {
	var head = document.getElementsByTagName("HEAD")[0];
	for (var i = 0; i < head.childNodes.length; ++i) {
		var e = head.childNodes[i];
		if (e.nodeName != "SCRIPT") continue;
		if (!e.src || e.src.length == 0) continue;
		var u = new URL(e.src);
		if (!u.path) continue;
		if (u.path.length > script_filename.length && u.path.substring(u.path.length-script_filename.length) == script_filename) {
			u.path = u.path.substring(0, u.path.length-script_filename.length);
			return u.toString();
		}
	}
	return null;
}
