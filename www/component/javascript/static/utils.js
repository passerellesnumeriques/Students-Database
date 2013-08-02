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