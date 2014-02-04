if (typeof require != 'undefined') {
	require("address_text.js");
}

/**
 * UI Control to display a list of addresses, that can be edited depending on the given rights
 * @param {DOMNode} container where to put this control
 * @param {Boolean} header if true, an header with icon and title is displayed at the beginning
 * @param {String} type either "people" or "organization"
 * @param {Number} type_id people id, or organization id, or -1 for a new entity (if new, addresses are only kept in memory but not synchronized with the database)
 * @param {Array} addresses array of PostalAddress
 * @param {Boolean} can_edit true if the user can edit an existing address
 * @param {Boolean} can_add true if the user can add a new address
 * @param {Boolean} can_remove true if the user can remove an existing address
 * @param {Function} onready called when the control is ready
 */
function addresses(container, header, type, type_id, addresses, can_edit, can_add, can_remove, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	this.addresses = addresses;
	var t=this;
	
	/** Returns the list of addresses currently displayed
	 * @returns {Array} list of PostalAddress
	 */
	this.getAddresses = function() {
		return this.addresses;
	};
	/** Called each time a modification is done (edit, add, or remove) */
	this.onchange = new Custom_Event();
	
	/** Create the table that contains the addresses */
	this._createTable = function() {
		container.appendChild(this.table = document.createElement("table"));
		this.table.appendChild(this.thead = document.createElement("thead"));
		this.table.appendChild(this.tbody = document.createElement("tbody"));
		this.table.appendChild(this.tfoot = document.createElement("tfoot"));
		
		if (header) {
			this.table.style.border = "1px solid #808080";
			this.table.style.borderSpacing = "0";
			this.table.style.marginBottom = "3px";
			setBorderRadius(this.table, 5, 5, 5, 5, 5, 5, 5, 5);
			var tr_head = document.createElement("tr");
			var th_head = document.createElement("th");
			th_head.colSpan = 2;
			th_head.style.textAlign = "left";
			th_head.style.padding = "2px 5px 2px 5px";
			th_head.innerHTML = "<img src='/static/contact/address_16.png' style='vertical-align:bottom;padding-right:3px'/>Address";
			th_head.style.backgroundColor = "#F0F0F0";
			setBorderRadius(th_head, 5, 5, 5, 5, 0, 0, 0, 0);
			tr_head.appendChild(th_head);
			this.thead.appendChild(tr_head);
		}
		for(var i = 0; i < this.addresses.length; i++)
			this.addAddress(this.addresses[i]);
		
		if (can_add){
			var td_foot_1 = document.createElement('td');
			var td_foot_2 = document.createElement('td');
			var tr_foot = document.createElement('tr');
			td_foot_2.innerHTML = "Add Address";
			td_foot_2.style.cursor = 'pointer';
			td_foot_2.style.fontStyle ='italic';
			td_foot_2.style.color = "#808080";
			td_foot_2.style.whiteSpace = "nowrap";
			td_foot_2.onclick = function(){t.createAddress();};
			tr_foot.appendChild(td_foot_1);
			tr_foot.appendChild(td_foot_2);
			this.tfoot.appendChild(tr_foot);
		}
		
		if (onready) onready(this);
	};
	
	/** Add a new address
	 * @param {PostalAddress} address the new postal address
	 * @param {Boolean} is_new if true, the popup dialog to edit the address will be automatically displayed
	 */
	this.addAddress = function(address, is_new){
		var tr = document.createElement("tr");
		tr.address = address;
		var td_category = document.createElement("td");
		td_category.style.textAlign = 'right';
		td_category.style.color = "#808080";
		td_category.style.verticalAlign = "top";
		td_category.style.paddingLeft = "5px";
		td_category.style.paddingRight = '5px';
		tr.appendChild(td_category);
		var td_data = document.createElement("td");
		var div_data = document.createElement("div");
		div_data.style.display = 'inline-block';
		td_data.appendChild(div_data);
		tr.appendChild(td_category);
		tr.appendChild(td_data);
		require("address_text.js",function() {
			var create = function(address) {
				var text = new address_text(address);
				div_data.appendChild(text.element);
				if (type_id == -1 || can_edit) {
					div_data.onmouseover = function() {
						text.element.style.textDecoration = 'underline';
						text.element.cursor = 'pointer';
						text.element.border = "1px solid #C0C0F0";
					};
					div_data.onmouseout = function () {
						text.element.style.textDecoration = '';
						text.element.cursor = '';
					};
					div_data.address = address;
					div_data.onclick = function() {
						require(["popup_window.js","edit_address.js"], function() {
							var show_popup = function(lock_id) {
								var content = document.createElement("DIV");
								content.style.padding = "5px";
								var copy = objectCopy(div_data.address);
								var edit = new edit_address(content, copy);
								var p = new popup_window("Edit Postal Address", "/static/contact/address_16.png", content);
								p.addOkCancelButtons(function() {
									p.freeze();
									for (var i = 0; i < t.addresses.length; ++i)
										if (t.addresses[i] == div_data.address) {
											t.addresses[i] = edit.address;
											t.onchange.fire(t);
											break;
										}
									var end = function() {
										p.close();
										div_data.removeChild(text.element);
										create(edit.address);
									};
									if (type_id != -1) {
										service.json("data_model","save_entity",{
											table: "Postal_address",
											key: edit.address.id,
											lock: lock_id,
											field_country: edit.address.country,
											field_geographic_area: edit.address.geographic_area ? edit.address.geographic_area.id : null,
											field_street: edit.address.street,
											field_street_number: edit.address.street_number,
											field_building: edit.address.building,
											field_unit: edit.address.unit,
											field_additional: edit.address.additional
										},function(res) {
											if (!res) { p.unfreeze(); return; }
											window.databaselock.removeLock(lock_id);
											end();
										});
									} else
										end();
								});
								p.show();
							};
							if (type_id != -1) {
								service.json("data_model", "lock_row", {
									table: "Postal_address",
									row_key: div_data.address.id
								}, function(res) {
									if (!res) return;
									window.databaselock.addLock(res.lock);
									show_popup(res.lock);
								});
							} else
								show_popup();
						});
					};
					if (is_new) { is_new = false; div_data.onclick(); }
				}
			};
			create(address);
		});
		t._createCategoryField(td_category,address);
		if(type_id == -1 || can_remove){
			var div_remove = document.createElement('div');
			div_remove.style.display = 'inline-block';
			this._addRemoveButton(address,div_remove);
			td_data.appendChild(div_remove);
		}
		this.tbody.appendChild(tr);
	};
	
	/** Called when the user clicks on "Add address". */
	this.createAddress = function(){
		var address = new PostalAddress(-1,null,null,null,null,null,null,null,"Work");
		if (type_id != -1) {
			service.json("contact","add_address",{
				type:type,
				type_id:type_id,
				address:address
			},function(res){
				if(!res) return;
				/* Update the result object */
				address.id = res.id;
				var l = t.addresses.length;
				t.addresses[l] = address;
				/* Update the table */
				t.addAddress(address, true);
				t.onchange.fire(t);
			});
		} else {
			/* Update the result object */
			var l = t.addresses.length;
			t.addresses[l] = address;
			/* Update the table */
			t.addAddress(address, true);
			t.onchange.fire(t);
		}
	};
	
	/** Add the remove button to the address row
	 * @param {PostalAddress} address the address
	 * @param {DOMNode} container where to put the button
	 */
	this._addRemoveButton = function (address, container){
		var remove_button = document.createElement('img');
		remove_button.src = theme.icons_16.remove;
		remove_button.onmouseover = function(e){this.src = theme.icons_16.remove_black; stopEventPropagation(e);};
		remove_button.onmouseout = function(e){this.src = theme.icons_16.remove; stopEventPropagation(e);};
		remove_button.style.cursor = 'pointer';
		// remove_button.style.verticalAlign = 'bottom';
		remove_button.onclick = function(){
			confirm_dialog("Are you sure you want to remove this address?", function(yes){if(yes) t.removeAddress(address);});
		};
		container.appendChild(remove_button);
	};
	
	/** Called when the user clicks on the remove button and confirms.
	 * @param {PostalAddress} address the address to remove
	 */
	this.removeAddress = function (address){
		if (type_id != -1) {
			/* Remove from database */
			service.json("data_model","remove_row",{table:"Postal_address", row_key:address.id}, function(res){
				if(!res) return;
				for (var i = 0; i < t.tbody.childNodes.length; ++i)
					if (t.tbody.childNodes[i].address == address)
						t.tbody.removeChild(t.tbody.childNodes[i]);
				t.addresses.remove(address);
				t.onchange.fire(t);
			});
		} else {
			for (var i = 0; i < t.tbody.childNodes.length; ++i)
				if (t.tbody.childNodes[i].address == address)
					t.tbody.removeChild(t.tbody.childNodes[i]);
			t.addresses.remove(address);
			t.onchange.fire(t);
		}
	};
	
	/** Add a 'type' field (Work, Home...) to an address row
	 * @param {DOMNode} container where to put it
	 * @param {PostalAddress} address the associated address object
	 */
	this._createCategoryField = function (container,address){
		this.context = null;
		container.innerHTML = address.address_type;
		container.style.cursor = "pointer";
		container.onclick = function(){t._showAddressTypeContextMenu(container,address);};
	};
	
	/**
	 * Create the context_menu displayed below the category field after clicking
	 * @param {DOMNode} container the category field: the menu will be displayed below this element
	 * @param {PostalAddress} address the associated address object
	 */
	this._showAddressTypeContextMenu = function(container,address){
		require('context_menu.js',function(){
			if(!t.context){
				t.context = new context_menu();
				t.context.onclose = function() {t.context = null;};
			}
			t.context.clearItems();
			t._addAddressTypeToContextMenu(container, "Work", address);
			t._addAddressTypeToContextMenu(container, "Home", address);
			t._addAddressTypeToContextMenu(container, "Custom", address);
			
			t.context.showBelowElement(container);
		});
	};
	
	/**
	 * Add an item to the category context_menu
	 * @param {DOMNode} container the one which contains the category field
	 * @param {String} data the value of the item
	 * @param {PostalAddress} address the associated address object
	 */
	this._addAddressTypeToContextMenu = function(container, data, address){
		var item = document.createElement('div');
		item.innerHTML = data;
		
		if(address.address_type == data) item.style.fontWeight ='bold';
		if(data == "Custom"){
			var input = document.createElement("INPUT");
			input.type = 'text';
			input.maxLength = 10;
			input.size = 10;
			item.appendChild(input);
			t.context.onclose = function(){
				if(input.value.checkVisible()){
					t._saveSubType(address, input.value.uniformFirstLetterCapitalized(),container);
				}
			};
			input.onkeypress = function(e){var ev = getCompatibleKeyEvent(e);
									if(ev.isEnter) t.context.hide();
								};
		}
		else{
			item.onclick = function(){
				t._saveSubType(address,data,container);
			};
		}
		item.className = "context_menu_item";
		t.context.addItem(item);
		if(data == "Custom") item.onclick = null;
	};
	
	/**
	 * Method called by the items of the category context menu on click Update the database, the result object and the
	 * displayed table
	 * @param {PostalAddress} address the address to update
	 * @param {String} address_type the new value
	 * @param {DOMNode} container the one which contains the category field
	 */
	this._saveSubType = function(address, address_type, container){
		if (type_id != -1) {
			service.json("data_model","save_entity",{table:"Postal_address",key:address.id, field_address_type:address_type, lock:-1},function(res){
				if(!res) return;
				container.innerHTML = address_type;
				address.address_type = address_type;
				t.onchange.fire(t);
			});
		} else {
			container.innerHTML = address_type;
			address.address_type = address_type;
			t.onchange.fire(t);
		}
	};
	
	this._createTable();
}
