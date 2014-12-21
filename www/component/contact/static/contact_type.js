if (typeof require != 'undefined') {
	require('editable_cell.js');
	require('contact_objects.js');
}
/**
 * UI Control to display a list of contacts of a given type (email, phone or IM)
 * @param {String} contact_type type of contact (email, phone, or IM)
 * @param {String} contact_type_name how to display the contact_type
 * @param {String} owner_type either "people" or "organization"
 * @param {Number} owner_id owner id (people id, or organization id) or -1 for a new one
 * @param {Array} contacts list of Contact
 * @param {Boolean} can_edit indicates if the user can edit an existing contact
 * @param {Boolean} can_add indicates if the user can add a new contact to the list, attached to the owner
 * @param {Boolean} can_remove indicates if the user can remove an existing contact
 * @param {Boolean} small if true we will use icons of 10x10 instead of 16x16, to take less space
 * @param {Function} ontypechanged called when the sub_type of a contact is changed
 * @param {Function} onready called when the UI control is ready
 */
function contact_type(contact_type, contact_type_name, owner_type, owner_id, contacts, can_edit, can_add, can_remove, small, ontypechanged, onready) {
	/** table containing all contacts */
	this.table = document.createElement("TABLE");
	this.table.style.backgroundColor = "white";
	this.table.style.borderSpacing = "0px";
	this.table.appendChild(this.colgroup = document.createElement("COLGROUP"));
	/** colgroup epement of the table, allowing to specify fixed column width */ 
	this.colgroup.appendChild(this.col1 = document.createElement("COL"));
	this.colgroup.appendChild(this.col2 = document.createElement("COL"));
	this.table.appendChild(this.thead = document.createElement("THEAD"));
	this.table.appendChild(this.tbody = document.createElement("TBODY"));
	this.table.appendChild(this.tfoot = document.createElement("TFOOT"));
	/** Return the list of Contact objects */ 
	this.getContacts = function() {
		return contacts;
	};
	/** Called each time something is changed in the contacts, a contact is added or removed */
	this.onchange = new Custom_Event();
	var t=this;
	if (can_add) {
		var td_foot_1 = document.createElement('td'); td_foot_1.style.padding = "0px";
		var td_foot_2 = document.createElement('td'); td_foot_2.style.padding = "0px";
		var tr_foot = document.createElement('tr');
		td_foot_1.style.paddingRight = "5px";
		var button = document.createElement("BUTTON");
		td_foot_2.appendChild(button);
		button.className = "flat small";
		button.innerHTML = "Add " + contact_type_name;
		button.style.fontStyle ='italic';
		button.style.color = "#808080";
		button.style.fontSize = "8pt";
		button.style.whiteSpace = 'nowrap';
		button.onclick = function(ev){
			t.dialogAddContact();
			stopEventPropagation(ev);
			return false;
		};
		tr_foot.appendChild(td_foot_1);
		tr_foot.appendChild(td_foot_2);
		this.tfoot.appendChild(tr_foot);
	} else
		this.tfoot.style.display = "none";
	
	/**
	 * Add a contact
	 * @param {Contact} contact the contact to add
	 */
	this._createContactRow = function(contact) {
		var tr = document.createElement("tr");
		tr.contact = contact;
		var td_category = document.createElement("td");
		td_category.style.textAlign = 'right';
		td_category.style.paddingRight = '5px';
		td_category.style.paddingLeft = '5px';
		td_category.style.color = "#808080";
		var td_data = document.createElement("td");
		td_data.style.paddingLeft = '5px';
		td_data.style.whiteSpace = 'nowrap';
		var div_data = document.createElement("div");
		div_data.style.display = 'inline-block';
		td_data.appendChild(div_data);
		tr.appendChild(td_category);
		tr.appendChild(td_data);
		this.tbody.appendChild(tr);
		layout.changed(this.tbody);
		var edit = null;
		if (owner_id == null || owner_id < 0) {
			// new
			this._createCategoryField(td_category, contact);
			var input = document.createElement("INPUT");
			input.type = 'text';
			input.maxLength = 100;
			div_data.appendChild(input);
			input.value = contact.contact;
			input.onchange = function() {
				contact.contact = input.value;
				t.onchange.fire(t);
			};
			require("input_utils.js",function(){ inputAutoresize(input,5); });
		} else if(can_edit){
			/*Manage the category Field*/
			this._createCategoryField(td_category, contact);
			/*Manage the data field*/
			var div = document.createElement("div");
			div.style.display = 'inline-block';
			edit = new editable_cell(div,"Contact","contact",contact.id, 'field_text', {max_length:100,min_length:1}, contact.contact);
			edit.contact = contact.contact;
			edit.onsave = function(text){
				if(text.checkVisible()){
					this.contact = text;
					t.onchange.fire(t);
					return text;
				}
				else{
					errorDialog("You must enter at least one visible character");
					return this.contact;
				}
			};
			div_data.appendChild(div);
		}
		else{
			div_data.innerHTML = contact.contact;
			this._createCategoryField(td_category, contact);
		}
		if (owner_id == null || owner_id < 0 || can_remove){
			var div_remove = document.createElement('div');
			div_remove.style.display = 'inline-block';
			this.addRemoveButton(contact,div_remove,edit);
			td_data.appendChild(div_remove);
		}
	};
	
	/**
	 * Creates the inputDialog displayed when clicking on the add contact button
	 * The input the dialog will check that the given data is visible
	 * The inputDialog created will call the createContact method
	 */
	this.dialogAddContact = function (){
		inputDialog(theme.icons_16.question,
			"Add a new "+contact_type_name,
			"Enter the new "+contact_type_name,
			"",
			100,
			function(text){
				if(text.checkVisible()) return;
				else return "You must enter at least one visible character";
			},
			function(text){
				require("contact_objects.js",function(){
					if(text) t.createContact(new Contact(-1, contact_type, "?", text));
				});
			}
		);
	};
	
	/**
	 * Add the contact in the database (if owner_id > 0), and update the display
	 * @param {Contact} contact the new contact
	 */
	this.createContact = function (contact){
		if (owner_id != null && owner_id > 0) {
			/*Update the database*/
			service.json("contact","add_contact",{owner_type:owner_type,owner_id:owner_id,contact:contact},function(res){
				if (!res) return;
				/*Update the result object*/
				var l = contacts.length;
				contact.id = res.id;
				contacts[l] = contact;
				/*Update the table*/
				t._createContactRow(contacts[l]);
				t.onchange.fire(t);
			});
		} else {
			/*Update the result object*/
			var l = contacts.length;
			contact.id = -1;
			contacts[l] = contact;
			/*Update the table*/
			t._createContactRow(contacts[l]);
			t.onchange.fire(t);
		}
	};
	
	/**
	 * Will add a removeButton to the given container
	 * @param {Contact} contact the contact associated
	 * @param {Element} container where to put the button
	 * @param {editable_cell} edit the editable cell or null if not editable
	 */
	this.addRemoveButton = function (contact, container, edit){
		var remove_button = document.createElement('img');
		remove_button.src = small ? theme.icons_10.remove : theme.icons_16.remove;
		remove_button.title = "Remove this contact";
		if (!small) {
			remove_button.onmouseover = function(e){this.src = theme.icons_16.remove_black; stopEventPropagation(e);};
			remove_button.onmouseout = function(e){this.src = theme.icons_16.remove; stopEventPropagation(e);};
		}
		remove_button.style.cursor = 'pointer';
		remove_button.style.verticalAlign = 'bottom';
		remove_button.onclick = function(ev){
			if (edit)
				edit.editable_field.unedit();
			confirmDialog("Are you sure you want to remove this "+contact_type_name+"?", function(text){if(text) t.removeContact(contact);});
			stopEventPropagation(ev);
			return false;
		};
		container.appendChild(remove_button);
	};
	
	/**
	 * Remove a contact from the owner, and update the display
	 * @param {Contact} contact the contact to remove
	 */
	this.removeContact = function (contact){
		if (owner_id != null && owner_id > 0) {
			service.json("data_model","remove_row",{table:"Contact", row_key:contact.id}, function(res){
				if (!res) return;
				for (var i = 0; i < t.tbody.childNodes.length; ++i)
					if (t.tbody.childNodes[i].contact == contact)
						t.tbody.removeChild(t.tbody.childNodes[i]);
				contacts.remove(contact);
				t.onchange.fire(t);
				setTimeout(function(){if (ontypechanged) ontypechanged();},1);
				layout.changed(t.tbody);
			});
		} else {
			for (var i = 0; i < t.tbody.childNodes.length; ++i)
				if (t.tbody.childNodes[i].contact == contact)
					t.tbody.removeChild(t.tbody.childNodes[i]);
			contacts.remove(contact);
			t.onchange.fire(t);
			setTimeout(function(){if (ontypechanged) ontypechanged();},1);
			layout.changed(t.tbody);
		}
	};
	
	/**
	 * Create the category column in the displayed table
	 * @param {Element} container the one which will contain the category field
	 * @param {Contact} contact the associated contact
	 */
	this._createCategoryField = function (container,contact){
		this.context = null;
		container.style.whiteSpace = "nowrap";
		container.innerHTML = contact.sub_type;
		if(can_edit){
			container.style.cursor = "pointer";
			container.onclick = function(ev){t._showCategoryContextMenu(container,contact);stopEventPropagation(ev);};
			setTimeout(function(){if (ontypechanged) ontypechanged();},1);
		}
	};
	
	/**
	 * Create the context_menu displayed below the category field after clicking
	 * @param {Element} container the category field: the context menu will be displayed below it
	 * @param {Contact} contact the associated contact
	 */
	this._showCategoryContextMenu = function(container,contact){
		require('contact_objects.js',function(){
			showContactTypeMenu(container,contact.type,contact.sub_type,true,function(new_type) {
				t._saveSubType(contact, new_type, container);
			});
		});
	};
	
	/**
	 * Method called by the items of the category context menu on click
	 * Update the database, the result object and the displayed table
	 * @param {Contact} contact the contact to update
	 * @param {String} sub_type the updated one
	 * @param {Element} container the one which contains the category field
	 */
	this._saveSubType = function(contact, sub_type, container){
		if (contact.id != -1) {
			service.json("data_model","save_entity",{table:"Contact",key:contact.id, field_sub_type:sub_type, lock:-1},function(res){
				if(!res) return;
				container.innerHTML = sub_type;
				/*Update the result object*/
				contact.sub_type = sub_type;
				t.onchange.fire(t);
				setTimeout(function(){if (ontypechanged) ontypechanged();},1);
			});
		} else {
			container.innerHTML = sub_type;
			contact.sub_type = sub_type;
			t.onchange.fire(t);
			setTimeout(function(){if (ontypechanged) ontypechanged();},1);
		}
	};
	
	require('editable_cell.js',function(){
		for (var i = 0; i < contacts.length; ++i)
			t._createContactRow(contacts[i]);
		if (onready) onready(t);
	});
}