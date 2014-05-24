function inputAutoresize(input, min_size) {
	input._min_size = min_size;
	input.mirror = document.createElement("SPAN");
	var style = getComputedStyle(input);
	if (style.fontSize) input.mirror.style.fontSize = style.fontSize;
	if (style.fontWeight) input.mirror.style.fontWeight = style.fontWeight;
	if (style.fontFamily) input.mirror.style.fontFamily = style.fontFamily;
	input.mirror.style.position = 'absolute';
	input.mirror.style.whiteSpace = 'pre';
	input.mirror.style.left = '0px';
	input.mirror.style.top = '-10000px';
	input.mirror.style.padding = "2px";
	document.body.appendChild(input.mirror);
	var last = 0;
	input.onresize = null;
	var update = function() {
		input.mirror.removeAllChildren();
		var s = input.value;
		input.mirror.appendChild(document.createTextNode(s));
		var w = getWidth(input.mirror);
		if (input._min_size < 0) {
			// must fill the width of its container
			input.style.width = "100%";
			if (w < 15) w = 15;
			input.style.minWidth = w+"px";
			if (input.offsetWidth != last) {
				layout.invalidate(input);
				if (input.onresize) input.onresize();
				last = input.offsetWidth;
			}
		} else {
			var min = input._min_size ? input._min_size * 10 : 15;
			if (w < min) w = min;
			input.style.width = w+"px";
			if (last != w) {
				layout.invalidate(input);
				if (input.onresize) input.onresize();
			}
			last = w;
		}
	};
	var prev_onkeydown = input.onkeydown;
	input.onkeydown = function(e) { if (prev_onkeydown) prev_onkeydown(e); update(); };
	var prev_onkeyup = input.onkeyup;
	input.onkeyup = function(e) { if (prev_onkeyup) prev_onkeyup(e); update(); };
	var prev_oninput = input.oninput;
	input.oninput = function(e) { if (prev_oninput) prev_oninput(e); update(); };
	var prev_onpropertychange = input.onpropertychange;
	input.onpropertychange = function(e) { if (prev_onpropertychange) prev_onpropertychange(e); update(); };
	var prev_onchange = input.onchange;
	input.onchange = function(e) { if (prev_onchange) prev_onchange(e); update(); };
	update();
	input.autoresize = update;
	input.setMinimumSize = function(min_size) {
		last = 0;
		this._min_size = min_size;
		update();
	};
}

function inputDefaultText(input, default_text) {
	var is_default = false;
	var original_class = input.className;
	var prev_onfocus = input.onfocus; 
	input.onfocus = function(ev) {
		if (is_default) {
			input.value = "";
			input.className = original_class;
		}
		if (prev_onfocus) prev_onfocus(ev);
	};
	var prev_onblur = input.onblur;
	input.onblur = function(ev) {
		input.value = input.value.trim();
		if (input.value.length == 0) {
			input.className = original_class ? original_class+" informative_text" : "informative_text";
			input.value = default_text;
			is_default = true;
		} else {
			input.className = original_class;
			is_default = false;
		}
		if (prev_onblur) prev_onblur(ev);
		if (input.onchange) input.onchange();
	};
	input.getValue = function() {
		if (is_default) return "";
		return input.value;
	};
	if (document.activeElement == input) input.onfocus(); else input.onblur();
}