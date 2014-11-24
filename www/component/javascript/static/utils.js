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
String.prototype.parseNumber=function() {
	if (this.length == 0) return Number.NaN;
	var value = 0;
	for (var i = 0; i < this.length; ++i) {
		var ord = this.charCodeAt(i);
		if (ord < 48 || ord > 57) return Number.NaN;
		value *= 10;
		value += (ord-48);
	}
	return value;
};
String.prototype.isSame=function(s) {
	return this.trim().latinize().toLowerCase() == s.trim().latinize().toLowerCase();
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

var latin_map = {'Á':'A','Ă':'A','Ắ':'A','Ặ':'A','Ằ':'A','Ẳ':'A','Ẵ':'A','Ǎ':'A','Â':'A','Ấ':'A','Ậ':'A','Ầ':'A','Ẩ':'A','Ẫ':'A','Ä':'A','Ǟ':'A','Ȧ':'A','Ǡ':'A','Ạ':'A','Ȁ':'A','À':'A','Ả':'A','Ȃ':'A','Ā':'A','Ą':'A','Å':'A','Ǻ':'A','Ḁ':'A','Ⱥ':'A','Ã':'A','Ꜳ':'AA','Æ':'AE','Ǽ':'AE','Ǣ':'AE','Ꜵ':'AO','Ꜷ':'AU','Ꜹ':'AV','Ꜻ':'AV','Ꜽ':'AY','Ḃ':'B','Ḅ':'B','Ɓ':'B','Ḇ':'B','Ƀ':'B','Ƃ':'B','Ć':'C','Č':'C','Ç':'C','Ḉ':'C','Ĉ':'C','Ċ':'C','Ƈ':'C','Ȼ':'C','Ď':'D','Ḑ':'D','Ḓ':'D','Ḋ':'D','Ḍ':'D','Ɗ':'D','Ḏ':'D','ǲ':'D','ǅ':'D','Đ':'D','Ƌ':'D','Ǳ':'DZ','Ǆ':'DZ','É':'E','Ĕ':'E','Ě':'E','Ȩ':'E','Ḝ':'E','Ê':'E','Ế':'E','Ệ':'E','Ề':'E','Ể':'E','Ễ':'E','Ḙ':'E','Ë':'E','Ė':'E','Ẹ':'E','Ȅ':'E','È':'E','Ẻ':'E','Ȇ':'E','Ē':'E','Ḗ':'E','Ḕ':'E','Ę':'E','Ɇ':'E','Ẽ':'E','Ḛ':'E','Ꝫ':'ET','Ḟ':'F','Ƒ':'F','Ǵ':'G','Ğ':'G','Ǧ':'G','Ģ':'G','Ĝ':'G','Ġ':'G','Ɠ':'G','Ḡ':'G','Ǥ':'G','Ḫ':'H','Ȟ':'H','Ḩ':'H','Ĥ':'H','Ⱨ':'H','Ḧ':'H','Ḣ':'H','Ḥ':'H','Ħ':'H','Í':'I','Ĭ':'I','Ǐ':'I','Î':'I','Ï':'I','Ḯ':'I','İ':'I','Ị':'I','Ȉ':'I','Ì':'I','Ỉ':'I','Ȋ':'I','Ī':'I','Į':'I','Ɨ':'I','Ĩ':'I','Ḭ':'I','Ꝺ':'D','Ꝼ':'F','Ᵹ':'G','Ꞃ':'R','Ꞅ':'S','Ꞇ':'T','Ꝭ':'IS','Ĵ':'J','Ɉ':'J','Ḱ':'K','Ǩ':'K','Ķ':'K','Ⱪ':'K','Ꝃ':'K','Ḳ':'K','Ƙ':'K','Ḵ':'K','Ꝁ':'K','Ꝅ':'K','Ĺ':'L','Ƚ':'L','Ľ':'L','Ļ':'L','Ḽ':'L','Ḷ':'L','Ḹ':'L','Ⱡ':'L','Ꝉ':'L','Ḻ':'L','Ŀ':'L','Ɫ':'L','ǈ':'L','Ł':'L','Ǉ':'LJ','Ḿ':'M','Ṁ':'M','Ṃ':'M','Ɱ':'M','Ń':'N','Ň':'N','Ņ':'N','Ṋ':'N','Ṅ':'N','Ṇ':'N','Ǹ':'N','Ɲ':'N','Ṉ':'N','Ƞ':'N','ǋ':'N','Ñ':'N','Ǌ':'NJ','Ó':'O','Ŏ':'O','Ǒ':'O','Ô':'O','Ố':'O','Ộ':'O','Ồ':'O','Ổ':'O','Ỗ':'O','Ö':'O','Ȫ':'O','Ȯ':'O','Ȱ':'O','Ọ':'O','Ő':'O','Ȍ':'O','Ò':'O','Ỏ':'O','Ơ':'O','Ớ':'O','Ợ':'O','Ờ':'O','Ở':'O','Ỡ':'O','Ȏ':'O','Ꝋ':'O','Ꝍ':'O','Ō':'O','Ṓ':'O','Ṑ':'O','Ɵ':'O','Ǫ':'O','Ǭ':'O','Ø':'O','Ǿ':'O','Õ':'O','Ṍ':'O','Ṏ':'O','Ȭ':'O','Ƣ':'OI','Ꝏ':'OO','Ɛ':'E','Ɔ':'O','Ȣ':'OU','Ṕ':'P','Ṗ':'P','Ꝓ':'P','Ƥ':'P','Ꝕ':'P','Ᵽ':'P','Ꝑ':'P','Ꝙ':'Q','Ꝗ':'Q','Ŕ':'R','Ř':'R','Ŗ':'R','Ṙ':'R','Ṛ':'R','Ṝ':'R','Ȑ':'R','Ȓ':'R','Ṟ':'R','Ɍ':'R','Ɽ':'R','Ꜿ':'C','Ǝ':'E','Ś':'S','Ṥ':'S','Š':'S','Ṧ':'S','Ş':'S','Ŝ':'S','Ș':'S','Ṡ':'S','Ṣ':'S','Ṩ':'S','ẞ':'SS','Ť':'T','Ţ':'T','Ṱ':'T','Ț':'T','Ⱦ':'T','Ṫ':'T','Ṭ':'T','Ƭ':'T','Ṯ':'T','Ʈ':'T','Ŧ':'T','Ɐ':'A','Ꞁ':'L','Ɯ':'M','Ʌ':'V','Ꜩ':'TZ','Ú':'U','Ŭ':'U','Ǔ':'U','Û':'U','Ṷ':'U','Ü':'U','Ǘ':'U','Ǚ':'U','Ǜ':'U','Ǖ':'U','Ṳ':'U','Ụ':'U','Ű':'U','Ȕ':'U','Ù':'U','Ủ':'U','Ư':'U','Ứ':'U','Ự':'U','Ừ':'U','Ử':'U','Ữ':'U','Ȗ':'U','Ū':'U','Ṻ':'U','Ų':'U','Ů':'U','Ũ':'U','Ṹ':'U','Ṵ':'U','Ꝟ':'V','Ṿ':'V','Ʋ':'V','Ṽ':'V','Ꝡ':'VY','Ẃ':'W','Ŵ':'W','Ẅ':'W','Ẇ':'W','Ẉ':'W','Ẁ':'W','Ⱳ':'W','Ẍ':'X','Ẋ':'X','Ý':'Y','Ŷ':'Y','Ÿ':'Y','Ẏ':'Y','Ỵ':'Y','Ỳ':'Y','Ƴ':'Y','Ỷ':'Y','Ỿ':'Y','Ȳ':'Y','Ɏ':'Y','Ỹ':'Y','Ź':'Z','Ž':'Z','Ẑ':'Z','Ⱬ':'Z','Ż':'Z','Ẓ':'Z','Ȥ':'Z','Ẕ':'Z','Ƶ':'Z','Ĳ':'IJ','Œ':'OE','ᴀ':'A','ᴁ':'AE','ʙ':'B','ᴃ':'B','ᴄ':'C','ᴅ':'D','ᴇ':'E','ꜰ':'F','ɢ':'G','ʛ':'G','ʜ':'H','ɪ':'I','ʁ':'R','ᴊ':'J','ᴋ':'K','ʟ':'L','ᴌ':'L','ᴍ':'M','ɴ':'N','ᴏ':'O','ɶ':'OE','ᴐ':'O','ᴕ':'OU','ᴘ':'P','ʀ':'R','ᴎ':'N','ᴙ':'R','ꜱ':'S','ᴛ':'T','ⱻ':'E','ᴚ':'R','ᴜ':'U','ᴠ':'V','ᴡ':'W','ʏ':'Y','ᴢ':'Z','á':'a','ă':'a','ắ':'a','ặ':'a','ằ':'a','ẳ':'a','ẵ':'a','ǎ':'a','â':'a','ấ':'a','ậ':'a','ầ':'a','ẩ':'a','ẫ':'a','ä':'a','ǟ':'a','ȧ':'a','ǡ':'a','ạ':'a','ȁ':'a','à':'a','ả':'a','ȃ':'a','ā':'a','ą':'a','ᶏ':'a','ẚ':'a','å':'a','ǻ':'a','ḁ':'a','ⱥ':'a','ã':'a','ꜳ':'aa','æ':'ae','ǽ':'ae','ǣ':'ae','ꜵ':'ao','ꜷ':'au','ꜹ':'av','ꜻ':'av','ꜽ':'ay','ḃ':'b','ḅ':'b','ɓ':'b','ḇ':'b','ᵬ':'b','ᶀ':'b','ƀ':'b','ƃ':'b','ɵ':'o','ć':'c','č':'c','ç':'c','ḉ':'c','ĉ':'c','ɕ':'c','ċ':'c','ƈ':'c','ȼ':'c','ď':'d','ḑ':'d','ḓ':'d','ȡ':'d','ḋ':'d','ḍ':'d','ɗ':'d','ᶑ':'d','ḏ':'d','ᵭ':'d','ᶁ':'d','đ':'d','ɖ':'d','ƌ':'d','ı':'i','ȷ':'j','ɟ':'j','ʄ':'j','ǳ':'dz','ǆ':'dz','é':'e','ĕ':'e','ě':'e','ȩ':'e','ḝ':'e','ê':'e','ế':'e','ệ':'e','ề':'e','ể':'e','ễ':'e','ḙ':'e','ë':'e','ė':'e','ẹ':'e','ȅ':'e','è':'e','ẻ':'e','ȇ':'e','ē':'e','ḗ':'e','ḕ':'e','ⱸ':'e','ę':'e','ᶒ':'e','ɇ':'e','ẽ':'e','ḛ':'e','ꝫ':'et','ḟ':'f','ƒ':'f','ᵮ':'f','ᶂ':'f','ǵ':'g','ğ':'g','ǧ':'g','ģ':'g','ĝ':'g','ġ':'g','ɠ':'g','ḡ':'g','ᶃ':'g','ǥ':'g','ḫ':'h','ȟ':'h','ḩ':'h','ĥ':'h','ⱨ':'h','ḧ':'h','ḣ':'h','ḥ':'h','ɦ':'h','ẖ':'h','ħ':'h','ƕ':'hv','í':'i','ĭ':'i','ǐ':'i','î':'i','ï':'i','ḯ':'i','ị':'i','ȉ':'i','ì':'i','ỉ':'i','ȋ':'i','ī':'i','į':'i','ᶖ':'i','ɨ':'i','ĩ':'i','ḭ':'i','ꝺ':'d','ꝼ':'f','ᵹ':'g','ꞃ':'r','ꞅ':'s','ꞇ':'t','ꝭ':'is','ǰ':'j','ĵ':'j','ʝ':'j','ɉ':'j','ḱ':'k','ǩ':'k','ķ':'k','ⱪ':'k','ꝃ':'k','ḳ':'k','ƙ':'k','ḵ':'k','ᶄ':'k','ꝁ':'k','ꝅ':'k','ĺ':'l','ƚ':'l','ɬ':'l','ľ':'l','ļ':'l','ḽ':'l','ȴ':'l','ḷ':'l','ḹ':'l','ⱡ':'l','ꝉ':'l','ḻ':'l','ŀ':'l','ɫ':'l','ᶅ':'l','ɭ':'l','ł':'l','ǉ':'lj','ſ':'s','ẜ':'s','ẛ':'s','ẝ':'s','ḿ':'m','ṁ':'m','ṃ':'m','ɱ':'m','ᵯ':'m','ᶆ':'m','ń':'n','ň':'n','ņ':'n','ṋ':'n','ȵ':'n','ṅ':'n','ṇ':'n','ǹ':'n','ɲ':'n','ṉ':'n','ƞ':'n','ᵰ':'n','ᶇ':'n','ɳ':'n','ñ':'n','ǌ':'nj','ó':'o','ŏ':'o','ǒ':'o','ô':'o','ố':'o','ộ':'o','ồ':'o','ổ':'o','ỗ':'o','ö':'o','ȫ':'o','ȯ':'o','ȱ':'o','ọ':'o','ő':'o','ȍ':'o','ò':'o','ỏ':'o','ơ':'o','ớ':'o','ợ':'o','ờ':'o','ở':'o','ỡ':'o','ȏ':'o','ꝋ':'o','ꝍ':'o','ⱺ':'o','ō':'o','ṓ':'o','ṑ':'o','ǫ':'o','ǭ':'o','ø':'o','ǿ':'o','õ':'o','ṍ':'o','ṏ':'o','ȭ':'o','ƣ':'oi','ꝏ':'oo','ɛ':'e','ᶓ':'e','ɔ':'o','ᶗ':'o','ȣ':'ou','ṕ':'p','ṗ':'p','ꝓ':'p','ƥ':'p','ᵱ':'p','ᶈ':'p','ꝕ':'p','ᵽ':'p','ꝑ':'p','ꝙ':'q','ʠ':'q','ɋ':'q','ꝗ':'q','ŕ':'r','ř':'r','ŗ':'r','ṙ':'r','ṛ':'r','ṝ':'r','ȑ':'r','ɾ':'r','ᵳ':'r','ȓ':'r','ṟ':'r','ɼ':'r','ᵲ':'r','ᶉ':'r','ɍ':'r','ɽ':'r','ↄ':'c','ꜿ':'c','ɘ':'e','ɿ':'r','ś':'s','ṥ':'s','š':'s','ṧ':'s','ş':'s','ŝ':'s','ș':'s','ṡ':'s','ṣ':'s','ṩ':'s','ʂ':'s','ᵴ':'s','ᶊ':'s','ȿ':'s','ß':'ss','ɡ':'g','ᴑ':'o','ᴓ':'o','ᴝ':'u','ť':'t','ţ':'t','ṱ':'t','ț':'t','ȶ':'t','ẗ':'t','ⱦ':'t','ṫ':'t','ṭ':'t','ƭ':'t','ṯ':'t','ᵵ':'t','ƫ':'t','ʈ':'t','ŧ':'t','ᵺ':'th','ɐ':'a','ᴂ':'ae','ǝ':'e','ᵷ':'g','ɥ':'h','ʮ':'h','ʯ':'h','ᴉ':'i','ʞ':'k','ꞁ':'l','ɯ':'m','ɰ':'m','ᴔ':'oe','ɹ':'r','ɻ':'r','ɺ':'r','ⱹ':'r','ʇ':'t','ʌ':'v','ʍ':'w','ʎ':'y','ꜩ':'tz','ú':'u','ŭ':'u','ǔ':'u','û':'u','ṷ':'u','ü':'u','ǘ':'u','ǚ':'u','ǜ':'u','ǖ':'u','ṳ':'u','ụ':'u','ű':'u','ȕ':'u','ù':'u','ủ':'u','ư':'u','ứ':'u','ự':'u','ừ':'u','ử':'u','ữ':'u','ȗ':'u','ū':'u','ṻ':'u','ų':'u','ᶙ':'u','ů':'u','ũ':'u','ṹ':'u','ṵ':'u','ᵫ':'ue','ꝸ':'um','ⱴ':'v','ꝟ':'v','ṿ':'v','ʋ':'v','ᶌ':'v','ⱱ':'v','ṽ':'v','ꝡ':'vy','ẃ':'w','ŵ':'w','ẅ':'w','ẇ':'w','ẉ':'w','ẁ':'w','ⱳ':'w','ẘ':'w','ẍ':'x','ẋ':'x','ᶍ':'x','ý':'y','ŷ':'y','ÿ':'y','ẏ':'y','ỵ':'y','ỳ':'y','ƴ':'y','ỷ':'y','ỿ':'y','ȳ':'y','ẙ':'y','ɏ':'y','ỹ':'y','ź':'z','ž':'z','ẑ':'z','ʑ':'z','ⱬ':'z','ż':'z','ẓ':'z','ȥ':'z','ẕ':'z','ᵶ':'z','ᶎ':'z','ʐ':'z','ƶ':'z','ɀ':'z','ﬀ':'ff','ﬃ':'ffi','ﬄ':'ffl','ﬁ':'fi','ﬂ':'fl','ĳ':'ij','œ':'oe','ﬆ':'st','ₐ':'a','ₑ':'e','ᵢ':'i','ⱼ':'j','ₒ':'o','ᵣ':'r','ᵤ':'u','ᵥ':'v','ₓ':'x'};
String.prototype.latinise = function() {
	return this.replace(/[^A-Za-z0-9]/g, function(x) { return latin_map[x] || x; });
};
String.prototype.latinize = String.prototype.latinise;
String.prototype.isLatin = function() {
	return this == this.latinise();
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
	if (ord >= 97 && ord <= 122) return true;
	if (ord >= 65 && ord <= 90) return true;
	return false;
}
function isDigit(c) {
	var ord = c.charCodeAt(0);
	return ord >= 48 && ord <= 57;
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
Array.prototype.contains=function(e){return this.indexOf(e) != -1;};
/** 
 * remove all occurences of the given element from this array, if any.
 * @param {any} e the element to remove
 */
Array.prototype.remove=function(e){for(var i=0;i<this.length;++i)if(this[i]==e){this.splice(i,1);i--;};};
Array.prototype.removeUnique=function(e){var i=this.indexOf(e);if(i>=0)this.splice(i,1);};

function _domRemoved(e) {
	if (e._ondomremoved) { e._ondomremoved.fire(e); e._ondomremoved.cleanup(); e._ondomremoved = null; }
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
		log_exception(err, "Remove child failed");
		return null;
	}
};
Element.prototype.removeAllChildren = function() {
	while (this.childNodes.length > 0) this.removeChild(this.childNodes[0]);
};
if (!window.to_cleanup) window.to_cleanup = [];

function addClassName(element, name) {
	if (element.className == "") { element.className = name; return; }
	var names = element.className.split(" ");
	if (names.contains(name)) return;
	element.className += " "+name;
}
function removeClassName(element, name) {
	if (element.className == "") return;
	if (element.className == name) { element.className = ""; return; }
	var names = element.className.split(" ");
	if (!names.contains(name)) return;
	names.remove(name);
	element.className = names.join(" ");
}
function hasClassName(element, name) {
	if (element.className == "") return false;
	if (element.className == name) return true;
	var names = element.className.split(" ");
	return names.contains(name);
}

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
	if (!window.to_cleanup) window.to_cleanup = [];
	window.to_cleanup.push(this);
	
	this.listeners = [];
}
Custom_Event.prototype = {
	/**
	 * Add a listener to this event
	 * @param listener
	 */
	add_listener: function(listener) {
		if (this.listeners === null) return;
		//if (this.listeners.contains(listener)) return;
		this.listeners.push(listener); 
	},
	remove_listener: function(listener) {
		if (this.listeners === null) return;
		this.listeners.removeUnique(listener);
	},
	/**
	 * Trigger the event: call all listeners with the given data as parameter
	 * @param data
	 */
	fire: function(data) {
		if (this.listeners == null) return;
		var list = [];
		for (var i = 0; i < this.listeners.length; ++i) list.push(this.listeners[i]);
		for (var i = 0; i < list.length; ++i) 
			try { list[i](data); } 
			catch (e) { log_exception(e, "occured in event listener: "+list[i]); }
	},
	
	cleanup: function() {
		this.listeners = null;
		if (window && window.to_cleanup && !window.closing)
			window.to_cleanup.removeUnique(this);
	}
};

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
	if (a.length != 3) return null;
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

