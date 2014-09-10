var _generate_id_counter = 0;
/**
 * Generates an unique id. 
 * @returns {String} the generated id
 */
function generateID() {
	return "id"+(_generate_id_counter++);
}

/**
 * Lock the screen by adding a semi-transparent element on top of the window
 * @param onclick called when the user click on the element on top of the window
 * @param content html code or html element to be put in the center of the element
 * @returns the element on top of the window created by this function
 */
function lock_screen(onclick, content) {
	var div = document.getElementById('lock_screen');
	if (div) {
		div.usage_counter++;
		return div;
	}
	div = document.createElement('DIV');
	div.usage_counter = 1;
	div.id = "lock_screen";
	div.style.backgroundColor = "rgba(128,128,128,0.5)";
	div.style.position = "fixed";
	div.style.top = "0px";
	div.style.left = "0px";
	div.style.width = getWindowWidth()+"px";
	div.style.height = getWindowHeight()+"px";
	div.style.zIndex = 10;
	if (onclick)
		div.onclick = onclick;
	if (content)
		set_lock_screen_content(div, content);
	if (typeof animation != 'undefined')
		div.anim = animation.fadeIn(div,200,null,10,100);
	div.listener = function() {
		div.style.width = getWindowWidth()+"px";
		div.style.height = getWindowHeight()+"px";
	};
	listenEvent(window, 'resize', div.listener);
	return document.body.appendChild(div);
}
function set_lock_screen_content(div, content) {
	div.removeAllChildren();
	var table = document.createElement("TABLE"); div.appendChild(table);
	table.style.width = "100%";
	table.style.height = "100%";
	var tr = document.createElement("TR"); table.appendChild(tr);
	var td = document.createElement("TD"); tr.appendChild(td);
	td.style.verticalAlign = 'middle';
	td.style.textAlign = 'center';
	var d = document.createElement("DIV");
	d.className = 'lock_screen_content';
	if (typeof content == 'string')
		d.innerHTML = content;
	else
		d.appendChild(content);
	td.appendChild(d);
}
function set_lock_screen_content_progress(lock_div, total, message, sub_div, onready) {
	theme.css("progress_bar.css");
	require("progress_bar.js", function() {
		var div = document.createElement("DIV");
		div.style.textAlign = "center";
		var span = document.createElement("SPAN");
		span.innerHTML = message;
		div.appendChild(span);
		div.appendChild(document.createElement("BR"));
		var pb = new progress_bar(200, 17);
		pb.element.style.display = "inline-block";
		div.appendChild(pb.element);
		pb.setTotal(total);
		var sub = null;
		if (sub_div) {
			sub = document.createElement("DIV");
			div.appendChild(sub);
		}
		set_lock_screen_content(lock_div, div);
		theme.css("progress_bar.css",function() {
			onready(span, pb, sub);			
		});
	});
}
/**
 * Remove the given element, previously created by using the function lock_screen
 * @param div
 */
function unlock_screen(div) {
	if (!div) div = document.getElementById('lock_screen');
	if (!div) return;
	if (!div.parentNode) return;
	if (typeof div.usage_counter != 'undefined') {
		div.usage_counter--;
		if (div.usage_counter > 0) return;
	}
	unlistenEvent(window, 'resize', div.listener);
	if (typeof animation != 'undefined') {
		div.id = '';
		if (div.anim) animation.stop(div.anim);
		div.anim = null;
		animation.fadeOut(div,200,function(){
			if (div.parentNode == document.body)
				document.body.removeChild(div);				
		},100,0);
	} else if (div.parentNode == document.body)
		document.body.removeChild(div);
}

function LoadingHidder(to_hide) {
	var t=this;
	/** {Element} table containing the loading content in front of the frame */
	this.div = document.createElement("DIV");
	this.div.style.position = "absolute";
	var z = 1;
	var p = to_hide;
	do {
		var style = getComputedStyle(p);
		if (style["z-index"] != "auto") { z = style["z-index"]; break; }
		p = p.parentNode;
	} while (p && p.nodeName != "BODY" && p.nodeName != "HTML");
	this.div.style.zIndex = z;
	this.div.style.backgroundColor = "rgba(128,128,128,0.5)";

	to_hide.ownerDocument.body.appendChild(this.div);
	
	/** Refresh the size and position of the loading, according to the size and position of the frame */
	this._position = function() {
		this.div.style.top = (absoluteTop(to_hide))+"px";
		this.div.style.left = (absoluteLeft(to_hide))+"px";
		this.div.style.width = to_hide.offsetWidth+"px";
		this.div.style.height = to_hide.offsetHeight+"px";
	};

	/** Call the _position function */
	var updater = function() { t._position(); };

	this._removed = false;
	/** Remove */
	this.remove = function() {
		this._removed = true;
		layout.unlistenElementSizeChanged(to_hide, updater);
		if (this.div.parentNode)
			this.div.parentNode.removeChild(this.div);
		this.div = null;
	};
	
	this.setContent = function(content) {
		set_lock_screen_content(this.div, content);
	};
	
	this._position();
	setTimeout(function() {
		if (t._removed) return;
		t._position();
	}, 50);
	layout.listenElementSizeChanged(to_hide, updater);
}

