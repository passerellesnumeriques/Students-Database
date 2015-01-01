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
/**
 * Convert this string into an integer. The difference with parseInt is that this function will return NaN even if it starts with digits, but has some non-digits characters after
 * @returns {Number} the integer or NaN
 */
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
/**
 * Compare 2 strings, ignore case, ignore language specific characters (accents...), ignore spaces around
 * @param {String} s the other string with wich to compare
 * @returns {Boolean} true if they are the same
 */
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

/** Mapping to latinize characters
 * @no_doc */
var latin_map = {'Á':'A','Ă':'A','Ắ':'A','Ặ':'A','Ằ':'A','Ẳ':'A','Ẵ':'A','Ǎ':'A','Â':'A','Ấ':'A','Ậ':'A','Ầ':'A','Ẩ':'A','Ẫ':'A','Ä':'A','Ǟ':'A','Ȧ':'A','Ǡ':'A','Ạ':'A','Ȁ':'A','À':'A','Ả':'A','Ȃ':'A','Ā':'A','Ą':'A','Å':'A','Ǻ':'A','Ḁ':'A','Ⱥ':'A','Ã':'A','Ꜳ':'AA','Æ':'AE','Ǽ':'AE','Ǣ':'AE','Ꜵ':'AO','Ꜷ':'AU','Ꜹ':'AV','Ꜻ':'AV','Ꜽ':'AY','Ḃ':'B','Ḅ':'B','Ɓ':'B','Ḇ':'B','Ƀ':'B','Ƃ':'B','Ć':'C','Č':'C','Ç':'C','Ḉ':'C','Ĉ':'C','Ċ':'C','Ƈ':'C','Ȼ':'C','Ď':'D','Ḑ':'D','Ḓ':'D','Ḋ':'D','Ḍ':'D','Ɗ':'D','Ḏ':'D','ǲ':'D','ǅ':'D','Đ':'D','Ƌ':'D','Ǳ':'DZ','Ǆ':'DZ','É':'E','Ĕ':'E','Ě':'E','Ȩ':'E','Ḝ':'E','Ê':'E','Ế':'E','Ệ':'E','Ề':'E','Ể':'E','Ễ':'E','Ḙ':'E','Ë':'E','Ė':'E','Ẹ':'E','Ȅ':'E','È':'E','Ẻ':'E','Ȇ':'E','Ē':'E','Ḗ':'E','Ḕ':'E','Ę':'E','Ɇ':'E','Ẽ':'E','Ḛ':'E','Ꝫ':'ET','Ḟ':'F','Ƒ':'F','Ǵ':'G','Ğ':'G','Ǧ':'G','Ģ':'G','Ĝ':'G','Ġ':'G','Ɠ':'G','Ḡ':'G','Ǥ':'G','Ḫ':'H','Ȟ':'H','Ḩ':'H','Ĥ':'H','Ⱨ':'H','Ḧ':'H','Ḣ':'H','Ḥ':'H','Ħ':'H','Í':'I','Ĭ':'I','Ǐ':'I','Î':'I','Ï':'I','Ḯ':'I','İ':'I','Ị':'I','Ȉ':'I','Ì':'I','Ỉ':'I','Ȋ':'I','Ī':'I','Į':'I','Ɨ':'I','Ĩ':'I','Ḭ':'I','Ꝺ':'D','Ꝼ':'F','Ᵹ':'G','Ꞃ':'R','Ꞅ':'S','Ꞇ':'T','Ꝭ':'IS','Ĵ':'J','Ɉ':'J','Ḱ':'K','Ǩ':'K','Ķ':'K','Ⱪ':'K','Ꝃ':'K','Ḳ':'K','Ƙ':'K','Ḵ':'K','Ꝁ':'K','Ꝅ':'K','Ĺ':'L','Ƚ':'L','Ľ':'L','Ļ':'L','Ḽ':'L','Ḷ':'L','Ḹ':'L','Ⱡ':'L','Ꝉ':'L','Ḻ':'L','Ŀ':'L','Ɫ':'L','ǈ':'L','Ł':'L','Ǉ':'LJ','Ḿ':'M','Ṁ':'M','Ṃ':'M','Ɱ':'M','Ń':'N','Ň':'N','Ņ':'N','Ṋ':'N','Ṅ':'N','Ṇ':'N','Ǹ':'N','Ɲ':'N','Ṉ':'N','Ƞ':'N','ǋ':'N','Ñ':'N','Ǌ':'NJ','Ó':'O','Ŏ':'O','Ǒ':'O','Ô':'O','Ố':'O','Ộ':'O','Ồ':'O','Ổ':'O','Ỗ':'O','Ö':'O','Ȫ':'O','Ȯ':'O','Ȱ':'O','Ọ':'O','Ő':'O','Ȍ':'O','Ò':'O','Ỏ':'O','Ơ':'O','Ớ':'O','Ợ':'O','Ờ':'O','Ở':'O','Ỡ':'O','Ȏ':'O','Ꝋ':'O','Ꝍ':'O','Ō':'O','Ṓ':'O','Ṑ':'O','Ɵ':'O','Ǫ':'O','Ǭ':'O','Ø':'O','Ǿ':'O','Õ':'O','Ṍ':'O','Ṏ':'O','Ȭ':'O','Ƣ':'OI','Ꝏ':'OO','Ɛ':'E','Ɔ':'O','Ȣ':'OU','Ṕ':'P','Ṗ':'P','Ꝓ':'P','Ƥ':'P','Ꝕ':'P','Ᵽ':'P','Ꝑ':'P','Ꝙ':'Q','Ꝗ':'Q','Ŕ':'R','Ř':'R','Ŗ':'R','Ṙ':'R','Ṛ':'R','Ṝ':'R','Ȑ':'R','Ȓ':'R','Ṟ':'R','Ɍ':'R','Ɽ':'R','Ꜿ':'C','Ǝ':'E','Ś':'S','Ṥ':'S','Š':'S','Ṧ':'S','Ş':'S','Ŝ':'S','Ș':'S','Ṡ':'S','Ṣ':'S','Ṩ':'S','ẞ':'SS','Ť':'T','Ţ':'T','Ṱ':'T','Ț':'T','Ⱦ':'T','Ṫ':'T','Ṭ':'T','Ƭ':'T','Ṯ':'T','Ʈ':'T','Ŧ':'T','Ɐ':'A','Ꞁ':'L','Ɯ':'M','Ʌ':'V','Ꜩ':'TZ','Ú':'U','Ŭ':'U','Ǔ':'U','Û':'U','Ṷ':'U','Ü':'U','Ǘ':'U','Ǚ':'U','Ǜ':'U','Ǖ':'U','Ṳ':'U','Ụ':'U','Ű':'U','Ȕ':'U','Ù':'U','Ủ':'U','Ư':'U','Ứ':'U','Ự':'U','Ừ':'U','Ử':'U','Ữ':'U','Ȗ':'U','Ū':'U','Ṻ':'U','Ų':'U','Ů':'U','Ũ':'U','Ṹ':'U','Ṵ':'U','Ꝟ':'V','Ṿ':'V','Ʋ':'V','Ṽ':'V','Ꝡ':'VY','Ẃ':'W','Ŵ':'W','Ẅ':'W','Ẇ':'W','Ẉ':'W','Ẁ':'W','Ⱳ':'W','Ẍ':'X','Ẋ':'X','Ý':'Y','Ŷ':'Y','Ÿ':'Y','Ẏ':'Y','Ỵ':'Y','Ỳ':'Y','Ƴ':'Y','Ỷ':'Y','Ỿ':'Y','Ȳ':'Y','Ɏ':'Y','Ỹ':'Y','Ź':'Z','Ž':'Z','Ẑ':'Z','Ⱬ':'Z','Ż':'Z','Ẓ':'Z','Ȥ':'Z','Ẕ':'Z','Ƶ':'Z','Ĳ':'IJ','Œ':'OE','ᴀ':'A','ᴁ':'AE','ʙ':'B','ᴃ':'B','ᴄ':'C','ᴅ':'D','ᴇ':'E','ꜰ':'F','ɢ':'G','ʛ':'G','ʜ':'H','ɪ':'I','ʁ':'R','ᴊ':'J','ᴋ':'K','ʟ':'L','ᴌ':'L','ᴍ':'M','ɴ':'N','ᴏ':'O','ɶ':'OE','ᴐ':'O','ᴕ':'OU','ᴘ':'P','ʀ':'R','ᴎ':'N','ᴙ':'R','ꜱ':'S','ᴛ':'T','ⱻ':'E','ᴚ':'R','ᴜ':'U','ᴠ':'V','ᴡ':'W','ʏ':'Y','ᴢ':'Z','á':'a','ă':'a','ắ':'a','ặ':'a','ằ':'a','ẳ':'a','ẵ':'a','ǎ':'a','â':'a','ấ':'a','ậ':'a','ầ':'a','ẩ':'a','ẫ':'a','ä':'a','ǟ':'a','ȧ':'a','ǡ':'a','ạ':'a','ȁ':'a','à':'a','ả':'a','ȃ':'a','ā':'a','ą':'a','ᶏ':'a','ẚ':'a','å':'a','ǻ':'a','ḁ':'a','ⱥ':'a','ã':'a','ꜳ':'aa','æ':'ae','ǽ':'ae','ǣ':'ae','ꜵ':'ao','ꜷ':'au','ꜹ':'av','ꜻ':'av','ꜽ':'ay','ḃ':'b','ḅ':'b','ɓ':'b','ḇ':'b','ᵬ':'b','ᶀ':'b','ƀ':'b','ƃ':'b','ɵ':'o','ć':'c','č':'c','ç':'c','ḉ':'c','ĉ':'c','ɕ':'c','ċ':'c','ƈ':'c','ȼ':'c','ď':'d','ḑ':'d','ḓ':'d','ȡ':'d','ḋ':'d','ḍ':'d','ɗ':'d','ᶑ':'d','ḏ':'d','ᵭ':'d','ᶁ':'d','đ':'d','ɖ':'d','ƌ':'d','ı':'i','ȷ':'j','ɟ':'j','ʄ':'j','ǳ':'dz','ǆ':'dz','é':'e','ĕ':'e','ě':'e','ȩ':'e','ḝ':'e','ê':'e','ế':'e','ệ':'e','ề':'e','ể':'e','ễ':'e','ḙ':'e','ë':'e','ė':'e','ẹ':'e','ȅ':'e','è':'e','ẻ':'e','ȇ':'e','ē':'e','ḗ':'e','ḕ':'e','ⱸ':'e','ę':'e','ᶒ':'e','ɇ':'e','ẽ':'e','ḛ':'e','ꝫ':'et','ḟ':'f','ƒ':'f','ᵮ':'f','ᶂ':'f','ǵ':'g','ğ':'g','ǧ':'g','ģ':'g','ĝ':'g','ġ':'g','ɠ':'g','ḡ':'g','ᶃ':'g','ǥ':'g','ḫ':'h','ȟ':'h','ḩ':'h','ĥ':'h','ⱨ':'h','ḧ':'h','ḣ':'h','ḥ':'h','ɦ':'h','ẖ':'h','ħ':'h','ƕ':'hv','í':'i','ĭ':'i','ǐ':'i','î':'i','ï':'i','ḯ':'i','ị':'i','ȉ':'i','ì':'i','ỉ':'i','ȋ':'i','ī':'i','į':'i','ᶖ':'i','ɨ':'i','ĩ':'i','ḭ':'i','ꝺ':'d','ꝼ':'f','ᵹ':'g','ꞃ':'r','ꞅ':'s','ꞇ':'t','ꝭ':'is','ǰ':'j','ĵ':'j','ʝ':'j','ɉ':'j','ḱ':'k','ǩ':'k','ķ':'k','ⱪ':'k','ꝃ':'k','ḳ':'k','ƙ':'k','ḵ':'k','ᶄ':'k','ꝁ':'k','ꝅ':'k','ĺ':'l','ƚ':'l','ɬ':'l','ľ':'l','ļ':'l','ḽ':'l','ȴ':'l','ḷ':'l','ḹ':'l','ⱡ':'l','ꝉ':'l','ḻ':'l','ŀ':'l','ɫ':'l','ᶅ':'l','ɭ':'l','ł':'l','ǉ':'lj','ſ':'s','ẜ':'s','ẛ':'s','ẝ':'s','ḿ':'m','ṁ':'m','ṃ':'m','ɱ':'m','ᵯ':'m','ᶆ':'m','ń':'n','ň':'n','ņ':'n','ṋ':'n','ȵ':'n','ṅ':'n','ṇ':'n','ǹ':'n','ɲ':'n','ṉ':'n','ƞ':'n','ᵰ':'n','ᶇ':'n','ɳ':'n','ñ':'n','ǌ':'nj','ó':'o','ŏ':'o','ǒ':'o','ô':'o','ố':'o','ộ':'o','ồ':'o','ổ':'o','ỗ':'o','ö':'o','ȫ':'o','ȯ':'o','ȱ':'o','ọ':'o','ő':'o','ȍ':'o','ò':'o','ỏ':'o','ơ':'o','ớ':'o','ợ':'o','ờ':'o','ở':'o','ỡ':'o','ȏ':'o','ꝋ':'o','ꝍ':'o','ⱺ':'o','ō':'o','ṓ':'o','ṑ':'o','ǫ':'o','ǭ':'o','ø':'o','ǿ':'o','õ':'o','ṍ':'o','ṏ':'o','ȭ':'o','ƣ':'oi','ꝏ':'oo','ɛ':'e','ᶓ':'e','ɔ':'o','ᶗ':'o','ȣ':'ou','ṕ':'p','ṗ':'p','ꝓ':'p','ƥ':'p','ᵱ':'p','ᶈ':'p','ꝕ':'p','ᵽ':'p','ꝑ':'p','ꝙ':'q','ʠ':'q','ɋ':'q','ꝗ':'q','ŕ':'r','ř':'r','ŗ':'r','ṙ':'r','ṛ':'r','ṝ':'r','ȑ':'r','ɾ':'r','ᵳ':'r','ȓ':'r','ṟ':'r','ɼ':'r','ᵲ':'r','ᶉ':'r','ɍ':'r','ɽ':'r','ↄ':'c','ꜿ':'c','ɘ':'e','ɿ':'r','ś':'s','ṥ':'s','š':'s','ṧ':'s','ş':'s','ŝ':'s','ș':'s','ṡ':'s','ṣ':'s','ṩ':'s','ʂ':'s','ᵴ':'s','ᶊ':'s','ȿ':'s','ß':'ss','ɡ':'g','ᴑ':'o','ᴓ':'o','ᴝ':'u','ť':'t','ţ':'t','ṱ':'t','ț':'t','ȶ':'t','ẗ':'t','ⱦ':'t','ṫ':'t','ṭ':'t','ƭ':'t','ṯ':'t','ᵵ':'t','ƫ':'t','ʈ':'t','ŧ':'t','ᵺ':'th','ɐ':'a','ᴂ':'ae','ǝ':'e','ᵷ':'g','ɥ':'h','ʮ':'h','ʯ':'h','ᴉ':'i','ʞ':'k','ꞁ':'l','ɯ':'m','ɰ':'m','ᴔ':'oe','ɹ':'r','ɻ':'r','ɺ':'r','ⱹ':'r','ʇ':'t','ʌ':'v','ʍ':'w','ʎ':'y','ꜩ':'tz','ú':'u','ŭ':'u','ǔ':'u','û':'u','ṷ':'u','ü':'u','ǘ':'u','ǚ':'u','ǜ':'u','ǖ':'u','ṳ':'u','ụ':'u','ű':'u','ȕ':'u','ù':'u','ủ':'u','ư':'u','ứ':'u','ự':'u','ừ':'u','ử':'u','ữ':'u','ȗ':'u','ū':'u','ṻ':'u','ų':'u','ᶙ':'u','ů':'u','ũ':'u','ṹ':'u','ṵ':'u','ᵫ':'ue','ꝸ':'um','ⱴ':'v','ꝟ':'v','ṿ':'v','ʋ':'v','ᶌ':'v','ⱱ':'v','ṽ':'v','ꝡ':'vy','ẃ':'w','ŵ':'w','ẅ':'w','ẇ':'w','ẉ':'w','ẁ':'w','ⱳ':'w','ẘ':'w','ẍ':'x','ẋ':'x','ᶍ':'x','ý':'y','ŷ':'y','ÿ':'y','ẏ':'y','ỵ':'y','ỳ':'y','ƴ':'y','ỷ':'y','ỿ':'y','ȳ':'y','ẙ':'y','ɏ':'y','ỹ':'y','ź':'z','ž':'z','ẑ':'z','ʑ':'z','ⱬ':'z','ż':'z','ẓ':'z','ȥ':'z','ẕ':'z','ᵶ':'z','ᶎ':'z','ʐ':'z','ƶ':'z','ɀ':'z','ﬀ':'ff','ﬃ':'ffi','ﬄ':'ffl','ﬁ':'fi','ﬂ':'fl','ĳ':'ij','œ':'oe','ﬆ':'st','ₐ':'a','ₑ':'e','ᵢ':'i','ⱼ':'j','ₒ':'o','ᵣ':'r','ᵤ':'u','ᵥ':'v','ₓ':'x'};
/** Replace accents by their corresponding latin character. (i.e. ộ will become o) */
String.prototype.latinise = function() {
	return this.replace(/[^A-Za-z0-9]/g, function(x) { return latin_map[x] || x; });
};
String.prototype.latinize = String.prototype.latinise;
/** Try to latinize the string and return true if it didn't changed */
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
/** @no_doc */
var ascii_0 = 48, ascii_9 = ascii_0+9;
/** Check if the given character is a digit
 * @param {String} c the character
 * @returns {Boolean} true if this is a decimal digit
 */