function getTimeString(d,short) {
	if (d == null) return null;
	if (d.getHours() < 12) return d.getHours()+(!short || d.getMinutes() > 0 ? ":"+_2digits(d.getMinutes()) : "")+"AM";
	if (d.getHours() == 12) return "12"+(!short || d.getMinutes() > 0 ? ":"+_2digits(d.getMinutes()) : "")+"PM";
	return (d.getHours()-12)+(!short || d.getMinutes() > 0 ? ":"+_2digits(d.getMinutes()) : "")+"PM";
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
	default: return "Invalid Month ("+month+")";
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
	default: return "Invalid Day ("+d+")";
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
	default: return "Invalid Day ("+d+")";
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
	default: return "Invalid Day ("+d+")";
	}
}

function wordsMatch(s1, s2, ignore_case) {
	if (ignore_case) {
		s1 = s1.latinize().toLowerCase();
		s2 = s2.latinize().toLowerCase();
	}
	var words1 = prepareMatchScore(s1);
	var words2 = prepareMatchScore(s2);
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

function wordsMatchingWithLetters(s1, s2) {
	s1 = s1.latinize().toLowerCase();
	s2 = s2.latinize().toLowerCase();
	var words1 = prepareMatchScore(s1);
	var words2 = prepareMatchScore(s2);
	var ss1 = ""; for (var i = 0; i < words1.length; ++i) ss1 += words1[i];
	var ss2 = ""; for (var i = 0; i < words2.length; ++i) ss2 += words2[i];
	var ok = true;
	for (var i = 0; ok && i < words1.length; i++) if (ss2.indexOf(words1[i]) < 0) ok = false;
	if (!ok) return false;
	for (var i = 0; ok && i < words2.length; i++) if (ss1.indexOf(words2[i]) < 0) ok = false;
	return ok;
}

function matchScore(ref, needle) {
	return matchScorePrepared(ref, prepareMatchScore(ref), needle, prepareMatchScore(needle));
}
function prepareMatchScore(s) {
	var words = [];
	var word = "";
	for (var i = 0; i < s.length; ++i) {
		var c = s.charAt(i);
		if (isLetter(c) || isDigit(c)) word += c;
		else {
			if (word.length > 0) words.push(word);
			word = "";
		}
	}
	if (word.length > 0) words.push(word);
	return words;
}
function matchScorePrepared(ref, ref_words, needle, needle_words) {
	// same = 100% match
	if (ref == needle) return 100;
	// starts with needle = 95% match
	if (ref.startsWith(needle)) return 95;
	// contains needle = 93%
	if (ref.indexOf(needle)>0) return 93;
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
	//if (ref_words.length > needle_words.length) score -= (ref_words.length-needle_words.length)*0.05;
	//score /= needle_words.length;
	score /= ref_words.length;
	return score*90;
}

function almostMatching(s1, s2) {
	s1 = s1.trim().latinize().toLowerCase();
	s2 = s2.trim().latinize().toLowerCase();
	if (s1.length <= 2 || s2.length <= 2) return false;
	// try to remove one letter in each
	for (var i = 0; i < s1.length; ++i) {
		var ss1 = s1.substr(0,i)+s1.substr(i+1);
		if (s2 == ss1) return true;
	}
	for (var i = 0; i < s2.length; ++i) {
		var ss2 = s2.substr(0,i)+s2.substr(i+1);
		if (s1 == ss2) return true;
	}
	// try to remove one letter in both
	for (var i = 0; i < s1.length; ++i) {
		var ss1 = s1.substr(0,i)+s1.substr(i+1);
		for (var j = 0; j < s2.length; ++j) {
			var ss2 = s2.substr(0,j)+s2.substr(j+1);
			if (ss1 == ss2) return true;
		}
	}
	var i = s1.indexOf('(');
	var par = false;
	if (i > 2) {
		s1 = s1.substr(0,i).trim();
		par = true;
	} 
	i = s2.indexOf('(');
	if (i > 2) {
		s2 = s2.substr(0,i).trim();
		par = true;
	}
	if (par) {
		if (s1.isSame(s2)) return true;
		if (almostMatching(s1, s2)) return true;
	}
	return false;
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