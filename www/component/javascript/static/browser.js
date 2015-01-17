/**
 * Object to detect which browser and version is used 
 */
window.browser = {
	/** information from the UserAgent */
	agent_infos: [],
	/** version of Internet Explorer, or 0 
	 * @no_name_check */
	IE: 0,
	/** version of Chrome, or 0 
	 * @no_name_check */
	Chrome: 0,
	/** version of WebKit, or 0 
	 * @no_name_check */
	WebKit: 0,
	/** version of Safari-compatible, or 0 
	 * @no_name_check */
	Safari: 0,
	/** version of Safari, or 0 
	 * @no_name_check */
	SafariBrowser: 0,
	/** version of FireFox, or 0 
	 * @no_name_check */
	FireFox: 0,
	/** version of Opera-compatible, or 0 
	 * @no_name_check */
	Opera: 0, 
	/** version of Opera, or 0 
	 * @no_name_check */
	OperaBrowser: 0,
	/** version of Presto, or 0 
	 * @no_name_check */
	Presto: 0,
	/** version 
	 * @no_name_check */
	Version: 0,
	/** Detect the navigator type and version */
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
		this._fill();
	},
	/** Fill navigator type and version from agent infos */
	_fill: function() {
		var rv = null;
		var hasNav = false;
		for (var i = 0; i < this.agent_infos.length; ++i) {
			var a = this.agent_infos[i];
			switch (a.name.toLowerCase()) {
			case "mozilla":
				for (var j = 0; j < a.infos.length; ++j) {
					var s = a.infos[j];
					if (s.substr(0,3) == "rv:") {
						rv = parseFloat(s.substring(3));
						continue;
					}
					if (s.substr(0,5).toLowerCase() != "msie ") continue;
					this.IE = parseFloat(s.substring(5));
					hasNav = true;
				}
				break;
			case "chrome": this.Chrome = parseFloat(a.version); hasNav = true; break;
			case "applewebkit": this.WebKit = parseFloat(a.version); hasNav = true; break;
			case "safari": this.Safari = parseFloat(a.version); hasNav = true; break;
			case "firefox": this.FireFox = parseFloat(a.version); hasNav = true; break;
			case "opera": this.Opera = parseFloat(a.version); hasNav = true; break;
			case "presto": this.Presto = parseFloat(a.version); hasNav = true; break;
			case "version": this.Version = parseFloat(a.version); hasNav = true; break;
			}
		}
		if (this.Safari > 0 && this.Version > 0) this.SafariBrowser = this.Version;
		if (this.Opera > 0) { if (this.Version > 0) this.OperaBrowser = this.Version; else this.OperaBrowser = this.Opera; }
		if (!hasNav && rv != null) {
			var isIE = false;
			for (var name in window)
				if (name.substring(0,2) == "ms") { isIE = true; break; }
			if (isIE) this.IE = rv;
		}
	}
};
browser.detect();

// needed, if utils not yet loaded...
if (!Array.prototype.contains)
	Array.prototype.contains=function(e){for(var i=0;i<this.length;++i)if(this[i]==e)return true;return false;};

/** @class document */

if (typeof document.getElementById != "function")
	/** If document.getElementById does not exist, it is added 
	 * @param {String} id the id
	 * @returns {Element} the element */
	document.getElementById = function(id) { return document.all[id]; };
