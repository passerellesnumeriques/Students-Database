function inputAutoresizeUpdater() {
	this.knowledge = [];
	this.to_update = [];
	this.timeout = null;
	this.update = function(input) {
		this.to_update.push(input);
		if (!this.timeout) this.timeout = setTimeout(function() { if (window._input_autoresize_updater) window._input_autoresize_updater._doUpdates(); },1);
	};
	this._doUpdates = function() {
		if (window.closing) return;
		this.timeout = null;
		for (var i = 0; i < this.to_update.length; ++i) {
			var input = this.to_update[i];
			if (!input.mirror) {
				this.to_update.splice(i,1);
				i--;
				continue;
			}
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
	this.cleanup = function() {
		this.knowledge = null;
		this.to_update = null;
	};
	if (!window.to_cleanup) window.to_cleanup = [];
	window.to_cleanup.push(this);
};
window._input_autoresize_updater = new inputAutoresizeUpdater();

function inputAutoresize(input, min_size) {
	input._min_size = min_size;
	input.mirror = document.createElement("SPAN");
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
	input.onkeydown = function(e) { if (this.inputAutoresize_prev_onkeydown) this.inputAutoresize_prev_onkeydown(e); if (update) update(); };
	input.inputAutoresize_prev_onkeyup = input.onkeyup;
	input.onkeyup = function(e) { if (this.inputAutoresize_prev_onkeyup) this.inputAutoresize_prev_onkeyup(e); if (update) update(); };
	input.inputAutoresize_prev_oninput = input.oninput;
	input.oninput = function(e) { if (this.inputAutoresize_prev_oninput) this.inputAutoresize_prev_oninput(e); if (update) update(); };
	input.inputAutoresize_prev_onpropertychange = input.onpropertychange;
	input.onpropertychange = function(e) { if (this.inputAutoresize_prev_onpropertychange) this.inputAutoresize_prev_onpropertychange(e); if (update) update(); };
	input.inputAutoresize_prev_onchange = input.onchange;
	input.onchange = function(e) { if (this.inputAutoresize_prev_onchange) this.inputAutoresize_prev_onchange(e); if (update) update(); };
	update();
	input.autoresize = update;
	input.setMinimumSize = function(min_size) {
		last = 0;
		this._min_size = min_size;
		update();
	};
	input.ondomremoved(function() {
		if (input.mirror.parentNode)
			input.mirror.parentNode.removeChild(input.mirror);
		input.mirror = null;
		input.autoresize = null;
		input.setMinimumSize = null;
		input.inputAutoresize_prev_onkeydown = null;
		input.inputAutoresize_prev_onkeyup = null;
		input.inputAutoresize_prev_oninput = null;
		input.inputAutoresize_prev_onpropertychange = null;
		input.inputAutoresize_prev_onchange = null;
		update = null;
		input = null;
	});
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

function InputOver(value, default_text) {
	if (!value) value = "";
	this.container = document.createElement("DIV");
	this.container.style.position = "relative";
	this.span = document.createElement("SPAN");
	if (value.length > 0 || !default_text) {
		this.span.appendChild(document.createTextNode(value));
	} else { 
		this.span.appendChild(document.createTextNode(default_text));
		this.span.style.fontStyle = "italic";
		setOpacity(this.span, 0.5);
	}
	this.container.appendChild(this.span);
	this.container.style.height = "16px";
	this.container.style.paddingLeft = "0px";
	this.container.style.marginRight = "2px";
	this.container.style.paddingTop = "2px";
	this.input = document.createElement("INPUT");
	this.input.style.position = "absolute";
	this.input.style.top = "0px";
	this.input.style.left = "-2px";
	this.input.style.width = "100%";
	this.input.style.padding = "0px";
	if (default_text) this.input.placeholder = default_text;
	this.container.appendChild(this.input);
	this.input.value = value;
	setOpacity(this.input, 0);
	var t=this;
	this.container.onmouseover = function() {
		var style = getComputedStyle(this);
		t.input.style.fontSize = style.fontSize;
		t.input.style.fontWeight = style.fontWeight;
		t.input.style.fontStyle = style.fontStyle;
		setOpacity(t.input, 1);
	};
	this.container.onmouseout = function() {
		if (t.input === document.activeElement) return;
		setOpacity(t.input, 0);
	};
	this.input.onblur = function() {
		setOpacity(t.input, 0);
	};
	this.input.onchange = function() {
		if (!default_text || t.input.value.length > 0) {
			t.span.style.fontStyle = "";
			t.span.childNodes[0].nodeValue = t.input.value;
			setOpacity(t.span, 1);
		} else {
			t.span.style.fontStyle = "italic"; 
			t.span.childNodes[0].nodeValue = default_text;
			setOpacity(t.span, 0.5);
		}
		layout.changed(t.container);
		t.onchange.fire(t);
	};
	this.onchange = new Custom_Event();
}
