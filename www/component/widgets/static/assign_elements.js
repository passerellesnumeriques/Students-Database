if (typeof require != 'undefined') {
	require("section.js");
}
if (typeof theme != 'undefined') {
	theme.css("section.css");
}

/**
 * Display a screen where the user can assign/unassign elements.
 * We can specify the possible assignments, the current assignments, and get the modifications made by the user.
 * @param {Element} container where to put this screen
 * @param {String|null} sections_css CSS class to use for sections
 * @param {String|null} non_assigned_icon URL of the icon to use in the section containing the elements not yet assigned
 * @param {Function} element_display_provider function able to display an element. The function takes 2 parameters:<ul><li>The element to display</li><li>Where to display it</li></ul>
 * @param {Function} onready function called when the screen is ready to be used
 */
function assign_elements(container, sections_css, non_assigned_icon, element_display_provider, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	t.container = container;

	/** An internal identifier that will be used to handle drag'n'drop */
	this.id = generateID();
	/** List of possible assignments */
	this._possible_assignments = [];
	/** Event fired when something changed */
	this.onchange = new Custom_Event();
	/**
	 * Add a possible assignment
	 * @param {String} id identifier of the new possible assignment
	 * @param {String|null} icon URL of the icon to use in the section of the assignment
	 * @param {String} title title to display in the section of the assignment
	 */
	this.addPossibleAssignment = function(id,icon,title) {
		var s = new assign_elements_section(this,icon,title,sections_css, element_display_provider);
		var unassign = document.createElement("BUTTON");
		unassign.innerHTML = "<img src='"+theme.icons_16.remove+"'/> Unassign";
		s.section.addToolBottom(unassign);
		this._sections_container.appendChild(s.section.element);
		var button = document.createElement("BUTTON");
		button.style.display = "block";
		//button.style.width = "100%";
		button.style.textAlign = "right";
		button.appendChild(document.createTextNode(title));
		var img = document.createElement("IMG");
		img.src = theme.icons_16.right;
		img.style.marginLeft = "3px";
		button.appendChild(img);
		this.div_buttons.appendChild(button);
		this._possible_assignments.push({id:id,section:s,button:button});
		layout.changed(container);
		button.onclick = function() {
			var list = t._non_assigned.removeSelectedElements();
			for (var i = 0; i < list.length; ++i) {
				list[i].current = id;
				s.addElement(list[i]);
			}
			if (list.length > 0)
				t.onchange.fire(t);
		};
		unassign.onclick = function() {
			var list = s.removeSelectedElements();
			for (var i = 0; i < list.length; ++i) {
				list[i].current = null;
				t._non_assigned.addElement(list[i]);
			}
			if (list.length > 0)
				t.onchange.fire(t);
		};
	};
	
	/**
	 * Internal function used to move an element from a section to another
	 * @param {String} element_id identifier of the element
	 * @param {assign_elements_section} target section where the element is moved
	 */
	this._move = function(element_id, target) {
		var element = this._non_assigned.removeElementID(element_id);
		if (!element)
			for (var i = 0; i < this._possible_assignments.length; ++i) {
				element = this._possible_assignments[i].section.removeElementID(element_id);
				if (element != null) break;
			}
		var target_id = null;
		for (var i = 0; i < this._possible_assignments.length; ++i)
			if (target == this._possible_assignments[i].section) { target_id = this._possible_assignments[i].id; break; }
		element.current = target_id;
		target.addElement(element);
		t.onchange.fire(t);
	};
	
	/**
	 * Add an element
	 * @param {Object} element the element
	 * @param {String|null} assignment identifier of the initial assignment, or null if not yet assigned
	 * @param {Boolean} moveable indicates if the element can be moved to a different assignment
	 */
	this.addElement = function(element, assignment, moveable) {
		var elem = {element:element,original:assignment,current:assignment,moveable:moveable,id:generateID()};
		var s = this._non_assigned;
		if (assignment)
			for (var i = 0; i < this._possible_assignments.length; ++i)
				if (this._possible_assignments[i].id == assignment) {
					s = this._possible_assignments[i].section;
					break;
				}
		s.addElement(elem);
	};
	
	/**
	 * Get the modifications made by the user.
	 * @returns {Array} list of elements having their assignment changed
	 */
	this.getChanges = function() {
		var changes = [];
		this._non_assigned.getChanges(changes);
		for (var i = 0; i < this._possible_assignments.length; ++i)
			this._possible_assignments[i].section.getChanges(changes);
		return changes;
	};
	
	/**
	 * Indicates changes have been saved, so the function getChanges should not return previous changes anymore
	 */
	this.changesSaved = function() {
		this._non_assigned.changesSaved();
		for (var i = 0; i < this._possible_assignments.length; ++i)
			this._possible_assignments[i].section.changesSaved();
		t.onchange.fire(t);
	};
	
	/**
	 * If some elements cannot be moved, this function specify the text explaining why they cannot be moved to the user
	 * @param {String} html HTML code
	 */
	this.setNonMovableReason = function(html) {
		if (typeof html == 'string') {
			var div = document.createElement("DIV");
			div.innerHTML = "<img src='"+theme.icons_16.info+"' style='vertical-align:bottom'/> "+html;
			html = div;
		}
		this.header.appendChild(html);
	};
	/**
	 * Add a button in the section of non-assigned elements
	 * @param {String|null} icon URL of the icon
	 * @param {String} text the text of the button which can contain HTML code
	 * @param {Function} onclick function to call when the button is clicked
	 */
	this.addUnassignedButton = function(icon, text, onclick) {
		var button = document.createElement("BUTTON");
		button.innerHTML = (icon ? "<img src='"+icon+"'/> " : "")+text;
		button.onclick = onclick;
		this._non_assigned.section.addToolBottom(button);
	};
	/**
	 * Select the given elements in the non-assigned section. If some elements are not found, they are just ignored.
	 * @param {Array} elements the elements to select
	 */
	this.selectUnassigned = function(elements) {
		this._non_assigned.selectElements(elements);
	};
	
	/**
	 * Creation of the screen
	 */
	this._init = function() {
		container.style.display = "flex";
		container.style.flexDirection = "column";
		this.header = document.createElement("DIV");
		this.header.innerHTML = "<img src='"+theme.icons_16.info+"' style='vertical-align:bottom'/> Select elements and assign/unassign them, or drag and drop elements";
		this.header.className = "info_header";
		this.header.style.flex = "none";
		container.appendChild(this.header);
		this._sections_container = document.createElement("DIV");
		this._sections_container.style.flex = "1 1 auto";
		this._sections_container.style.padding = "5px";
		this._sections_container.style.display = "flex";
		this._sections_container.style.flexDirection = "row";
		container.appendChild(this._sections_container);
		this._non_assigned = new assign_elements_section(this,non_assigned_icon, "Non-assigned", sections_css, element_display_provider);
		this._sections_container.appendChild(this._non_assigned.section.element);
		var div = document.createElement("DIV");
		div.style.margin = "0px 8px 0px 3px";
		div.style.alignSelf = "center";
		div.style.flex = "none";
		this.div_buttons = document.createElement("DIV"); div.appendChild(this.div_buttons);
		this.div_buttons.style.backgroundColor = "#FFFFFF";
		setBorderRadius(this.div_buttons, 5,5,5,5,5,5,5,5);
		setBoxShadow(this.div_buttons, 2,2,2,0, "#C0C0C0");
		this.div_buttons.style.padding = "2px";
		this._sections_container.appendChild(div);
		layout.changed(container);
		if (onready) onready(this);
	};
	require(["section.js"], function() {
		t._init();
	});
}
/**
 * Internal class used by assign_elements, which handle a section (either non-assigned elements, or a possible assignment)
 * @param {assign_elements} assign the assign_elements screen
 * @param {String|null} icon URL of the icon
 * @param {String} title title of the section
 * @param {String|null} css CSS class of the section
 * @param {Function} element_display_provider function to display an element
 */
