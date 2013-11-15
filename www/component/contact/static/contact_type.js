if (typeof require != 'undefined')
	require('editable_cell.js');

function contact_type(contact_type, contact_type_name, table_join, join_key, join_value, contacts, can_edit, can_add, can_remove, ontypechanged, onready) {
	this.table = document.createElement("TABLE");
	this.table.appendChild(this.colgroup = document.createElement("COLGROUP"));
	this.colgroup.appendChild(this.col1 = document.createElement("COL"));
	this.colgroup.appendChild(this.col2 = document.createElement("COL"));
	this.table.appendChild(this.thead = document.createElement("THEAD"));
	this.table.appendChild(this.tbody = document.createElement("TBODY"));
	this.table.appendChild(this.tfoot = document.createElement("TFOOT"));
	this.getContacts = function() {
		return contacts;
	};
	this.onchange = new Custom_Event();
	var t=this;
	if (can_add) {
		var td_foot_1 = document.createElement('td');
		var td_foot_2 = document.createElement('td');
		var tr_foot = document.createElement('tr');
		td_foot_1.style.paddingRight = "5px";
		td_foot_2.innerHTML = "Add " + contact_type_name;
		td_foot_2.style.cursor = 'pointer';
		td_foot_2.style.fontStyle ='italic';
		td_foot_2.style.color = "#808080";
		td_foot_2.style.paddingLeft = '5px';
		td_foot_2.style.whiteSpace = 'nowrap';
		td_foot_2.onclick = function(){t.dialogAddField();};
		tr_foot.appendChild(td_foot_1);
		tr_foot.appendChild(td_foot_2);
		this.tfoot.appendChild(tr_foot);
	}
	
	this.addContact = function(contact) {
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
		var edit = null;
		if (join_value == -1) {
			// new
			this.createCategoryField(td_category, contact);
			var input = document.createElement("INPUT");
			input.type = 'text';
			input.maxLength = 100;
			div_data.appendChild(input);
			input.value = contact.contact;
			input.onchange = function() {
				contact.contact = input.value;
				t.onchange.fire(t);
			};
			require("autoresize_input.js",function(){ autoresize_input(input,5); });
		} else if(can_edit){
			/*Manage the category Field*/
			this.createCategoryField(td_category, contact);
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
					error_dialog("You must enter at least one visible character");
					return this.contact;
				}
			};
			div_data.appendChild(div);
		}
		else{
			div_data.innerHTML = contact.contact;
			this.createCategoryField(td_category, contact);
		}
		if (join_value == -1 || can_remove){
			var div_remove = document.createElement('div');
			div_remove.style.display = 'inline-block';
			this.addRemoveButton(contact,div_remove,edit);
			td_data.appendChild(div_remove);
		}
	};
	
	/**
	 * @method dialogAddField
	 * Creates the input_dialog displayed when clicking on the add contact button
	 * The input the dialog will check that the given data is visible
	 * The input_dialog created will call the addField method
	 * @param contact_type {string} can be "email", "phone" or "IM"
	 * @param contact_type_name {string} the text displayed in the header of the input_dialog
	 */
	this.dialogAddField = function (){
		input_dialog(theme.icons_16.question,
			"Add a new "+contact_type_name,
			"Enter the new "+contact_type_name,
			"",
			100,
			function(text){
				if(text.checkVisible()) return;
				else return "You must enter at least one visible character";
			},
			function(text){
				if(text) t.addField({type: contact_type, sub_type:"Work", contact: text});
			}
		);
	};
	
	/**
	 * @method addField
	 * Add the field in the database, updates the result object, and fianlly updates the displayed table
	 * @param text {string} the new contact
	 */
	this.addField = function (contact){
		if (join_value != -1) {
			/*Update the database*/
			service.json("contact","add_contact",{table:table_join,column:join_key,key:join_value,type:contact.type, contact:contact.contact, sub_type:contact.sub_type},function(res){
				if (!res) return;
				/*Update the result object*/
				var l = contacts.length;
				contact.id = res.id;
				contacts[l] = contact;
				/*Update the table*/
				t.addContact(contacts[l]);
				t.onchange.fire(t);
			});
		} else {
			/*Update the result object*/
			var l = contacts.length;
			contact.id = -1;
			contacts[l] = contact;
			/*Update the table*/
			t.addContact(contacts[l]);
			t.onchange.fire(t);
		}
	};
	
	/**
	 * @method addRemoveButton
	 * Will add a removeButton to the given container
	 * @param container
	 * @param contact
	 */
	this.addRemoveButton = function (contact, container, edit){
		var remove_button = document.createElement('img');
		remove_button.src = theme.icons_16.remove;
		remove_button.onmouseover = function(e){this.src = theme.icons_16.remove_black; stopEventPropagation(e);};
		remove_button.onmouseout = function(e){this.src = theme.icons_16.remove; stopEventPropagation(e);};
		remove_button.style.cursor = 'pointer';
		remove_button.style.verticalAlign = 'bottom';
		remove_button.onclick = function(){
			if (edit)
				edit.editable_field.unedit();
			confirm_dialog("Are you sure you want to remove this "+contact_type_name+"?", function(text){if(text) t.removeContact(contact);});
		};
		container.appendChild(remove_button);
	};
	
	/**
	 * @method removeContact
	 * Remove a contact from the database, from the result object, and from the displayed table
	 * @param contact
	 */
	this.removeContact = function (contact){
		if (join_value != -1) {
			service.json("data_model","remove_row",{table:"Contact", row_key:contact.id}, function(res){
				if (!res) return;
				for (var i = 0; i < t.tbody.childNodes.length; ++i)
					if (t.tbody.childNodes[i].contact == contact)
						t.tbody.removeChild(t.tbody.childNodes[i]);
				contacts.remove(contact);
				t.onchange.fire(t);
				setTimeout(function(){if (ontypechanged) ontypechanged();},1);
			});
		} else {
			for (var i = 0; i < t.tbody.childNodes.length; ++i)
				if (t.tbody.childNodes[i].contact == contact)
					t.tbody.removeChild(t.tbody.childNodes[i]);
			contacts.remove(contact);
			t.onchange.fire(t);
			setTimeout(function(){if (ontypechanged) ontypechanged();},1);
		}
	};
	
	/**
	 * @method createCategoryField
	 * Create the category column in the displayed table
	 * @param container the one which will contain the category field
	 * @param contact
	 */
	this.createCategoryField = function (container,contact){
		this.context = null;
		container.innerHTML = contact.sub_type;
		container.style.cursor = "pointer";
		container.onclick = function(){t.addContext(container,contact);};
		setTimeout(function(){if (ontypechanged) ontypechanged();},1);
	};
	
	/**
	 * @method addContext
	 * Create the context_menu displayed below the category field after clicking
	 * @param container
	 * @param contact
	 */
	this.addContext = function(container,contact){
		require('context_menu.js',function(){
			if(!t.context){
				t.context = new context_menu();
				t.context.onclose = function() {t.context = null;};
			}
			t.context.clearItems();
			t.setContext(container, "Work", contact);
			t.setContext(container, "Home", contact);
			t.setContext(container, "Custom", contact);
			
			t.context.showBelowElement(container);
		});
	};
	
	/**
	 * @method setContext
	 * Add an item to the category context_menu
	 * @param container the one which contains the category field
	 * @param {string} data the value of the item
	 * @param contact
	 * In the custom case, an input field is created
	 */
	this.setContext = function(container, data, contact){
		var item = document.createElement('div');
		item.innerHTML = data;
		
		if(contact.sub_type == data) item.style.fontWeight ='bold';
		if(data == "Custom"){
			var input = document.createElement("INPUT");
			input.type = 'text';
			input.maxLength = 10;
			input.size = 10;
			item.appendChild(input);
			t.context.onclose = function(){
				if(input.value.checkVisible()){
					t.saveSubType(contact, input.value.uniformFirstLetterCapitalized(),container);
				}
			};
			input.onkeypress = function(e){var ev = getCompatibleKeyEvent(e);
									if(ev.isEnter) t.context.hide();
								};
		}
		else{
			item.onclick = function(){
				t.saveSubType(contact,data,container);
			};
		}
		item.className = 'context_menu_item';
		t.context.addItem(item);
		if(data == "Custom") item.onclick = null;
	};
	
	/**
	 * @method saveSubType
	 * Method called by the items of the category context menu on click
	 * Update the database, the result object and the displayed table
	 * @param contact the contact to update
	 * @param sub_type the updated one
	 * @param container the one which contains the category field
	 */
	this.saveSubType = function(contact, sub_type,container){
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
			t.addContact(contacts[i]);
		if (onready) onready(t);
	});
}