function isDigit(c) {
	var ord = c.charCodeAt(0);
	return ord >= ascii_0 && ord <= ascii_9;
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

/** @no_doc */
function _domRemoved(e) {
	if (e._ondomremoved) { e._ondomremoved.fire(e); e._ondomremoved.cleanup(); e._ondomremoved = null; }
	if (e.nodeType != 1) return;
	for (var i = 0; i < e.childNodes.length; ++i)
		_domRemoved(e.childNodes[i]);
}
/** Add a listener to be called when this element is removed from the DOM
 * @param {Function} listener the function to call, with the element as parameter
 */
Element.prototype.ondomremoved = function(listener) {
	if (!this._ondomremoved) this._ondomremoved = new Custom_Event();
	this._ondomremoved.addListener(listener);
};
/** @no_doc */
Element.prototype._removeChild = Element.prototype.removeChild;
/** @no_doc */
Element.prototype.removeChild = function(e) {
	if (!_domRemoved) return;
	_domRemoved(e);
	return this._removeChild(e);
};
/** Remove all children on this element.
 * This must be used instead of using innerHTML, so that we can fire remove events on every element.
 */
Element.prototype.removeAllChildren = function() {
	while (this.childNodes.length > 0) this.removeChild(this.childNodes[0]);
};
if (!window.to_cleanup) window.to_cleanup = [];

/**
 * Add a CSS class on an element
 * @param {Element} element the element
 * @param {String} name the name of the CSS class
 */
function addClassName(element, name) {
	if (element.className == "") { element.className = name; return; }
	var names = element.className.split(" ");
	if (names.contains(name)) return;
	element.className += " "+name;
}
/**
 * Remove a CSS class on an element
 * @param {Element} element the element
 * @param {String} name the name of the CSS class
 */
function removeClassName(element, name) {
	if (element.className == "") return;
	if (element.className == name) { element.className = ""; return; }
	var names = element.className.split(" ");
	if (!names.contains(name)) return;
	names.remove(name);
	element.className = names.join(" ");
}
/**
 * Check if an element has a CSS class
 * @param {Element} element the element
 * @param {String} name the name of the CSS class
 * @returns {Boolean} true if the element has this CSS class
 */
function hasClassName(element, name) {
	if (element.className == "") return false;
	if (element.className == name) return true;
	var names = element.className.split(" ");
	return names.contains(name);
}

/**
 * Decode a string given in URL format. Compare to decodeURIComponent, it will also replace the '+' sign with a space
 * @param {String} s the string to decode
 * @returns {String} the decoded string
 */
function urldecode(s) {
	return decodeURIComponent(s).replace(/\+/g, " ");
}

/** Represent an URL
 * @param {String} s string containing the URL to be parsed
 */
function URL(s) {
	var i = s.indexOf("://");
	if (i > 0) {
		/** the protocol of the URL (i.e. http) */
		this.protocol = s.substr(0, i).toLowerCase();
		s = s.substr(i+3);
		i = s.indexOf("/");
		/** the hostname (i.e. www.google.com) */
		this.host = s.substr(0,i);
		s = s.substr(i);
		i = this.host.indexOf(":");
		if (i > 0) {
			/** the port number (i.e. 80) */
			this.port = this.host.substr(i+1);
			this.host = this.host.substr(0,i);
		} else
			/** the port number (i.e. 80) */
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
		/** the anchor */
		this.hash = s.substr(i+1);
		s = s.substr(0,i);
	}
	i = s.indexOf('?');
	/** the parameters of the URL (i.e. path?param1=value1&param2=value2 will create an object with 2 attributes) */
	this.params = new Object();
	if (i > 0) {
		/** the path of the resource pointed by this URL */
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

/** 
 * Clean Custom_Event objects to avoid memory leaks.
 * This is used to to the cleanup on a regular basis, to avoid when reloading a part of a page
 * containing a lot of elements, to have to clean thousands of objects, this can be done a little later when JavaScript is not busy anymore.
 */
function Custom_Event_Cleaner() {
	/** List of events to clean */
	this.events = {};
	/** Clean */
	this.cleanup = function() {
		clearInterval(window._cecleaner_interval);
		window._cecleaner_interval = null;
		var list = this.events;
		this.events = {};
		for (var id in list)
			list[id].cleanup();
		this.cleant = [];
	};
	/** List of already cleant */
	this.cleant = [];
	/** Remove some cleant */
	this.removeCleant = function() {
		var list = this.cleant.splice(0,250);
		for (var i = 0; i < list.length; ++i)
			delete this.events[list[i]];
	};
}
/** @no_doc */
window._cecleaner = new Custom_Event_Cleaner();
/** @no_doc */
window._cecleaner_interval = setInterval(function(){window._cecleaner.removeCleant();}, 5000);
if (!window.to_cleanup) window.to_cleanup = [];
window.to_cleanup.push(window._cecleaner);
/** @no_doc */
var _cecounter = 1;

/** Event
 * @constructor
 */
function Custom_Event() {
	/** List of listeners */
	this.listeners = [];
	/** {Number} Internal id to improve performance in searching it */
	this.id = _cecounter++;
}
Custom_Event.prototype = {
	/**
	 * Add a listener to this event
	 * @param {Function} listener the function to call
	 */
		addListener: function(listener) {
		if (this.listeners === null) return;
		if (this.listeners.length == 0) window._cecleaner.events[this.id] = this;
		this.listeners.push(listener); 
	},
	/**
	 * Remove a listener from this event
	 * @param {Function} listener the function
	 */
	removeListener: function(listener) {
		if (this.listeners === null) return;
		this.listeners.removeUnique(listener);
		if (this.listeners.length == 0) delete window._cecleaner.events[this.id];
	},
	/**
	 * Trigger the event: call all listeners with the given data as parameter
	 * @param {Object} data anything can be given
	 */
	fire: function(data) {
		if (this.listeners == null) return;
		var list = [];
		for (var i = 0; i < this.listeners.length; ++i) list.push(this.listeners[i]);
		for (var i = 0; i < list.length; ++i) 
			try { list[i](data); } 
			catch (e) { logException(e, "occured in event listener: "+list[i]); }
	},
	/** Clean this object to avoid memory leaks */
	cleanup: function() {
		if (this.listeners === null) return;
		if (this.listeners.length > 0 && window._cecleaner)
			window._cecleaner.cleant.push(this.id);
		this.listeners = null;
	}
};

/**
 * Utility object when several asynchronous operations are done, and we are waiting for all to finish before to continue.
 * @param {Number} nb_pending_operations number of asynchronous operations pending
 * @param {Function} onready function to call when all are finished
 */
function AsynchLoadListener(nb_pending_operations, onready) {
	this.nb_waiting = nb_pending_operations;
	/** Indicates one asynchronous operation just finished */
	this.operationDone = function() {
		if (--this.nb_waiting == 0) onready();
	};
}

/**
 * Print an error message to the console
 * @param {Exception} e the error
 * @param {String} additional_message if given, it will be added to the error message of the exception
 */
function logException(e, additional_message) {
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
 * Default implementation of errorDialog is using alert
 * @param {String} message error message
 */
function errorDialog(message) {
	alert(message);
}

/** Parse the given SQL date, and returns a Date object
 * @param {String} s the SQL date to convert
 * @param {Boolean} utc if true, the date will be set in UTC timezone
 * @returns {Date} the date, or null if it cannot be converted
 */
function parseSQLDate(s, utc) {
	if (s == null || s.length == 0) return null;
	var d = new Date();
	if (utc) d.setUTCHours(0,0,0,0);
	else d.setHours(0,0,0,0);
	var a = s.split("-");
	if (a.length != 3) return null;
	if (utc) {
		d.setUTCFullYear(parseInt(a[0]));
		d.setUTCMonth(parseInt(a[1])-1);
		d.setUTCDate(parseInt(a[2]));
	} else d.setFullYear(parseInt(a[0]), parseInt(a[1])-1, parseInt(a[2]));
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
 * @param {Boolean} utc if true, we will use the date as a UTC date
 * @returns {String} the SQL date, or null if the given date is null
 */
function dateToSQL(d, utc) {
	if (d == null) return null;
	if (utc) return d.getUTCFullYear()+"-"+_2digits(d.getUTCMonth()+1)+"-"+_2digits(d.getUTCDate());
	return d.getFullYear()+"-"+_2digits(d.getMonth()+1)+"-"+_2digits(d.getDate());
};
/** Convert the given number into 2 digits hexadecimal number
 * @param {Number} val the number to convert
 * @returns {String} 2 digits hexadecimal
 */
function _2Hex(val) {
	return hexDigit(Math.floor(val/16))+hexDigit(val%16);
}
/** Gives the hexadecimal character of the given number
 * @param {Number} val a number between 0 and 15
 * @returns {String} the hexadecimal character
 */
function hexDigit(val) {
	if (val < 10) return ""+val;
	return String.fromCharCode("A".charCodeAt(0)+(val-10));
}
/** Return a string representation of the given date: 2 digits day, space, month name, space, year
 * @param {Date} d the date
 * @param {Boolean} short if true, the month name will be short
 * @returns {String} the string representation
 */
function getDateString(d,short) {
	if (d == null) return "";
	return _2digits(d.getDate())+" "+(short ? getMonthShortName(d.getMonth()+1) : getMonthName(d.getMonth()+1))+" "+d.getFullYear();
}
/**
 * Convert a date into a time string
 * @param {Date} d the date
 * @param {Boolean} short it true we will try to make it as short as possible (if number of minutes is 0, we only show hours)
 * @returns {String} the time string
 */
function getTimeString(d,short) {
	if (d == null) return null;
	return getTimeStringFromMinutes(d.getHours()*60+d.getMinutes(), short);
}
/**
 * Convert a number of minutes into a time string
 * @param {Number} minutes number of minutes
 * @param {Boolean} short it true we will try to make it as short as possible (if number of minutes is 0, we only show hours)
 * @returns {String} the time string
 */
function getTimeStringFromMinutes(minutes, short) {
	var h = Math.floor(minutes/60);
	var m = minutes-h*60;
	if (h < 12) return h+(!short || m > 0 ? ":"+_2digits(m) : "")+"AM";
	if (h == 12) return "12"+(!short || m > 0 ? ":"+_2digits(m) : "")+"PM";
	return (h-12)+(!short || m > 0 ? ":"+_2digits(m) : "")+"PM";
}
/**
 * Convert a number of minutes into a duration string
 * @param {Number} minutes number of minutes
 * @returns {String} the duration string
 */
function getDurationStringFromMinutes(minutes) {
	if (minutes == null) return "";
	var h = Math.floor(minutes/60);
	var m = minutes-h*60;
	if (m == 0) return h+"h";
	return h+"h"+_2digits(m)
}
/**
 * Convert a time string into a number of minutes
 * @param {String} s the time string
 * @returns {Number} number of minutes, or null if it is not a valid time string
 */
function parseTimeStringToMinutes(s) {
	if (s == null || s.length == 0) return null;
	// first, we expect 1 or 2 digits for the hours
	var d1 = s.charCodeAt(0);
	if (d1 < ascii_0 || d1 > ascii_9) return null; // not a digit
	d1 -= ascii_0;
	if (s.length == 1) return d1*60; // only 1 digit => considered as a number of hours
	var d2 = s.charCodeAt(1);
	var i;
	if (d2 >= ascii_0 && d2 <= ascii_9) {
		// we have a second digit
		d2 -= ascii_0;
		d1 = d1*10+d2;
		i = 2;
		if (d1 > 23) return null; // invalid number: we cannot have a value more than 23 hours
		if (s.length == 2) return d1*60; // we have only those 2 digits => considered as number of hours
	} else
		i = 1;
	// after hours, we may have minutes, or AM/PM
	d2 = s.charAt(i);
	if (d2 == ":") {
		// we now expect minutes
		i++;
		if (s.length == i) return d1*60; // we have 'xx:' => we just consider xx as hours
		d2 = s.charCodeAt(i);
		if (d2 >= ascii_0 && d2 <= ascii_9) {
			// we have a digit, so we have the minutes
			d2 -= ascii_0;
			i++;
			if (s.length == i) return d1*60+d2; // we have xx:x => return it
			var d3 = s.charCodeAt(i);
			if (d3 >= ascii_0 && d3 <= ascii_9) {
				// we have a second digit
				d3 -= ascii_0;
				d2 = d2*10+d3;
				i++;
				if (s.length == i) return d1*60+d2; // we have xx:xx => return it
			}
		}
	} else
		d2 = 0; // no minutes
	var c = s.charAt(i);
	if (c == 'a' || c == 'A') {
		var c2 = s.length > i ? s.charAt(i+1) : null;
		if (c2 == 'm' || c2  == 'M') {
			// we got the AM
			if (d1 > 12) return null; // invalid number of hours
			return d1*60+d2;
		}
	} else if (c == 'p' || c == 'P') {
		var c2 = s.length > i ? s.charAt(i+1) : null;
		if (c2 == 'm' || c2  == 'M') {
			// we got the PM
			if (d1 > 12) return null; // invalid number of hours
			if (d1 == 12) return d1*60+d2; // 12PM is noon
			return (d1+12)*60+d2;
		}
	}
	// if we are here, there is something unexpected at the end, we just ignore it
	return d1*60+d2;
}
/**
 * Convert a duratino string into a number of minutes
 * @param {String} s the duratino string
 * @returns {Number} number of minutes, or null if it is not a valid duration string
 */
function parseDurationStringToMinutes(s) {
	if (s == null || s.length == 0) return null;
	// first, we expect 1 or 2 digits for the hours or minutes
	var d1 = s.charCodeAt(0);
	if (d1 < ascii_0 || d1 > ascii_9) return null; // not a digit
	d1 -= ascii_0;
	if (s.length == 1) return d1*60; // only 1 digit => considered as a number of hours
	var d2 = s.charCodeAt(1);
	var i;
	if (d2 >= ascii_0 && d2 <= ascii_9) {
		// we have a second digit
		d2 -= ascii_0;
		d1 = d1*10+d2;
		i = 2;
		if (s.length == 2) return d1*60; // we have only those 2 digits => considered as number of hours
	} else
		i = 1;
	// after the first number, we may have ':' to separate from minutes, or the unit 'h' or 'm'
	d2 = s.charAt(i);
	if (d2 == 'h' || d2 == 'H') {
		// first number was hours
		d1 *= 60;
		i++;
	} else if (d2 == 'm' || d2 == 'M') {
		// first number was minutes
		return d1;
	} else if (d2 == ':') {
		// first number was hours
		d1 *= 60;
		i++;
	} else
		return null; // unexpected character
	if (s.length == i) return d1; // we reached the end
	// if we are here, we got 'xx:' or 'xxh', so now we expect a number of minutes
	d2 = s.charCodeAt(i);
	if (d2 >= ascii_0 && d2 <= ascii_9) {
		// we have a digit, so we have the minutes
		d2 -= ascii_0;
		i++;
		if (s.length == i) return d1*60+d2; // we have xx:x => return it
		var d3 = s.charCodeAt(i);
		if (d3 >= ascii_0 && d3 <= ascii_9) {
			// we have a second digit
			d3 -= ascii_0;
			d2 = d2*10+d3;
		}
		return d1+d2;
	}
	return d1;
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
 * @param {Boolean} from_date if true, we will convert d, because in a Date object, 0 is Sunday
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
 * @param {Boolean} from_date if true, we will convert d, because in a Date object, 0 is Sunday
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
 * @param {Boolean} from_date if true, we will convert d, because in a Date object, 0 is Sunday
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

/**
 * Check how much the 2 given strings are matching
 * @param {String} s1 the first string
 * @param {String} s2 the second string
 * @param {Boolean} ignore_case if true, we will ignore case and latinize the strings
 * @returns {Object} an object with the following information:<ul>
 * <li>nb_words_1: the number of words contained in s1</li>
 * <li>nb_words_2: the number of words contained in s2</li>
 * <li>nb_words1_in_words2: the number of words of s1 which are also in s2, by comparing word by word</li>
 * <li>nb_words2_in_words1: the number of words of s2 which are also in s1, by comparing word by word</li>
 * <li>nb_words1_in_2: the number of words of s1 present in s2 (but not necessarly as an individual word in s2, it may be a part of a word of s2)</li>
 * <li>nb_words2_in_1: the number of words of s2 present in s1 (but not necessarly as an individual word in s1, it may be a part of a word of s1)</li>
 * <li>_1_fully_in_2: if true, it means that removing all words of s2 from s1, s1 became empty</li>
 * <li>_2_fully_in_1: if true, it means that removing all words of s1 from s2, s2 became empty</li>
 * </ul>
 */
function wordsMatch(s1, s2, ignore_case) {
	if (ignore_case) {
		s1 = s1.latinize().toLowerCase();
		s2 = s2.latinize().toLowerCase();
	}
	var words1 = prepareMatchScore(s1);
	var words2 = prepareMatchScore(s2);
	var words1_in_words2 = 0;
	var words2_in_words1 = 0;
	var words1_in_2 = 0;
	var words2_in_1 = 0;
	var rem2 = ""+s2;
	for (var i = 0; i < words1.length; ++i) {
		for (var j = 0; j < words2.length; ++j)
			if (words2[j] == words1[i]) { words1_in_words2++; break; }
		var j = rem2.indexOf(words1[i]);
		if (j >= 0) {
			words1_in_2++;
			rem2 = rem2.substring(0,j)+rem2.substring(j+words1[i].length);
		}
	}
	var rem1 = ""+s1;
	for (var i = 0; i < words2.length; ++i) {
		for (var j = 0; j < words1.length; ++j)
			if (words1[j] == words2[i]) { words2_in_words1++; break; }
		var j = rem1.indexOf(words2[i]);
		if (j >= 0) {
			words2_in_1++;
			rem1 = rem1.substring(0,j)+rem1.substring(j+words2[i].length);
		}
	}
	return {
		nb_words_1:words1.length,
		nb_words_2:words2.length,
		nb_words1_in_words2:words1_in_words2,
		nb_words2_in_words1:words2_in_words1,
		nb_words1_in_2:words1_in_2,
		nb_words2_in_1:words2_in_1,
		_1_fully_in_2: rem1.trim().length == 0,
		_2_fully_in_1: rem2.trim().length == 0,
	};
}
/**
 * Match the 2 words, but more permissive than wordsMatch: if there is an additional letter to a word, or a letter missing, we will consider it almost match
 * @param {String} s1 the first string
 * @param {String} s2 the second string
 * @returns {Object} matching information:<ul>
 * <li>nb_words_1: the number of words contained in s1</li>
 * <li>nb_words_2: the number of words contained in s2</li>
 * <li>nb_words1_in_2: the number of words of s1 present in s2 (but not necessarly as an individual word in s2, it may be a part of a word of s2)</li>
 * <li>nb_words2_in_1: the number of words of s2 present in s1 (but not necessarly as an individual word in s1, it may be a part of a word of s1)</li>
 * <li>remaining1: the remaining string of s1 when we remove all words of s2</li>
 * <li>remaining2: the remaining string of s2 when we remove all words of s1</li>
 * </ul>
 */
function wordsAlmostMatch(s1, s2) {
	s1 = s1.latinize().toLowerCase();
	s2 = s2.latinize().toLowerCase();
	var words1 = prepareMatchScore(s1);
	var words2 = prepareMatchScore(s2);
	var words1_in_words2 = 0;
	var words2_in_words1 = 0;
	var rem2 = ""+s2;
	for (var i = 0; i < words1.length; ++i) {
		for (var j = 0; j < words2.length; ++j) {
			if (almostMatching(words1[i], words2[j])) {
				var k = rem2.indexOf(words2[j]);
				if (k >= 0) {
					words1_in_words2++;
					rem2 = rem2.substring(0,k)+rem2.substring(k+words2[j].length);
				}
			}
		}
	}
	var rem1 = ""+s1;
	for (var i = 0; i < words2.length; ++i) {
		for (var j = 0; j < words1.length; ++j) {
			if (almostMatching(words2[i], words1[j])) {
				var k = rem1.indexOf(words1[j]);
				if (k >= 0) {
					words2_in_words1++;
					rem1 = rem1.substring(0,k)+rem1.substring(k+words1[j].length);
				}
			}
		}
	}
	return {
		nb_words_1:words1.length,
		nb_words_2:words2.length,
		nb_words1_in_2:words1_in_words2,
		nb_words2_in_1:words2_in_words1,
		remaining1: rem1,
		remaining2: rem2
	};
}
/**
 * Match two strings, but allowing spaces to make a difference: 'aabb' will be matching 'aa bb'
 * @param {String} s1 the first string
 * @param {String} s2 the second string
 * @returns {Boolean} true if the 2 strings are matching
 */
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
/**
 * Compute a matching score. See matchScorePrepared for details
 * @param {String} ref the reference string
 * @param {String} needle the string to search in the reference
 * @returns {Number} the score between 0 and 100
 */
function matchScore(ref, needle) {
	return matchScorePrepared(ref, prepareMatchScore(ref), needle, prepareMatchScore(needle));
}
/**
 * Prepare a string for matchScorePrepared: convert the string into a list of words
 * @param {String} s the string
 * @returns {Array} the list of words
 */
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
/**
 * Compute a matching score, between 0 and 100.
 * If the strings are exactly the same, 100 is returned.
 * If the reference string starts with the needle, 95 is returned.
 * If needle is contained in the reference, 93 is returned.
 * Else, we compute word by word, and return a score between 0 and 90, where<ul>
 * <li>If a word is completely matching, it is considered as 1 point</li>
 * <li>If a word in the reference starts with a word of needle, it is considered as 0.75 point</li>
 * <li>If a word in the needle is contained in a word of the reference, it is 0.5 point</li>
 * <li>The total of points is divided by the number of words in the reference</li>
 * <li>Then multiply by 90 to get a score between 0 and 90.</li> 
 * </ul>
 * @param {String} ref teh reference string
 * @param {Array} ref_words the words in the reference string
 * @param {String} needle the string to search
 * @param {Array} needle_words the words in needle
 * @returns {Number} the score between 0 and 100
 */
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
/**
 * Check if 2 strings are almost matching: ignore case and accents, ignore parts inside parenthesis, ignore if the difference is only 1 letter.
 * @param {String} s1 the first string
 * @param {String} s2 the second string
 * @returns {Boolean} true if the 2 strings are almost matching
 */
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
 * @param {Number} figure number
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
 * @param {Object} object the object
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