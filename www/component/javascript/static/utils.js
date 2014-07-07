/** 
 * check if this string starts with the given string
 * @param {String} s the start
 * @returns {Boolean} true if the string starts with the given string
 */
String.prototype.startsWith=function(s){return this.length<s.length?false:this.substring(0,s.length)==s;};
/** 
 * check if this string ends with the given string
 * @param {String} s the end
 * @returns {Boolean} true if this string ends with the given string
 */
String.prototype.endsWith=function(s){return this.length<s.length?false:this.substring(this.length-s.length)==s;};
/** 
 * remove leading and trailing spaces, and return the result
 * @returns {String} a new string without any leading or trailing space
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
/** Convert this string into HTML (replace special characters)
 * @returns {String} the HTML string
 */
String.prototype.toHTML=function() {
    return this
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
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
* @param {String} separator separator to use between words
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

function _domRemoved(e) {
	if (e._ondomremoved) e._ondomremoved.fire(e);
	if (e.nodeType != 1) return;
	for (var i = 0; i < e.childNodes.length; ++i)
		_domRemoved(e.childNodes[i]);
}
Element.prototype.ondomremoved = function(listener) {
	if (!this._ondomremoved) this._ondomremoved = new Custom_Event();
	this._ondomremoved.add_listener(listener);
};
Element.prototype._removeChild = Element.prototype.removeChild;
Element.prototype.removeChild = function(e) {
	_domRemoved(e);
	try { return this._removeChild(e); }
	catch (err) {
		window.top.console.error("Remove child failed: "+e.getMessage());
	}
};
Element.prototype.removeAllChildren = function() {
	while (this.childNodes.length > 0) this.removeChild(this.childNodes[0]);
};

function urldecode(s) {
	return decodeURIComponent(s).replace(/\+/g, " ");
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
				this.params[urldecode(p.substr(0,i))] = urldecode(p.substr(i+1));
			else
				this.params[urldecode(p)] = "";
		}
		if (s.length > 0) {
			i = s.indexOf('=');
			if (i > 0)
				this.params[urldecode(s.substr(0,i))] = urldecode(s.substr(i+1));
			else
				this.params[urldecode(s)] = "";
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
	
}
URL.prototype = {
	/** create a string representing the URL */
	toString: function() {
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
	}	
};

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

/** Parse the given SQL date, and returns a Date object
 * @param {String} s the SQL date to convert
 * @returns {Date} the date, or null if it cannot be converted
 */
function parseSQLDate(s) {
	if (s == null || s.length == 0) return null;
	var d = new Date();
	d.setHours(0,0,0,0);
	var a = s.split("-");
	if (a.length == 3)
		d.setFullYear(parseInt(a[0]), parseInt(a[1])-1, parseInt(a[2]));
	return d;
};
/** Convert the given number into a string, containing at least 2 digits (0 added if less than 10)
 * @param {Number} n the number to convert
 * @returns {String} the resulting string with at least 2 digits
 */
function _2digits(n) {
	var s = ""+n;
	while (s.length < 2) s = "0"+s;
	return s;
};
/** Convert a JavaScript date into a SQL date
 * @param {Date} d the date to convert
 * @returns {String} the SQL date, or null if the given date is null
 */
function dateToSQL(d) {
	if (d == null) return null;
	return d.getFullYear()+"-"+_2digits(d.getMonth()+1)+"-"+_2digits(d.getDate());
};
/** Convert the given number into 2 digits hexadecimal number
 * @param {Number} val the number to convert
 * @returns {String} 2 digits hexadecimal
 */
function _2Hex(val) {
	return HexDigit(Math.floor(val/16))+HexDigit(val%16);
}
/** Gives the hexadecimal character of the given number
 * @param {Number} val a number between 0 and 15
 * @returns {String} the hexadecimal character
 */
function HexDigit(val) {
	if (val < 10) return ""+val;
	return String.fromCharCode("A".charCodeAt(0)+(val-10));
}
/** Return a string representation of the given date: 2 digits day, space, month name, space, year
 * @param {Date} d the date
 * @returns {String} the string representation
 */
function getDateString(d) {
	if (d == null) return "";
	return _2digits(d.getDate())+" "+getMonthName(d.getMonth()+1)+" "+d.getFullYear();
}

function getTimeString(d) {
	if (d == null) return "";
	return _2digits(d.getHours())+":"+_2digits(d.getMinutes());
}

function getMinutesTimeString(minutes) {
	if (minutes == null) minutes = 0;
	return _2digits(Math.floor(minutes/60))+":"+_2digits(minutes%60);
}

function parseTimeStringToMinutes(s) {
	var i = s.indexOf(':');
	var h,m;
	if (i < 0) {
		h = parseInt(s);
		m = 0;
	} else {
		h = parseInt(s.substring(0,i));
		m = parseInt(s.substring(i+1));
	}
	if (isNaN(h)) h = 0; else if (h > 23) h = 23; else if (h < 0) h = 0;
	if (isNaN(m)) m = 0; else if (m > 59) m = 59; else if (m < 0) m = 0;
	return h*60+m;
}


/** Return the name of the given month
 * @param {Number} month between 1 and 12
 * @returns {String} the full name of the month
 */
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
	default: return "Invalid Month ("+month+")";
	}
}
/** Return the short name (3 letters) of the given month
 * @param {Number} month between 1 and 12
 * @returns {String} the 3 letters short name of the month
 */
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
/** Return the full name of the given week day
 * @param {Number} d the day between 0 (Monday) and 6 (Sunday)
 * @returns {String} the name of the day
 */
