if (typeof require != 'undefined')
	require("context_menu.js");

function select_checkboxes(container) {
	var t = this;
	if (typeof container == 'string') container = document.getElementById(container);
	
	this.options = [];
	this.onchange = null;
	this._max_width = 0;
	
	this.getHTMLElement = function() { return this._div; };
	
	this.add = function(value, html, selected) {
		var item = document.createElement("DIV");
		var cb = document.createElement("INPUT"); cb.type = 'checkbox';
		item.appendChild(cb);
		if (selected) cb.checked = 'checked';
		cb.onchange = function() { t._selectionChanged(); };
		if (typeof html == 'string') {
			var span = document.createElement("SPAN");
			span.innerHTML = html;
			html = span;
		}
		item.appendChild(html);
		item.value = value;
		item.style.whiteSpace = "nowrap";
		item.style.paddingRight = "3px";
		html.onclick = function(event) { cb.checked = cb.checked ? '' : 'checked'; cb.onchange(); stopEventPropagation(event); };
		this.options.push({value:value,item:item,checkbox:cb,html:html});
		var temp = document.createElement("DIV");
		temp.style.position = "absolute";
		temp.style.whiteSpace = "nowrap";
		temp.style.top = "-10000px";
		temp.innerHTML = item.innerHTML;
		document.body.appendChild(temp);
		var t=this;
		setTimeout(function() {
			var w = temp.offsetWidth;
			if (w > t._max_width) {
				t._max_width = w;
				t._htmlContainer.style.width = w+"px";
			}
			document.body.removeChild(temp);
		},1);
	};
	
	this.disable = function() {
		this._htmlContainer.style.backgroundColor = '#D0D0D0';
		this._htmlContainer.style.color = '#606060';
		this._div.onclick = null;
	};
	
	this.getSelection = function() {
		var selection = [];
		for (var i = 0; i < this.options.length; ++i)
			if (this.options[i].checkbox.checked) selection.push(this.options[i].value);
		return selection;
	};
	
	this.setSelection = function(values) {
		for (var i = 0; i < this.options.length; ++i)
			this.options[i].checkbox.checked = values.contains(this.options[i].value) ? "checked" : "";
		this._selectionChanged();
	};
	
	this.selectAll = function() {
		var values = [];
		for (var i = 0; i < this.options.length; ++i) values.push(this.options[i].value);
		this.setSelection(values);
	};
	this.unselectAll = function() {
		this.setSelection([]);
	};
	
	this._selectionChanged = function() {
		var values = [];
		for (var i = 0; i < this.options.length; ++i)
			if (this.options[i].checkbox.checked) values.push(this.options[i]);
		
		this._htmlContainer.removeAllChildren();
		if (values.length == 1)
			this._htmlContainer.innerHTML = values[0].html.nodeType == 1 ? values[0].html.innerHTML : "&nbsp;"+values[0].html.nodeValue+"&nbsp;";
		else if (values.length == this.options.length)
			this._htmlContainer.innerHTML = "&nbsp;All selected&nbsp;";
		else if (values.length > 1)
			this._htmlContainer.innerHTML = "&nbsp;"+values.length+"/"+this.options.length+" values selected&nbsp;";
		this._cb_all.checked = values.length == this.options.length ? "checked" : "";
		if (this.onchange) this.onchange(this);
	};
	
	this.focus = function () {
		this._div.onclick();
	};
	
	this._init = function() {
		this._div = document.createElement("DIV");
		this._div.style.display = 'inline-block';
		this.table = document.createElement("TABLE"); this._div.appendChild(this.table);
		this.table.style.borderCollapse = "collapse";
		this.table.style.borderSpacing = "0px";
		var tr = document.createElement("TR"); this.table.appendChild(tr);
		this._htmlContainer = document.createElement("TD"); tr.appendChild(this._htmlContainer);
		this._htmlContainer.style.padding = "0px";
		this._htmlContainer.style.border = "1px solid #808080";
		this._htmlContainer.style.whiteSpace = "nowrap";
		this._button = document.createElement("TD"); tr.appendChild(this._button);
		this._button.style.height = "100%";
		this._button.style.border = "1px solid #808080";
		this._button.style.padding = "0px";
		this._button.innerHTML = "<table style='height:100%;border-collapse:collapse;border-spacing:0'><tr><td valign=middle align=center style='padding:0px'><img src='"+getScriptPath("select_checkboxes.js")+"button.gif'/></td></tr></table>";
		setBackgroundGradient(this._button, "vertical", [{pos:0,color:"#FFFFFF"},{pos:33,color:"#FFFFFF"},{pos:100,color:"#A0A0A0"}]);
		container.appendChild(this._div);
		this._div.style.cursor = "pointer";

		var t=this;
		
		var temp = document.createElement("DIV");
		temp.innerHTML = "&nbsp;100/100 values selected&nbsp;";
		temp.style.position = "absolute";
		temp.style.whiteSpace = "nowrap";
		temp.style.top = "-10000px";
		document.body.appendChild(temp);
		setTimeout(function() {
			var w = temp.offsetWidth;
			if (w > t._max_width) {
				t._max_width = w;
				t._htmlContainer.style.width = w+"px";
			}
			document.body.removeChild(temp);
		},1);
		
		this._div_all = document.createElement("DIV");
		this._div_all.style.paddingRight = "3px";
		this._div_all.style.borderBottom = "1px solid black";
		this._div_all.style.backgroundColor = "#E0E0FF";
		this._cb_all = document.createElement("INPUT"); this._cb_all.type = "checkbox";
		this._div_all.appendChild(this._cb_all);
		this._div_all.appendChild(document.createTextNode("Select All"));

		this._cb_all.onchange = function() {
			if (this.checked) t.selectAll();
			else t.unselectAll();
		};
		this._div.onclick = function() {
			require("context_menu.js", function() {
				var menu = new context_menu();
				menu.addItem(t._div_all, true);
				for (var i = 0; i < t.options.length; ++i)
					menu.addItem(t.options[i].item, true);
				menu.showBelowElement(t._div, true);
			});
		};
	};
	this._init();
}