function assign_elements_section(assign,icon,title,css,element_display_provider) {
	var t=this;
	/** List of elements in the section */
	this._elements = [];
	/**
	 * Add an element in the section
	 * @param {Object} element the element to add
	 */
	this.addElement = function(element) {
		var div = document.createElement("DIV");
		div.style.whiteSpace = "nowrap";
		var cb = document.createElement("INPUT");
		cb.type = "checkbox";
		if (!element.moveable) cb.disabled = "disabled";
		div.appendChild(cb);
		var span = document.createElement("SPAN");
		span.style.cursor = "default";
		span.style.paddingLeft = "3px";
		span.style.paddingRight = "3px";
		span.style.whiteSpace = "nowrap";
		element_display_provider(element.element, span);
		if (element.moveable) {
			span.onclick = function() {
				if (cb.checked) cb.checked = ""; else cb.checked = "checked";
				cb.onchange();
			};
			span.draggable = true;
			span.ondragstart = function(event) {
				event.dataTransfer.setData("assign_element_"+assign.id,element.id);
				event.dataTransfer.effectAllowed = "move";
				return true;
			};
		} else
			span.style.color = "#808080";
		div.appendChild(span);
		this.content.appendChild(div);
		this._elements.push({element:element,div:div,cb:cb});
		this._span_nb.innerHTML = this._elements.length;
		cb.onchange = function() {
			if (this.checked && !t.cb.checked) t.cb.checked = "checked";
			if (!this.checked && t.cb.checked) {
				var ok = true;
				for (var i = 0; i < t._elements.length; ++i)
					if (t._elements[i].cb.checked) { ok = false; break; }
				if (ok)
					t.cb.checked = "";
			}
		};
	};
	/**
	 * Remove the elements which are currently selected
	 */
	this.removeSelectedElements = function() {
		var list = [];
		for (var i = 0; i < this._elements.length; ++i) {
			var e = this._elements[i];
			if (!e.cb.checked) continue;
			this.content.removeChild(e.div);
			list.push(e.element);
			this._elements.splice(i,1);
			i--;
		}
		this.cb.checked = "";
		this._span_nb.innerHTML = this._elements.length;
		return list;
	};
	/**
	 * Remove the given element from this section
	 * @param {String} id identifier of the element
	 */
	this.removeElementID = function(id) {
		for (var i = 0; i < this._elements.length; ++i)
			if (this._elements[i].element.id == id) {
				var elem = this._elements[i];
				this.content.removeChild(elem.div);
				this._elements.splice(i,1);
				if (elem.cb.checked) {
					var ok = true;
					for (var i = 0; i < t._elements.length; ++i)
						if (t._elements[i].cb.checked) { ok = false; break; }
					if (ok)
						t.cb.checked = "";
				}
				this._span_nb.innerHTML = this._elements.length;
				return elem.element;
			}
		return null;
	};
	/**
	 * Get the modifications made by the user on the elements contained in this section
	 * @param {Array} changes list to fill with elements which have been modified
	 */
	this.getChanges = function(changes) {
		for (var i = 0; i < t._elements.length; ++i)
			if (t._elements[i].element.current != t._elements[i].element.original)
				changes.push(t._elements[i].element);
	};
	/**
	 * Indicates that changes have been saved
	 */
	this.changesSaved = function() {
		for (var i = 0; i < t._elements.length; ++i)
			t._elements[i].element.original = t._elements[i].element.current;
	};
	/**
	 * Select the given elements. If some elements are not found in this section, they are just ignored.
	 * @param {Array} elements list of elements to select
	 */
	this.selectElements = function(elements) {
		for (var i = 0; i < t._elements.length; ++i)
			if (elements.contains(t._elements[i].element.element))
				t._elements[i].cb.checked = "checked";
			else
				t._elements[i].cb.checked = "";
	};
	/**
	 * Creation of the section
	 */
	this._init = function() {
		var span = document.createElement("DIV");
		this.cb = document.createElement("INPUT");
		this.cb.type = "checkbox";
		this.cb.style.marginRight = "3px";
		this.cb.onchange = function() {
			for (var i = 0; i < t._elements.length; ++i)
				if (t._elements[i].element.moveable)
					t._elements[i].cb.checked = this.checked ? "checked" : "";
		};
		span.appendChild(this.cb);
		span.appendChild(document.createTextNode(title+" ("));
		this._span_nb = document.createElement("SPAN");
		this._span_nb.innerHTML = "0";
		span.appendChild(this._span_nb);
		span.appendChild(document.createTextNode(")"));
		this.content = document.createElement("DIV");
		this.section = new section(icon, span, this.content, false, true, css);
		this.section.element.style.flex = "none";

		this.content.ondragover = function(event) {
			if (event.dataTransfer.types.contains("assign_element_"+assign.id)) {
				var element_id = event.dataTransfer.getData("assign_element_"+assign.id);
				for (var i = 0; i < t._elements.length; ++i)
					if (t._elements[i].element.id == element_id) return true; // same target
				event.dataTransfer.dropEffect = "move";
				event.preventDefault();
				return false;
			}
		};
		this.content.ondragenter = function(event) {
			if (event.dataTransfer.types.contains("assign_element_"+assign.id)) {
				var element_id = event.dataTransfer.getData("assign_element_"+assign.id);
				for (var i = 0; i < t._elements.length; ++i)
					if (t._elements[i].element.id == element_id) return true; // same target
				t.content.style.outline = "1px dotted #808080";
				event.dataTransfer.dropEffect = "move";
				event.preventDefault();
				return true;
			}
		};
		this.content.ondragleave = function(event) {
			t.content.style.outline = "";
		};
		this.content.ondrop = function(event) {
			t.content.style.outline = "";
			var element_id = event.dataTransfer.getData("assign_element_"+assign.id);
			assign._move(element_id,t);
			event.stopPropagation();
			return false;
		};
	};
	this._init();
}