function getDayName(d, from_date) {
	if (from_date) d = d==0 ? 6 : d-1;
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
/** Return the 3 letters short name of the given week day
 * @param {Number} d the day between 0 (Monday) and 6 (Sunday)
 * @returns {String} the 3 letters name of the day
 */
function getDayShortName(d, from_date) {
	if (from_date) d = d==0 ? 6 : d-1;
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
/** Return the 1 letter name of the given week day
 * @param {Number} d the day between 0 (Monday) and 6 (Sunday)
 * @returns {String} the 1 letter name of the day
 */
function getDayLetter(d, from_date) {
	if (from_date) d = d==0 ? 6 : d-1;
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

function wordsMatch(s1, s2) {
	var words1 = s1.split(" ");
	var words2 = s2.split(" ");
	var words1_in_words2 = 0;
	var words2_in_words1 = 0;
	for (var i = 0; i < words1.length; ++i) {
		for (var j = 0; j < words2.length; ++j)
			if (words2[j] == words1[i]) { words1_in_words2++; break; }
	}
	for (var i = 0; i < words2.length; ++i) {
		for (var j = 0; j < words1.length; ++j)
			if (words1[j] == words2[i]) { words2_in_words1++; break; }
	}
	return {nb_words_1:words1.length,nb_words_2:words2.length,nb_words1_in_words2:words1_in_words2,nb_words2_in_words1:words2_in_words1};
}

function matchScore(ref, needle) {
	return matchScorePrepared(ref, prepareMatchScore(ref), needle, prepareMatchScore(needle))
}
function prepareMatchScore(s) {
	var words = s.split(" ");
	for (var i = 0; i < words.length; ++i) {
		words[i] = words[i].trim();
		if (words[i].length == 0) {
			words.splice(i,1);
			i--;
		}
	}
	return words;
}
function matchScorePrepared(ref, ref_words, needle, needle_words) {
	// same = 100% match
	if (ref == needle) return 100;
	// starts with needle = 95% match
	if (ref.startsWith(needle)) return 95;
	// calculate number of words which are the same, starts with, or contains
	var nb_words = 0;
	var nb_starts = 0;
	var nb_contains = 0;
	for (var i = 0; i < needle_words.length; ++i) {
		if (ref_words.contains(needle_words[i])) nb_words++;
		else {
			var found = false;
			for (var j = 0; j < ref_words.length; ++j)
				if (ref_words[j].startsWith(needle_words[i])) {
					nb_starts++;
					found = true;
					break;
				}
			if (!found)
				for (var j = 0; j < ref_words.length; ++j)
					if (ref_words[j].indexOf(needle_words[i]) > 0) {
						nb_contains++;
						found = true;
						break;
					}
		}
	}
	var score = nb_words+nb_starts*0.75+nb_contains*0.5;
	score /= needle_words.length;
	return score*90;
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

/** Get the value of the given cookie name
 * @param {String} cname name of the cookie
 * @returns {String} the value of the cookie (or empty string if it does not exist)
 */
function getCookie(cname) {
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i<ca.length; i++) {
		var c = ca[i].trim();
		if (c.indexOf(name)==0) return c.substring(name.length,c.length);
	}
	return "";
}
/** Set the value of a cookie
 * @param {String} cname name of the cookie
 * @param {String} cvalue value of the cookie
 * @param {Number} expires_minutes expiration time in minutes
 * @param {String} url URL where the cookie is valid
 */
function setCookie(cname,cvalue,expires_minutes,url) {
	var d = new Date();
	d.setTime(d.getTime()+(expires_minutes*60*1000));
	var expires = "expires="+d.toGMTString();
	document.cookie = cname + "=" + cvalue + "; " + expires + "; Path="+url;
}