/**
 * Convert a string into a color [r,g,b,a]
 * @param {String} color the string to convert
 * @returns {Array} the color [r,g,b,a]
 */
function parseColor(color) {
	if (color.charAt(0) == '#')
		return parseHexColor(color.substring(1));
	if (color.startsWith("rgb(") || color.startsWith("rgba("))
		return parseRGBColor(color.substring(4, color.indexOf(')')));
}

/**
 * Parse a string containing an hexadecimal representation of the color RRGGBB (no leading #)
 * @param {String} hex the string RRGGBB
 * @returns {Array} the color [r,g,b,a] with a always 1
 */
function parseHexColor(hex) {
	return [
	  parseInt(hex.substring(0,2),16),
	  parseInt(hex.substring(2,4),16),
	  parseInt(hex.substring(4,6),16),
	  1
	];
}

/**
 * Parse a string with format r,g,b[,a]
 * @param {String} rgb the string
 * @returns {Array} the color [r,g,b,a] if a is not in the string, it will be 1 
 */
function parseRGBColor(rgb) {
	var c = [];
	while (rgb.length > 0) {
		var i = rgb.indexOf(',');
		if (i >= 0) {
			c.push(parseInt(rgb.substring(0,i)));
			rgb = rgb.substring(i+1);
		} else {
			c.push(parseInt(rgb));
			break;
		}
	}
	while (c.length < 3) c.push(0);
	if (c.length == 3) c.push(1);
	return c;
}

/** Make the given color darker
 * @param {Array} color a color [r,g,b]
 * @param {Number} amount how much darker
 * @returns {Array} the resulting color
 */
function colorDarker(color, amount) {
	var c = [color[0],color[1],color[2],color[3]];
	c[0] -= amount; if (c[0] < 0) c[0] = 0;
	c[1] -= amount; if (c[1] < 0) c[1] = 0;
	c[2] -= amount; if (c[2] < 0) c[2] = 0;
	return c;
}
/** Make a color lighter
 * @param {Array} color a color [r,g,b]
 * @param {Number} amount how much lighter
 * @returns {Array} the resulting color
 */
function colorLighter(color, amount) {
	var c = [color[0],color[1],color[2],color[3]];
	c[0] += amount; if (c[0] > 255) c[0] = 255;
	c[1] += amount; if (c[1] > 255) c[1] = 255;
	c[2] += amount; if (c[2] > 255) c[2] = 255;
	return c;
}
/** Make a color darker or lighter, depending if it is already dark or light
 * @param {Array} color the color [r,g,b] to modify
 * @param {Number} amount how much darker or loghter
 */
function colorDarkerOrLighter(color, amount) {
	var tot = color[0]+color[1]+color[2];
	if (tot < 128*3)
		colorLighter(color, amount);
	else
		colorDarker(color, amount);
}

/** Make a CSS string from the given color
 * @param {Array} color [r,g,b,a] with a optional
 * @returns {String} the string (hexadecimal representation if no alpha, or rgba(...) if alpha)
 */
function colorToString(color) {
	if (color.length == 3 || color[3] == 1)
		return "#"+_2Hex(color[0])+_2Hex(color[1])+_2Hex(color[2]);
	return "rgba("+color[0]+","+color[1]+","+color[2]+","+color[3]+")";
}

/** Compare 2 colors
 * @param {Array} c1 color1
 * @param {Array} c2 color2
 * @returns {Boolean} true if they are the same
 */
function colorEquals(c1, c2) {
	if (c1[0] != c2[0]) return false;
	if (c1[1] != c2[1]) return false;
	if (c1[2] != c2[2]) return false;
	if (c1.length == 3) {
		if (c2.length == 3) return true;
		return c2[3] == 1;
	}
	if (c2.length == 3) return c1[3] == 1;
	return c1[3] == c2[3];
}

/**
 * Create a color between 2 coloes
 * @param {Array} color_from starting color (0%)
 * @param {Array} color_to ending color (100%)
 * @param {Number} percent percentage between color_from and color_end
 * @returns {Array} the resulting color
 */
function colorBetween(color_from, color_to, percent) {
	var col = [
		Math.floor(color_from[0]+(color_to[0]-color_from[0])*percent/100),
		Math.floor(color_from[1]+(color_to[1]-color_from[1])*percent/100),
		Math.floor(color_from[2]+(color_to[2]-color_from[2])*percent/100)
	];
	if (col[0] > 255) col[0] = 255;
	if (col[1] > 255) col[1] = 255;
	if (col[2] > 255) col[2] = 255;
	return col;
}