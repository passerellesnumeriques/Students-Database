function autocomplete_item(value, text, html) {
	this.value = value;
	this.text = text;
	this.html = html;
}

function autocomplete(container, min_chars, default_message, provider, onselectitem) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;

	this.getInputValue = function() {
		return t.input.default_message ? "" : t.input.value;
	};
	
	this.reset = function() {
		this.input.default_message = true;
		this.input.value = default_message;
		this.input.className = "informative_text";
	};
	
	this._init = function() {
		this.input = document.createElement('input');
		this.input.type = 'text';
		this.input.value = default_message;
		this.input.className = "informative_text";
		this.input.default_message = true;
		this.input.onfocus = function(){ 
			if (t.input.default_message) { 
				t.input.value = ""; t.input.className = ""; t.input.default_message = false; 
				layout.changed(container);
			} else 
				t.input.select();
		};
		this.input.onblur = function(){ 
			setTimeout(function(){
				if (t.input.value == "") { 
					t.input.value = default_message; t.input.className = "informative_text"; t.input.default_message = true; 
					layout.changed(container);
				}; 
				t.menu.hide();
			},100); 
		};
		container.appendChild(this.input);
		
		this.menu = new autocomplete_menu(this, onselectitem);
		
		this._provider_call = false;
		this._provider_recall = false;
		this._provider_last_string = "";
		this.input.onkeyup = function(e){
			var ev = getCompatibleKeyEvent(e);
			if (ev.isArrowDown) t.menu.down();
			else if (ev.isArrowUp) t.menu.up();
			else if (ev.isEnter) t.menu.select();
			t.input.default_message = false;
			if (t._provider_call) t._provider_recall = true;
			else t._call_provider();
		};
		
	};
	
	this._call_provider = function() {
		t._provider_call = true;
		t._provider_recall = false;
		setTimeout(function() {
			if (t.input.value.length < min_chars) {
				t.menu.hide();
				t._provider_last_string = "";
				t._provider_call = false;
				t._provider_recall = false;
				return;
			} else {
				if (t.input.value != t._provider_last_string) {
					t.menu.loading();
					t._provider_last_string = t.input.value;
					t._provider_recall = false;
					provider(t.input.value, function(items) {
						t.menu.reset(items);
						t._provider_call = false;
						if (t._provider_recall)
							t._call_provider();
					});
				} else {
					t._provider_recall = false;
					t._provider_call = false;
				}
			}
		},10);
	};
	
	this._init();
}

function autocomplete_menu(ac, onselectitem) {
	require("animation.js");
	theme.css("context_menu.css");
	var t=this;
	
	this.div = ac.input.ownerDocument.createElement("DIV");
	this.div.style.position = "absolute";
	this.div.className = "context_menu";
	this.div.style.zIndex = 100;
	
	this.loading = function() {
		this.highlighted = -1;
		this.div.removeAllChildren();
		var img = ac.input.ownerDocument.createElement("IMG");
		img.onload = function() {
			t.resize();
		};
		img.src = theme.icons_16.loading;
		this.div.appendChild(img);
		this.resize();
	};
	this.reset = function(items) {
		this.highlighted = -1;
		if (items.length == 0) {
			this.div.innerHTML = "No result";
			this.div.style.fontStyle = 'italic';
			this.resize();
			return;
		}
		this.div.removeAllChildren();
		this.div.style.fontStyle = "";
		for (var i = 0; i < items.length; ++i) {
			var d = ac.input.ownerDocument.createElement("DIV");
			if (typeof items[i].html == 'string')
				d.innerHTML = items[i].html;
			else
				d.appendChild(items[i].html);
			d.item = items[i];
			d.index = i;
			d.className = "context_menu_item";
			d.onmouseover = function() { t.highlight(this.index); };
			d.onclick = function(e) {
				t.item_selected(this.item);
				stopEventPropagation(e);
				return false;
			};
			this.div.appendChild(d);
		}
		this.highlight(0);
		this.resize();
	};
	this.highlighted = -1;
	this.highlight = function(index) {
		if (this.highlighted != -1)
			this.div.childNodes[this.highlighted].className = "context_menu_item";
		if (index != -1)
			this.div.childNodes[index].className = "context_menu_item selected";
		this.highlighted = index;
	};
	this.down = function() {
		if (this.highlighted == -1)
			this.highlight(0);
		else if (this.highlighted < this.div.childNodes.length-1)
			this.highlight(this.highlighted+1);
	};
	this.up = function() {
		if (this.highlighted == -1)
			this.highlight(0);
		else if (this.highlighted > 0)
			this.highlight(this.highlighted-1);
	};
	this.select = function() {
		if (this.highlighted == -1) return;
		this.item_selected(this.div.childNodes[this.highlighted].item);
	};
	this.hide = function() {
		if (typeof animation != 'undefined')
			this.anim = animation.fadeOut(t.div,300,function() {
				t.div.style.visibility = "hidden";
				t.div.style.top = "-10000px";
			});
		else {
			t.div.style.visibility = "hidden";
			t.div.style.top = "-10000px";
		}
	};
	this.resize = function() {
		this.div.style.visibility = "visible";
		setOpacity(this.div, 1);
		this.div.style.overflow = "";
		if (this.anim) {
			animation.stop(this.anim);
			this.anim = null;
		}
		var knowledge = [];
		var w = getWidth(this.div, knowledge);
		var h = getHeight(this.div, knowledge);
		var iw = getWidth(ac.input, knowledge);
		if (w < iw) w = iw;
		var x = absoluteLeft(ac.input);
		var y = absoluteTop(ac.input);
		if (y+ac.input.offsetHeight+h > getWindowFromDocument(ac.input.ownerDocument).getWindowHeight()) {
			// not enough space below
			var space_below = getWindowFromDocument(ac.input.ownerDocument).getWindowHeight()-(y+ac.input.offsetHeight);
			var space_above = y;
			if (space_above > space_below) {
				y = y-h;
				if (y < 0) {
					// not enough space: scroll bar
					y = 0;
					this.div.style.overflowY = 'scroll';
					this.div.style.height = space_above+"px";
				}
			} else {
				// not enough space: scroll bar
				y = y+ac.input.offsetHeight;
				this.div.style.overflowY = 'scroll';
				this.div.style.height = space_below+"px";
			}
		} else {
			// by default, show it below
			y = y+ac.input.offsetHeight;
		}
		if (x+w > getWindowFromDocument(ac.input.ownerDocument).getWindowWidth()) {
			x = getWindowFromDocument(ac.input.ownerDocument).getWindowWidth()-w;
		}
		y += ac.input.ownerDocument.body.scrollTop;
		x += ac.input.ownerDocument.body.scrollLeft;
		this.div.style.top = y+"px";
		this.div.style.left = x+"px";
		if (this.div.parentNode != ac.input.ownerDocument.body)
			ac.input.ownerDocument.body.appendChild(this.div);
	};
	this.item_selected = function(item) {
		ac.input.value = item.text;
		if (onselectitem) onselectitem(item);
		this.hide();
	};
}
