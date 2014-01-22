function parse_color(color) {
	if (color.charAt(0) == '#')
		return parse_hex_color(color.substring(1));
	if (color.startsWith("rgb(") || color.startsWith("rgba("))
		return parse_rgb_color(color.substring(4, color.indexOf(')')));
}

function parse_hex_color(hex) {
	return [
	  parseInt(hex.substring(0,2),16),
	  parseInt(hex.substring(2,4),16),
	  parseInt(hex.substring(4,6),16),
	  1
	];
}

function parse_rgb_color(rgb) {
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

function color_darker(color, amount) {
	var c = [color[0],color[1],color[2],color[3]];
	c[0] -= amount; if (c[0] < 0) c[0] = 0;
	c[1] -= amount; if (c[1] < 0) c[1] = 0;
	c[2] -= amount; if (c[2] < 0) c[2] = 0;
	return c;
}

function color_string(color) {
	if (color.length == 3 || color[3] == 1)
		return "rgb("+color[0]+","+color[1]+","+color[2]+")";
	return "rgba("+color[0]+","+color[1]+","+color[2]+","+color[3]+")";
}

function color_between(color_from, color_to, percent) {
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