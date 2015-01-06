if (typeof require != 'undefined')
	require("context_menu.js");

/**
 * A control which looks like a SELECT, but in which we can put any HTML in every option
 * @param {Element} container where to put the select
 */
function select(container) {
	var t = this;
	if (typeof container == 'string') container = document.getElementById(container);
	
	/** List of options */
	this.options = [];
	/** {Function} if specified, called when the value changed */
	this.onchange = null;
	/** {Function} if specified, called just before the value change */
	this.onbeforechange = null;
	/** Current value */
	this.value = null;
	/** Maximum width of this control */
	this._max_width = 0;
	
	/** Get the element of this select
	 * @returns {Element} the element
	 */
	this.getHTMLElement = function() { return this._div; };
	
	/** Add an option
	 * @param {String} value the value
	 * @param {Element} html element to display
	 */
	this.add = function(value, html) {
		var item = document.createElement("DIV");
		item.innerHTML = html;
		item.value = value;
		item.style.whiteSpace = "nowrap";
		item.className = "context_menu_item";
		item.onclick = function() { t.select(this.value); };
		this.options.push({value:value,html:html,item:item});
		if (this.options.length == 1) this.select(value);
		var temp = document.createElement("DIV");
		temp.style.position = "absolute";
		temp.className = "context_menu_item";
		temp.style.whiteSpace = "nowrap";
		temp.style.top = "-10000px";
		temp.innerHTML = html;
		document.body.appendChild(temp);
		var t=this;
		setTimeout(function() {
			var w = getWidth(temp,[]);
			if (w > t._max_width) {
				t._max_width = w;
				t._htmlContainer.style.width = w+"px";
			}
			document.body.removeChild(temp);
		},1);
	};
	
	/** Set the selected item
	 * @param {String} value value of the item to select
	 */
	this.select = function(value) {
		// if (this.onbeforechange && !this.onbeforechange(...)) return;
		// if(fire_onchange == null)
			// fire_onchange = true;
		if(value != this.value){
			var change = function() {
				for (var i = 0; i < t.options.length; i++)
					if (t.options[i].value == value) {
						t.value = value;
						t._htmlContainer.innerHTML = t.options[i].html;
						if (t.onchange) t.onchange();
						// alert("after onchange");
						break;
					}
			};
			if (!this.onbeforechange) change();
			else this.onbeforechange(this.value, value, function(){ change(); });
		} // else nothing to do
	};
	
	/** Get the current value
	 * @returns {String} the value
	 */
	this.getSelectedValue = function() {
		return this.value;
	};
	
	/** Initialize the control */
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
		this._button = document.createElement("TD"); tr.appendChild(this._button);
		this._button.style.height = "100%";
		this._button.style.border = "1px solid #808080";
		this._button.style.padding = "0px";
		this._button.innerHTML = "<table style='height:100%;border-collapse:collapse;border-spacing:0'><tr><td valign=middle align=center style='padding:0px'><img src='"+getScriptPath("select.js")+"button.gif'/></td></tr></table>";
		setBackgroundGradient(this._button, "vertical", [{pos:0,color:"#FFFFFF"},{pos:33,color:"#FFFFFF"},{pos:100,color:"#A0A0A0"}]);
		container.appendChild(this._div);
		this._div.style.cursor = "pointer";
		var t=this;
		this._div.onclick = function() {
			require("context_menu.js", function() {
				var menu = new context_menu();
				for (var i = 0; i < t.options.length; ++i)
					menu.addItem(t.options[i].item);
				menu.showBelowElement(t._div, true);
			});
		};
	};
	this._init();
}