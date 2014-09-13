function inputAutoresizeUpdater() {
	this.knowledge = [];
	this.to_update = [];
	this.update = function(input) {
		this.to_update.push(input);
		if (this.to_update.length == 1) setTimeout(function() { if (window._input_autoresize_updater) window._input_autoresize_updater._doUpdates(); },1);
	};
	this._doUpdates = function() {
		if (window.closing) return;
		
		for (var i = 0; i < this.to_update.length; ++i) {
			var input = this.to_update[i];
			input.mirror.removeAllChildren();
			input.mirror.appendChild(document.createTextNode(input.value));
		}
		var widths = [];
		for (var i = 0; i < this.to_update.length; ++i)
			widths.push(getWidth(this.to_update[i].mirror, this.knowledge));
		for (var i = 0; i < this.to_update.length; ++i) {
			var input = this.to_update[i];
			var w = widths[i];
			if (!input._last_width) input._last_width = 0;
			if (input._min_size < 0) {
				// must fill the width of its container
				input.style.width = "100%";
				if (w < 15) w = 15;
				input.style.minWidth = w+"px";
			} else {
				var min = input._min_size ? input._min_size * 10 : 15;
				if (w < min) w = min;
				input.style.width = w+"px";
				if (input._last_width != w) {
					layout.changed(input);
					if (input.onresize) input.onresize();
				}
				input._last_width = w;
			}
		}
		for (var i = 0; i < this.to_update.length; ++i) {
			var input = this.to_update[i];
			if (input._min_size < 0) {
				if (input.offsetWidth != input._last_width) {
					layout.changed(input);
					if (input.onresize) input.onresize();
					input._last_width = input.offsetWidth;
				}
			}
		}
	};
};
window._input_autoresize_updater = new inputAutoresizeUpdater();

function inputAutoresize(input, min_size) {
	input._min_size = min_size;
	input.mirror = document.createElement("SPAN");
	input.ondomremoved(function() {
		if (input.mirror.parentNode)
			input.mirror.parentNode.removeChild(input.mirror);
		input.mirror = null;
		input.autoresize = null;
		input.inputAutoresize_prev_onkeydown = null;
		input.inputAutoresize_prev_onkeyup = null;
		input.inputAutoresize_prev_oninput = null;
		input.inputAutoresize_prev_onpropertychange = null;
		input.inputAutoresize_prev_onchange = null;
	});
	if (input.style) {
		var style = getComputedStyle(input);
		if (input.style.fontSize)
			input.mirror.style.fontSize = input.style.fontSize;
		else if (style.fontSize) input.mirror.style.fontSize = style.fontSize;
		if (input.style.fontWeight)
			input.mirror.style.fontWeight = input.style.fontWeight;
		else if (style.fontWeight) input.mirror.style.fontWeight = style.fontWeight;
		if (input.style.fontFamily)
			input.mirror.style.fontFamily = input.style.fontFamily;
		else if (style.fontFamily) input.mirror.style.fontFamily = style.fontFamily;
	}
	input.mirror.style.position = 'absolute';
	input.mirror.style.whiteSpace = 'pre';
	input.mirror.style.left = '0px';
	input.mirror.style.top = '-10000px';
	input.mirror.style.padding = "2px";
	document.body.appendChild(input.mirror);
	input.onresize = null;
	var update = function() {
		window._input_autoresize_updater.update(input);
	};
	input.inputAutoresize_prev_onkeydown = input.onkeydown;
	input.onkeydown = function(e) { if (this.inputAutoresize_prev_onkeydown) this.inputAutoresize_prev_onkeydown(e); update(); };
	input.inputAutoresize_prev_onkeyup = input.onkeyup;
	input.onkeyup = function(e) { if (this.inputAutoresize_prev_onkeyup) this.inputAutoresize_prev_onkeyup(e); update(); };
	input.inputAutoresize_prev_oninput = input.oninput;
	input.oninput = function(e) { if (this.inputAutoresize_prev_oninput) this.inputAutoresize_prev_oninput(e); update(); };
	input.inputAutoresize_prev_onpropertychange = input.onpropertychange;
	input.onpropertychange = function(e) { if (this.inputAutoresize_prev_onpropertychange) this.inputAutoresize_prev_onpropertychange(e); update(); };
	input.inputAutoresize_prev_onchange = input.onchange;
	input.onchange = function(e) { if (this.inputAutoresize_prev_onchange) this.inputAutoresize_prev_onchange(e); update(); };
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
	input.inputDefaultText_prev_onfocus = input.onfocus; 
	input.onfocus = function(ev) {
		if (is_default) {
			input.value = "";
			input.className = original_class;
		}
		if (this.inputDefaultText_prev_onfocus) this.inputDefaultText_prev_onfocus(ev);
	};
	input.inputDefaultText_prev_onblur = input.onblur;
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
		if (this.inputDefaultText_prev_onblur) this.inputDefaultText_prev_onblur(ev);
		if (input.onchange) input.onchange();
	};
	input.getValue = function() {
		if (is_default) return "";
		return input.value;
	};
	if (document.activeElement == input) input.onfocus(); else input.onblur();
}