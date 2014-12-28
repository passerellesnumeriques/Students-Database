if (typeof require != 'undefined') {
	require("label.js");
}

/**
 * List of labels
 * @param {String} color CSS color of the labels
 * @param {Array} list the labels: {id,name,editable,removable}
 * @param {Function} onedit function to call when a label needs to be edited
 * @param {Function} onremove function to call when a label is removed
 * @param {Function} add_list_provider function called when the user wants to add a new label: it must return a list of elements which will be displayed in a context_menu
 * @param {Function} onready function called when this widget is ready
 */
function labels(color, list, onedit, onremove, add_list_provider, onready) {
	
	/** DIV containing the labels */
	this.element = document.createElement("DIV");
	this.element.style.display = "inline-block";
	/** List of labels */
	this.list = [];
	var t=this;
	
	/**
	 * Add a label
	 * @param {String} id identifier
	 * @param {String} name text of the label
	 * @param {Boolean} editable indicates if the label can be edited
	 * @param {Boolean} removable indicates if the label can be removed
	 */
	this.addItem = function(id, name, editable, removable) {
		var item = {id:id,name:name,editable:editable};
		item.label = new label(name, color, onedit && editable ? function(l,onedited) {
			onedit(id, onedited);
		} : null, onremove && removable ? function() {
			onremove(id, function() {
				t.element.removeChild(item.label.element);
			});
		} : null);
		this.list.push(item);
		item.label.element.style.marginRight = "5px";
		if (t.addButton)
			this.element.insertBefore(item.label.element, t.addButton);
		else
			this.element.appendChild(item.label.element);
	};
	
	require("label.js",function() {
		for (var i = 0; i < list.length; ++i)
			t.addItem(list[i].id, list[i].name, list[i].editable, list[i].removable);
		
		if (add_list_provider) {
			t.addButton = document.createElement("IMG");
			t.addButton.src = theme.icons_10.add;
			t.addButton.className = "button";
			t.addButton.margin = "0px";
			t.addButton.padding = "1px";
			t.addButton.style.verticalAlign = "bottom";
			t.element.appendChild(t.addButton);
			require("context_menu.js");
			t.addButton.onclick = function() {
				var items = add_list_provider(t);
				if (!items || items.length == 0) return;
				require("context_menu.js",function() {
					var menu = new context_menu();
					for (var i = 0; i < items.length; ++i)
						menu.addItem(items[i]);
					menu.showBelowElement(t.addButton);
				});
			};
		}
		
		if (onready) onready(t);
	});
}