/** Wait for things to be initialized in a frame
 * @param {window} win the window of the frame
 * @param {Function} test tests if it is ready or not (takes the window as parameter, must return true if the frame is ready)
 * @param {Function} onready called when the frame is ready
 * @param {Number} timeout time in milliseconds after which we will not try anymore (if not specified, default is 30 seconds)
 */
function waitFrameReady(win, test, onready, timeout) {
	if (typeof timeout == 'undefined') timeout = 30000;
	if (timeout < 50) return;
	if (!test(win)) { setTimeout(function() { waitFrameReady(win, test, onready, timeout-50); }, 50); return; }
	onready(win);
}
/** Wait for things to be initialized in a frame
 * @param {Element} frame the iframe element
 * @param {Function} test tests if it is ready or not (takes the window as parameter, must return true if the frame is ready)
 * @param {Function} onready called when the frame is ready
 * @param {Number} timeout time in milliseconds after which we will not try anymore (if not specified, default is 30 seconds)
 */
function waitFrameContentReady(frame, test, onready, timeout) {
	if (typeof timeout == 'undefined') timeout = 30000;
	if (timeout < 50) return;
	var win = getIFrameWindow(frame);
	if (!win || !test(win)) { setTimeout(function() { waitFrameContentReady(frame, test, onready, timeout-50); }, 50); return; }
	onready(win);
}

function waitForFrame(frame_name, onready, timeout) {
	if (typeof timeout == 'undefined') timeout = 30000;
	if (timeout < 50) return;
	var frame = findFrame(frame_name);
	if (frame) {
		var win = getIFrameWindow(frame);
		if (win) {
			if (win._page_ready) {
				onready(win);
				return;
			}
		}
	}
	setTimeout(function() { waitForFrame(frame_name, onready, timeout-50); }, 50);
}

if (typeof window.top._current_tooltip == 'undefined')
	window.top._current_tooltip = null;
/** Display a tooltip for the given element, any tooltip currently displayed will be removed.
 * @param {Element} element the HTML element to attach with a tooltip
 * @param {Element|String} content the content of the tooltip
 */
function createTooltip(element, content) {
	if (!content) return;
	if (typeof content == 'string') {
		var div = document.createElement("DIV");
		div.innerHTML = content;
		content = div;
	}
	content.style.position = "absolute";
	var x = absoluteLeft(element);
	var w = element.offsetWidth;
	var ww = getWindowWidth();
	if (x <= ww/2) {
		content.className = "tooltip";
		if (w < 44) {
			x = x-22+Math.floor(w/2);
			if (x < 0) x = 0;
		}
		content.style.left = x+"px";
	} else {
		content.className = "tooltip_right";
		x = (ww-(x+w));
		if (w < 44) {
			x = x-22+Math.floor(w/2);
			if (x >= ww) x = ww-1;
		}
		if (x < 0) {
			x = 0;
			content.className = "tooltip_right tooltip_veryright";
		}
		content.style.right = x+"px";
	}
	content.style.top = (absoluteTop(element)+element.offsetHeight+5)+"px";
	removeTooltip();
	if (typeof animation != 'undefined') {
		content.style.visibility = 'hidden';
		setOpacity(content, 0);
		animation.fadeIn(content, 200);
	} else {
	}
	document.body.appendChild(content);
	element._tooltip = window.top._current_tooltip = content;
	content._element = element;
	element._tooltip_timeout = setTimeout(function (){
		if (window.top._current_tooltip && window.top._current_tooltip == element._tooltip) {
			animation.fadeOut(content, 750, function() {
				if (window.top._current_tooltip && window.top._current_tooltip == element._tooltip)
					removeTooltip();
			});
		}
	},10000);
	element._listener = function() {
		if (window.top._current_tooltip && window.top._current_tooltip == element._tooltip)
			removeTooltip();
	};
	listenEvent(window,'mouseout',element._listener);
}
/** Remove the current tooltip on the window */
function removeTooltip() {
	if (!window.top._current_tooltip) return;
	if (window.top._current_tooltip.parentNode) {
		window.top._current_tooltip.parentNode.removeChild(window.top._current_tooltip);
	}
	unlistenEvent(getWindowFromDocument(window.top._current_tooltip._element.ownerDocument),'mouseout',window.top._current_tooltip._element._listener);
	window.top._current_tooltip._element._tooltip = null;
	window.top._current_tooltip = null;
}
/** Set a tooltip for the given element
 * @param {Element} element the HTML element to attach the tooltip content
 * @param {Element|String} content the content of the tooltip
 */
function tooltip(element, content) {
	require("animation.js");
	element.onmouseover = function() {
		createTooltip(element, content);
	};
	element.onmouseout = function() {
		if (this._tooltip && this._tooltip == window.top._current_tooltip)
			removeTooltip();
		this._tooltip = null;
	};
}

function printContent(container, onready, filename) {
	if (typeof container == 'string') container = document.getElementById(container);
	window.top.popup_frame(theme.icons_16.print, "Print", "/dynamic/application/page/print"+(filename?"?filename="+encodeURIComponent(filename):""), null, 95, 95, function(frame,pop){
		waitFrameContentReady(frame, 
			function(win) {
				return win.printing_ready;
			}, function(win) {
				win.setPrintContent(container, onready);
			}
		);
	});
}