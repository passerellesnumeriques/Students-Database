function autocomplete(container, provider, min_chars, default_message, onselect, menu_delay) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (!menu_delay) menu_delay = 300;
	var t=this;

	this.to = null;
	
	this.getInputValue = function() {
		return t.input.default_message ? "" : t.input.value;
	};
	
	this._autoFill = function(val) {
		var items = provider(val);
		if (items.length == 0) {
			if (t.context) t.context.hide();
			t.context = null;
		}
		require('context_menu.js',function(){
			if(!t.context){
				t.context = new context_menu();
				t.context.onclose = function() { t.context = null; };
			}
			t.context.clearItems();
			for (var i = 0; i < items.length; ++i) {
				var div = document.createElement('div');
				div.className = 'context_menu_item';
				if (items[i].html) {
					if (typeof items[i].html == 'string')
						div.innerHTML = items[i].html;
					else
						div.appendChild(items[i].html);
				} else
					div.appendChild(document.createTextNode(items[i].text));
				div.item = items[i];
				div.onclick = function(){onselect(this.item);t.input.value=this.item.text;};
				t.context.addItem(div);
			}
			t.context.showBelowElement(t.input);
		});
	};
	
	this._init = function() {
		this.input = document.createElement('input');
		this.input.type = 'text';
		this.input.onkeypress = function(e){
			var ev = getCompatibleKeyEvent(e);
			if (ev.isArrowDown) {
				// TODO
			} else if (ev.isArrowUp) {
				// TODO
			} else if (ev.isEnter) {
				// TODO
			}
			t.input.default_message = false;
			if(t.to){window.clearTimeout(t.to);}
			t.to = setTimeout(function(){
				var val = t.input.value;
				val = val.toLowerCase();
				if(val.length >= min_chars){t._autoFill(val);}
			},menu_delay);
		};
		this.input.value = default_message;
		this.input.style.fontStyle = "italic";
		this.input.style.color = "#808080";
		this.input.default_message = true;
		this.input.onclick = function(){ if (t.input.default_message) { t.input.value = ""; t.input.style.fontStyle = ""; t.input.style.color = ""; t.input.default_message = false; } };
		this.input.onblur = function(){ if (t.input.value == "") { t.input.value = default_message; t.input.style.fontStyle = "italic"; t.input.style.color = "#808080"; t.input.default_message = true; } };
		container.appendChild(this.input);
	};
	this._init();

}