if (typeof document.getElementsByClassName!='function') {
	/** If document.getElementsByClassName does not exist, it is added */
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
if (typeof Element.prototype.getElementsByClassName!='function') {
	/** If document.getElementsByClassName does not exist, it is added */
    Element.prototype.getElementsByClassName = function() {
        var elms = this.getElementsByTagName('*');
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
/** Return the document object of the given frame
 * @param {Element} frame iframe
 * @returns {document} the document of the iframe 
 */
window.getIFrameDocument = function(frame) {
	if (frame.contentDocument) return frame.contentDocument;
	if (frame.document) return frame.document;
	return frame.contentWindow.document;
};
/** return the window object of the given frame 
 * @param {Element} frame iframe
 * @returns {window} the window of the iframe
 */
window.getIFrameWindow = function(frame) {
	if (frame.contentWindow) return frame.contentWindow;
	if (!frame.contentDocument) return null;
	return frame.contentDocument.window;
};

/** Return the window from the given document
 * @param {Document} doc the document
 * @returns {Window} the window containing the given document
 */
window.getWindowFromDocument = function(doc) {
	if (browser.IE > 0 && browser.IE <= 8)
		return doc.parentWindow;
	return doc.defaultView;
};

/** Return the window containing the given element
 * @param {Element} e the element
 * @returns {Window} its window, or null if it is not in the DOM
 */
window.getWindowFromElement = function(e) {
	if (!e) return null;
	//while (e.offsetParent) e = e.offsetParent;
	//while (e.parentNode && e.parentNode.nodeName != 'BODY' && e.parentNode.nodeName != 'HTML') e = e.parentNode;
	return getWindowFromDocument(e.ownerDocument);
};

if (typeof getComputedStyle == "undefined") {
	/** Defined it if this function is not available in the current browser
	 * @param {Element} e the element
	 * @param {String} n pseudo element
	 * */
	getComputedStyle = function(e,n) {
		return e.currentStyle;
	};
}
/** If this class is not available in the current browser, it creates it
 */
if (typeof XMLHttpRequest == "undefined")
	/** For IE browser, if it does not exist, we add it
	 * @no_name_check */
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
/** HTTP status code returned by the browser in case of timeout
 * @no_name_check */
window.HTTP_Status_Timeout = browser.IE > 0 ? 12031 : 0;
/** HTTP status code returned by the browser in case of connection problem 
 * @no_name_check */
window.HTTP_Status_ConnectionLost = browser.IE > 0 ? 12029 : 0;
	
/**
 * Set the opacity of an element (if the browser has a way to support it)
 * @param {Element} element the element
 * @param {Number} opacity from 0 to 1
 */
function setOpacity(element, opacity) {
	if (browser.IE > 0 && browser.IE < 8) {
		opacity = Math.round(opacity*100);
		element.style.filter = "alpha(opacity="+opacity+");";
	} else if (browser.IE >= 8 && browser.IE < 9) {
		opacity = Math.round(opacity*100);
		element.style.MsFilter = "progid:DXImageTransform.Microsoft.Alpha(Opacity="+opacity+")";
	} else {
		var o = new Number(opacity).toFixed(2);
		if (browser.FireFox > 0 && browser.FireFox < 0.9)
			element.style.MozOpacity = o;
		else if (browser.SafariBrowser > 0 && browser.SafariBrowser < 2)
			element.style.KhtmlOpacity = o;
		else
			element.style.opacity = o;
	}	
}
/**
 * Retrieve the opacity of an element
 * @param {Element} element the element
 * @returns {Number} the opacity between 0 and 1
 */
function getOpacity(element) {
	if (typeof element.style == 'undefined') return 1;
	if (typeof element.style.opacity != 'undefined') return parseFloat(element.style.opacity);
	if (typeof element.style.MozOpacity != 'undefined') return parseFloat(element.style.MozOpacity);
	if (typeof element.style.KhtmlOpacity != 'undefined') return parseFloat(element.style.KhtmlOpacity);
	return 1;
}
/**
 * Set box-shadow if the browser has a way to support it 
 * @param {Element} elem the HTML element
 * @param {Number} a horizontal shadow
 * @param {Number} b vertical shadow
 * @param {Number} c blur distance
 * @param {Number} d size of shadow
 * @param {String} color color string
 * @param {Boolean} inset if inset
 */
function setBoxShadow(elem,a,b,c,d,color,inset) { 
	elem.style.boxShadow = a+"px "+b+"px "+c+"px "+d+"px "+color+(inset ? " inset" : "");
	elem.style.MozBoxShadow = a+"px "+b+"px "+c+"px "+d+"px "+color+(inset ? " inset" : "");
	elem.style.WebkitBoxShadow = a+"px "+b+"px "+c+"px "+d+"px "+color+(inset ? " inset" : "");
}
/**
 * Set a border radius if the browser has a way to support it
 * @param {Element} elem the HTML element
 * @param {Number} topleft_width in pixels
 * @param {Number} topleft_height in pixels
 * @param {Number} topright_width in pixels
 * @param {Number} topright_height in pixels
 * @param {Number} bottomleft_width in pixels
 * @param {Number} bottomleft_height in pixels
 * @param {Number} bottomright_width in pixels
 * @param {Number} bottomright_height in pixels
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
 * Retrieve the border radius style of the given element
 * @param {Element} elem the element
 * @returns {Array} [topleft,topright,bottomleft,bottomright]
 */
function getBorderRadius(elem) {
	var style = getComputedStyle(elem);
	var getValue = function(name) {
		if (typeof style[name] == 'undefined') return 0;
		if (style[name] == "") return 0;
		return parseInt(style[name]);
	};
	var getFinalValue = function(names) {
		for (var i = 0; i < names.length; ++i) {
			var value = getValue(names[i]);
			if (value != 0) return value;
		}
		return 0;
	};
	return [
		getFinalValue(["borderTopLeftRadius", "MozBorderRadius-Tpleft", "WebkitBorderTopLeftRadius"]),
		getFinalValue(["borderTopRightRadius", "MozBorderRadiusTopright", "WebkitBorderTopRightRadius"]),
		getFinalValue(["borderBottomLeftRadius", "MozBorderRadiusBottomleft", "WebkitBorderBottomRightRadius"]),
		getFinalValue(["borderBottomRightRadius", "MozBorderRadiusBottomright", "WebkitBorderBottomRightRadius"])
	];
}
/**
 * Set a background gradient if the browser has a way to support it
 * @param {Element} element the HTML element
 * @param {String} orientation one of: horizontal, vertical, diagonal-topleft, diagonal-bottomleft, radial
 * @param {Array} stops list of objects with 2 attributes: <code>pos</code> between 0 and 100, and <code>color</code> the string defining the color
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
 * Rotate an HTML element
 * @param {Element} element the HTML element
 * @param {Number} degres degres of rotation
 */
function setRotation(element, degres) {
	element.style.transform = "rotate("+degres+"deg)";
	element.style.MsTransform = "rotate("+degres+"deg)";
	element.style.WebkitTransform = "rotate("+degres+"deg)";
}
	
/**
 * Return an object that will contain the same information whatever browser is used: {x,y,button}
 * @param {Event} e the event
 * @returns {Object} {x,y,button}
 */
function getCompatibleMouseEvent(e) {
	var ev = {};
	if (browser.IE == 0 || browser.IE >= 9) { ev.x = e.clientX; ev.y = e.clientY; }
	else { ev.x = window.event.clientX+document.documentElement.scrollLeft; ev.y = window.event.clientY+document.documentElement.scrollTop; }
	if (browser.IE == 0 || browser.IE >= 11) ev.button = e.button;
	else switch (window.event.button) { case 1: ev.button = 0; break; case 4: ev.button = 1; break; case 2: ev.button = 2; break; } 
	return ev;
}
/**
 * Return a key event, whatever browser is used.
 * @param {Event} e the event
 * @returns {Object} information about the event
 */
function getCompatibleKeyEvent(e) {
	// source: http://www.javascripter.net/faq/keycodes.htm
	var ev = browser.IE == 0 || browser.IE >= 9 ? e : window.event;
	if (!ev.keyCode) ev.keyCode = ev.charCode;
	var done = true;
	ev.isPrintable = false;
	switch (ev.keyCode) {
	case 8: ev.isBackspace = true; break;
	case 9: ev.isTab = true; break;
	case 13: ev.isEnter = true; break;
	case 16: ev.isShift = true; break;
	case 17: ev.isCtrl = true; break;
	case 18: ev.isAlt = true; break;
	case 27: ev.isEscape = true; break;
	case 32: ev.isSpace = true; ev.isPrintable = true; ev.printableChar = " "; break;
	case 33: ev.isPageUp = true; break;
	case 34: ev.isPageDown = true; break;
	case 35: ev.isEnd = true; break;
	case 36: ev.isHome = true; break;
	case 37: ev.isArrowLeft = true; break;
	case 38: ev.isArrowUp = true; break;
	case 39: ev.isArrowRight = true; break;
	case 40: ev.isArrowDown = true; break;
	case 46: ev.isDelete = true; break;
	default: done = false; break;
	}
	if (!done) {
		if (ev.keyCode >= 48 && ev.keyCode <= 57) { ev.isPrintable = true; ev.printableChar = String.fromCharCode(ev.keyCode); }
		else if (ev.keyCode >= 65 && ev.keyCode <= 90) { ev.isPrintable = true; ev.printableChar = String.fromCharCode(ev.keyCode); }
		else {
			switch (ev.keyCode) {
			case 188: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "<" : ","; break;
			case 190: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? ">" : "."; break;
			case 191: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "?" : "/"; break;
			case 192: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "~" : "`"; break;
			case 219: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "{" : "["; break;
			case 220: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "\\" : "|"; break;
			case 221: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "}" : "]"; break;
			case 222: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "'" : "\""; break;
			default:
				if (browser.OperaBrowser > 0) {
					if (ev.keyCode >= 48 && ev.keyCode <= 57) { ev.isPrintable = true; ev.printableChar = String.fromCharCode(ev.keyCode); }
					switch (ev.keyCode) {
					case 42: ev.printableChar = true; ev.printableChar = "*"; break;
					case 43: ev.printableChar = true; ev.printableChar = "+"; break;
					case 45: ev.printableChar = true; ev.printableChar = "-"; break;
					case 78: ev.printableChar = true; ev.printableChar = "."; break;
					case 47: ev.printableChar = true; ev.printableChar = "/"; break;
					case 59: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? ":" : ";"; break;
					case 61: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "+" : "="; break;
					case 109: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "_" : "-"; break;
					}
				} else {
					// numpad numbers
					if (ev.keyCode >= 96 && ev.keyCode <= 105) { ev.isPrintable = true; ev.printableChar = String.fromCharCode(ev.keyCode-(96-48)); }
					switch (ev.keyCode) {
					case 106: ev.printableChar = true; ev.printableChar = "*"; break;
					case 107: ev.printableChar = true; ev.printableChar = "+"; break;
					case 109: ev.printableChar = true; ev.printableChar = "-"; break;
					case 110: ev.printableChar = true; ev.printableChar = "."; break;
					case 111: ev.printableChar = true; ev.printableChar = "/"; break;
					default:
						if (browser.SafariBrowser > 0) {
							switch (ev.keyCode) {
							case 59: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? ":" : ";"; break;
							case 61: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "+" : "="; break;
							case 109: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "_" : "-"; break;
							}
						} else {
							switch (ev.keyCode) {
							case 186: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? ":" : ";"; break;
							case 187: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "+" : "="; break;
							case 189: ev.isPrintable = true; ev.printableChar = ev.shiftKey ? "_" : "-"; break;
							}
						}
					}
				}
			}
		}
	}
	return ev;
};

if (!browser.IE >= 9) {
	/** Get the height of the window
	 * @returns {Number} the height of the window
	 */
	window.getWindowHeight = function() { return window.innerHeight; };
	/** Get the width of the window
	 * @returns {Number} the width of the window
	 */
	window.getWindowWidth = function() { return window.innerWidth; };
} else if (browser.IE >= 7) {
	window.getWindowHeight = function() { return document.documentElement.scrollHeight; };
	window.getWindowWidth = function() { return document.documentElement.scrollWidth; };
} else {
	window.getWindowHeight = function() { return document.body.clientHeight; };
	window.getWindowWidth = function() { return document.body.clientWidth; };
}

if (browser.IE == 0) {
	/**
	 * Stop the propagation of an event to the hierarchy of DOM (cancelBubble for IE, stopPropagation on others)
	 * @param {Event} evt the event
	 * @param {Boolean} do_not_prevent_default if true, we won't prevent the default action of the browser on the current element
	 * @returns {Boolean} return false
	 */
	window.stopEventPropagation = function(evt, do_not_prevent_default) {
		evt.stopPropagation();
		if (!do_not_prevent_default)
			evt.preventDefault();
		return false;
	};
} else {
	window.stopEventPropagation = function(evt, do_not_prevent_default) {
		window.event.cancelBubble = true;
		window.event.returnValue = do_not_prevent_default ? true : false;
		return false;
	};
}

/**
 * Get the class name for the given object
 * @param {Object} obj the object we want to know its class
 * @returns {String} the class of the object
 */
function getObjectClassName(obj) {
	if (obj == null) return "null";
	if (obj == undefined) return "undefined";
	if (typeof obj.constructor != 'undefined') return getFunctionName(obj.constructor);
	if (typeof obj.__proto__ != 'undefined') {
		if (typeof obj.__proto__.constructor != 'undefined') return getFunctionName(obj.__proto__.constructor);
		return getObjectClassName(obj.__proto__);
	}
	return "Object";
}
/**
 * Return the name of the given function
 * @param {Function} f the function
 * @returns {String} its name
 */
function getFunctionName(f) {
	if (typeof f.name != 'undefined') return f.name;
	var s = f.toString();
	var i = s.indexOf(' ');
	s = s.substring(i+1);
	i = s.indexOf('(');
	return s.substring(0,i);
}

/**
 * Attach a listener to the given event type on the given element
 * @param {Element} elem the HTML element
 * @param {String} type the type of event ('click' for onclick, 'mousedown', 'mousemove'...)
 * @param {Function} handler the listener to be called when the event occur
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
 * @param {Element} elem the HTML element
 * @param {String} type the type of event ('click' for onclick, 'mousedown', 'mousemove'...)
 * @param {Function} handler the listener to be removed
 */
function unlistenEvent(elem, type, handler) {
	if (!elem) return;
	if (elem == window && !document.createEvent) elem = document;
	if (elem.removeEventListener)
		elem.removeEventListener(type,handler,false);
	else if (elem.detachEvent)
	     elem.detachEvent('on'+type,handler); 
}
/**
 * Create an event
 * @param {String} type the type of event ('click' for onclick, 'mousedown', 'mousemove'...)
 * @param {Object} attributes attributes to put on the event
 * @returns {Event} the event
 */
function createEvent(type, attributes) {
	var evt;
	if (document.createEvent) {
		evt = document.createEvent("HTMLEvents");
		evt.initEvent(type, true, true);
	} else {
		evt = document.createEventObject();
		evt.eventType = type;
		evt.type = type;
	}
	evt.eventName = type;
	if (attributes) for (var attr in attributes) evt[attr] = attributes[attr];
	return evt;
}
/**
 * Trigger an event (create and fire)
 * @param {Element} elem the HTML element
 * @param {String} type the type of event ('click' for onclick, 'mousedown', 'mousemove'...)
 * @param {Object} attributes attributes to set in the event
 */
function triggerEvent(elem, type, attributes) {
	var evt = createEvent(type, attributes);
	fireEvent(elem, type, evt);
}

/**
 * Fire a DOM event
 * @param {Element} elem the element on which to fire the event
 * @param {String} type type of event (i.e. mousemove, click, resize...)
 * @param {Event} evt the event to fire
 */
function fireEvent(elem, type, evt) {
	if (document.createEvent) {
		elem.dispatchEvent(evt);
	} else {
		if (elem == window) elem = document;
		if (elem == document) elem = document.documentElement;
		elem.fireEvent("on" + type, evt);
	}
}

/** List of javascripts already loaded */
var _scripts_loaded = {};
/** List of javascript currently loading */
var _scripts_loading = {};
/**
 * Dynamically load a javascript into the page. If it was already loaded, it will not load it again, but will call <code>onload</code> immediately
 * @param {String} url URL of the JavaScript file to load
 * @param {Object} additional_attributes to be put on the script node
 * @param {Function} onload called once the javascript is loaded
 * @returns {Element} the SCRIPT node
 */
function addJavascript(url, onload, additional_attributes) {
	if (typeof url == 'string') url = new URL(url);
	var p = url.toString();
	if (typeof _scripts_loaded[p] != 'undefined') {
		if (onload) onload();
		return _scripts_loaded[p];
	}
	if (typeof _scripts_loading[p] != 'undefined') {
		if (onload) _scripts_loading[p].data.addListener(onload);
		return _scripts_loading[p];
	}
	if (document.readyState != "complete") {
		// delay the load, as we may not have yet all the scripts in the head
		var listener = function() {
			if (typeof addJavascript != 'function') return; // page closed
			addJavascript(url,onload,additional_attributes);
			unlistenEvent(document, 'readystatechange', listener);
			listener = null;
		};
		listenEvent(document, 'readystatechange',listener);
		return null;
	}
	var head = document.getElementsByTagName("HEAD")[0];
	for (var i = 0; i < head.childNodes.length; ++i) {
		var e = head.childNodes[i];
		if (e.nodeName != "SCRIPT") continue;
		if (!e.src || e.src.length == 0) continue;
		var eu = new URL(e.src).toString();
		if (eu == p) {
			// we found a script there
			if (e.data) {
				// already using data ?? should be in the _scripts_loading...
				_scripts_loading[p] = e;
				if (onload)
					e.data.addListener(onload);
				return e;
			}
			// didn't use this way...
			if (e._loaded) {
				// but marked as already loaded
				_scripts_loaded[p] = e;
				if (onload) onload();
				return e;
			}
			e.data = new Custom_Event();
			_scripts_loading[p] = e;
			if (onload) e.data.addListener(onload);
			if (e.onload) e.data.addListener(e.onload);
			e.onload = function() { if (_scripts_loading) delete _scripts_loading[p]; if (_scripts_loaded) _scripts_loaded[p]=e; this.data.fire(); this.data.cleanup(); this.data = null; };
			return e;
		}
	}
	// this is a new script
	var s = document.createElement("SCRIPT");
	if (additional_attributes)
		for (var name in additional_attributes)
			s[name] = additional_attributes[name];
	s.data = new Custom_Event();
	if (onload) s.data.addListener(onload);
	s.type = "text/javascript";
	s.onload = function() { if (_scripts_loading) delete _scripts_loading[p]; if (_scripts_loaded) _scripts_loaded[p]=s; this._loaded = true; s.data.fire(); s.data.cleanup(); s.data = null; s.onload = null; s.onreadystatechange = null; };
	//s.onerror = function(ev) { alert("Error loading javascript file: "+this.src); for (var name in ev) alert("Event: "+name+"="+ev[name]); };
	s.onreadystatechange = function() { if (this.readyState == 'loaded') { if (_scripts_loading) delete _scripts_loading[p]; if (_scripts_loaded) _scripts_loaded[p]=s; this._loaded = true; s.data.fire(); s.data.cleanup(); s.data = null; this.onreadystatechange = null; s.onload = null; } };
	head.appendChild(s);
	s.src = p;
	return s;
}
/**
 * Indicate a javascript is already loaded. This is automatically called by addJavascript, but may be useful in case some scripts are loaded in a different way
 * @param {Element} node the SCRIPT node
 */
function javascriptLoaded(node) {
	var url = new URL(node.src).toString();
	_scripts_loaded[url] = node;
	delete _scripts_loading[url]; 
}
/**
 * Remove a JavaScript from the HEAD
 * @param {String} url the URL of the JavaScript file to remove
 */
function removeJavascript(url) {
	var p = new URL(url).toString();
	if (typeof _scripts_loaded[p] != 'undefined') {
		var node = _scripts_loaded[p];
		if (node.parentNode) node.parentNode.removeChild(node);
		delete _scripts_loaded[p];
	}
	if (typeof _scripts_loading[p] != 'undefined') {
		var node = _scripts_loading[p];
		if (node.parentNode) node.parentNode.removeChild(node);
		delete _scripts_loading[p];
	}
	var head = document.getElementsByTagName("HEAD")[0];
	var nodes = [];
	for (var i = 0; i < head.childNodes.length; ++i) nodes.push(head.childNodes[i]);
	for (var i = 0; i < nodes.length; ++i) {
		var e = nodes[i];
		if (e.nodeName != "SCRIPT") continue;
		if (!e.src || e.src.length == 0) continue;
		var u = new URL(e.src).toString();
		if (u == p) {
			head.removeChild(e);
			continue;
		}
	}
}

/**
 * Dynamically load a stylesheet in the page.
 * @param {String} url the URL of the CSS file to load
 * @param {Function} onload if given, called when the CSS file has been loaded
 */
function addStylesheet(url,onload) {
	if (typeof url == 'string') url = new URL(url);
	if (document.readyState != "complete") {
		// delay the load, as we may not have yet all the css in the head
		setTimeout(function(){if (typeof addStylesheet == 'function') addStylesheet(url);},1);
		return;
	}
	var head = document.getElementsByTagName("HEAD")[0];
	for (var i = 0; i < head.childNodes.length; ++i) {
		var e = head.childNodes[i];
		if (e.nodeName != "LINK") continue;
		if (!e.href || e.href.length == 0) continue;
		var u = new URL(e.href);
		if (u.toString() == url.toString()) {
			// we found it
			if (onload) onload();
			return;
		}
	}
	var s = document.createElement("LINK");
	s.rel = "stylesheet";
	s.type = "text/css";
	s.href = url.toString();
	s.onload = function() { if (onload) onload(); this.onload = null; this._loaded = true; };
	document.getElementsByTagName("HEAD")[0].appendChild(s);
}

/**
 * Return the URL of the script ending by the given filename, or null if it cannot be found
 * @param {String} script_filename the file name to search
 * @returns {String} the URL where it has been found, or null of it was not found
 */
function getScriptPath(script_filename) {
	var head = document.getElementsByTagName("HEAD")[0];
	for (var i = 0; i < head.childNodes.length; ++i) {
		var e = head.childNodes[i];
		if (e.nodeName != "SCRIPT") continue;
		if (!e.src || e.src.length == 0) continue;
		var u = new URL(e.src);
		if (!u.path) continue;
		if (u.path.length > script_filename.length && u.path.substring(u.path.length-script_filename.length-1) == "/"+script_filename) {
			u.path = u.path.substring(0, u.path.length-script_filename.length);
			return u.toString();
		}
	}
	return null;
}
