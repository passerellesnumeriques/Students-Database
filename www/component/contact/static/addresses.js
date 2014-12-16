if (typeof require != 'undefined') {
	require("address_text.js");
	require("contact_objects.js");
}

/**
 * UI Control to display a list of addresses, that can be edited depending on the given rights
 * @param {Element} container where to put this control
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
	this.onchange.add_listener(function() {
		layout.changed(container);
	});
	
	/** Create the table that contains the addresses */
	this._createTable = function() {
		container.appendChild(this.table = document.createElement("table"));
		this.table.style.backgroundColor = "white";
		this.table.appendChild(this.thead = document.createElement("thead"));
		this.table.appendChild(this.tbody = document.createElement("tbody"));
		this.table.appendChild(this.tfoot = document.createElement("tfoot"));
		this.table.style.borderSpacing = "0px";
		
		if (header) {
			this.table.style.border = "1px solid #808080";
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
		} else
			this.thead.style.display = "none";
		for(var i = 0; i < this.addresses.length; i++)
			this._createAddressRow(this.addresses[i]);
		
		if (can_add){
			var td_foot_1 = document.createElement('td'); td_foot_1.style.padding = "0px";
			var td_foot_2 = document.createElement('td'); td_foot_2.style.padding = "0px";
			var tr_foot = document.createElement('tr');
			var button = document.createElement("BUTTON");
			td_foot_2.appendChild(button);
			button.className = "flat small";
			button.innerHTML = "Add Address";
			button.style.fontStyle ='italic';
			button.style.color = "#808080";
			button.style.fontSize = "8pt";
			button.style.whiteSpace = 'nowrap';
			button.onclick = function(ev){
				t.createAddress();
				stopEventPropagation(ev);
				return false;
			};
			tr_foot.appendChild(td_foot_1);
			tr_foot.appendChild(td_foot_2);
			this.tfoot.appendChild(tr_foot);
		} else
			this.tfoot.style.display = "none";
		
		layout.changed(container);
		if (onready) onready(this);
	};
	
	/** Add a new address
	 * @param {PostalAddress} address the new postal address
	 * @param {Boolean} is_new if true, the popup dialog to edit the address will be automatically displayed
	 */
	this.addAddress = function(address) {
		t._createAddressRow(address);
		t.addresses.push(address);
		t.onchange.fire(t);
	};
	/** Add a new address
	 * @param {PostalAddress} address the new postal address
	 * @param {Boolean} is_new if true, the popup dialog to edit the address will be automatically displayed
	 */
	this._createAddressRow = function(address, is_new){
		var tr = document.createElement("tr");
		tr.address = address;
		var td_category = document.createElement("td");
		td_category.style.textAlign = 'right';
		td_category.style.color = "#808080";
		td_category.style.verticalAlign = "middle";
		td_category.style.paddingLeft = "5px";
		td_category.style.paddingRight = '5px';
		tr.appendChild(td_category);
		var td_data = document.createElement("td");
		td_data.style.whiteSpace = "nowrap";
		var div_data = document.createElement("div");
		div_data.style.display = 'inline-block';
		div_data.style.verticalAlign = "middle";
		td_data.appendChild(div_data);
		tr.appendChild(td_category);
		tr.appendChild(td_data);
		require("address_text.js",function() {
			var create = function(address) {
				var text = new address_text(address);
				div_data.appendChild(text.element);
				if (type_id == null || type_id < 0 || can_edit) {
					div_data.onmouseover = function() {
						text.element.style.textDecoration = 'underline';
						text.element.style.cursor = 'pointer';
						text.element.border = "1px solid #C0C0F0";
					};
					div_data.onmouseout = function () {
						text.element.style.textDecoration = '';
						text.element.style.cursor = '';
					};
					div_data.address = address;
					div_data.onclick = function(ev,is_new) {
						if (ev) stopEventPropagation(ev);
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
											updatePostalAddress(t.addresses[i], edit.address);
											t.onchange.fire(t);
											break;
										}
									var end = function() {
										p.close();
										div_data.removeChild(text.element);
										create(edit.address);
									};
									if (type_id != null && type_id > 0) {
										service.json("data_model","save_entity",{
											table: "PostalAddress",
											key: edit.address.id,
											lock: lock_id,
											field_country: edit.address.country_id,
											field_geographic_area: edit.address.geographic_area ? edit.address.geographic_area.id : null,
											field_street: edit.address.street,
											field_street_number: edit.address.street_number,
											field_building: edit.address.building,
											field_unit: edit.address.unit,
											field_additional: edit.address.additional,
											field_lat: edit.address.lat,
											field_lng: edit.address.lng,
											unlock:true
										},function(res) {
											if (!res) { p.unfreeze(); return; }
											window.databaselock.removeLock(lock_id);
											end();
										});
									} else
										end();
								},function() {
									if (is_new) t.removeAddress(address);
									return true;
								});
								p.show();
							};
							if (type_id != null && type_id > 0) {
								service.json("data_model", "lock_row", {
									table: "PostalAddress",
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
					if (is_new) { is_new = false; div_data.onclick(null,true); }
				}
				layout.changed(div_data);
			};
			create(address);
		});
		t._createCategoryField(td_category,address);
		if(type_id == null || type_id < 0 || can_remove){
			var div_remove = document.createElement('div');
			div_remove.style.display = 'inline-block';
			div_remove.style.verticalAlign = "middle";
			this._addRemoveButton(address,div_remove);
			td_data.appendChild(div_remove);
		}
		this.tbody.appendChild(tr);
		layout.changed(this.tbody);
	};
	
	/** Called when the user clicks on "Add address". */
	this.createAddress = function(){
		require("contact_objects.js", function() {
			var address = new PostalAddress(-1,null,null,null,null,null,null,null,type == 'people' ? "Home" : "Office");
			t.createAndAddAddress(address);
		});
	};
	
	/**
	 * Create the address in database (if type_id > 0), and display it
	 * @param {PostalAddress} address the address to add
	 */
	this.createAndAddAddress = function(address) {
		if (type_id != null && type_id > 0) {
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
				t._createAddressRow(address, true);
			});
		} else {
			/* Update the result object */
			var l = t.addresses.length;
			t.addresses[l] = address;
			/* Update the table */
			t._createAddressRow(address, true);
		}
	};
	
	/** Add the remove button to the address row
	 * @param {PostalAddress} address the address
	 * @param {Element} container where to put the button
	 */
	this._addRemoveButton = function (address, container){
		var remove_button = document.createElement('img');
		remove_button.src = header ? theme.icons_16.remove : theme.icons_10.remove;
		remove_button.title = "Remove this address";
		if (header) {
			remove_button.onmouseover = function(e){this.src = theme.icons_16.remove_black; stopEventPropagation(e);};
			remove_button.onmouseout = function(e){this.src = theme.icons_16.remove; stopEventPropagation(e);};
		}
		remove_button.style.cursor = 'pointer';
		// remove_button.style.verticalAlign = 'bottom';
		remove_button.onclick = function(ev){
			confirm_dialog("Are you sure you want to remove this address?", function(yes){if(yes) t.removeAddress(address);});
			stopEventPropagation(ev);
			return false;
		};
		container.appendChild(remove_button);
	};
	
	/** Called when the user clicks on the remove button and confirms.
	 * @param {PostalAddress} address the address to remove
	 */
	this.removeAddress = function (address){
		if (type_id != null && type_id > 0) {
			/* Remove from database */
			service.json("data_model","remove_row",{table:"PostalAddress", row_key:address.id}, function(res){
				if(!res) return;
				for (var i = 0; i < t.tbody.childNodes.length; ++i)
					if (t.tbody.childNodes[i].address == address)
						t.tbody.removeChild(t.tbody.childNodes[i]);
				t.addresses.remove(address);
				t.onchange.fire(t);
				layout.changed(t.tbody);
			});
		} else {
			for (var i = 0; i < t.tbody.childNodes.length; ++i)
				if (t.tbody.childNodes[i].address == address)
					t.tbody.removeChild(t.tbody.childNodes[i]);
			t.addresses.remove(address);
			t.onchange.fire(t);
			layout.changed(t.tbody);
		}
	};
	
	/** Add a 'type' field (Work, Home...) to an address row
	 * @param {Element} container where to put it
	 * @param {PostalAddress} address the associated address object
	 */
	this._createCategoryField = function (container,address){
		var span = document.createElement("SPAN");
		span.appendChild(document.createTextNode(address.address_type));
		container.appendChild(span);
		span.style.cursor = "pointer";
		span.onclick = function(ev){
			t._showAddressTypeContextMenu(span,address);
			stopEventPropagation(ev);
			return false;
		};
	};
	
	/**
	 * Create the context_menu displayed below the category field after clicking
	 * @param {Element} container the category field: the menu will be displayed below this element
	 * @param {PostalAddress} address the associated address object
	 */
	this._showAddressTypeContextMenu = function(container,address){
		require(['context_menu.js','contact_objects.js'],function(){
			showAddressTypeMenu(container,type,address.address_type,true,function(new_type) {
				t._saveSubType(address,new_type,container);
			});
		});
	};
	
	/**
	 * Method called by the items of the category context menu on click Update the database, the result object and the
	 * displayed table
	 * @param {PostalAddress} address the address to update
	 * @param {String} address_type the new value
	 * @param {Element} container the one which contains the category field
	 */
	this._saveSubType = function(address, address_type, container){
		if (type_id != null && type_id > 0) {
			service.json("data_model","save_entity",{table:"PostalAddress",key:address.id, field_address_type:address_type, lock:-1},function(res){
				if(!res) return;
				container.removeAllChildren();
				container.appendChild(document.createTextNode(address_type));
				address.address_type = address_type;
				t.onchange.fire(t);
				layout.changed(container);
			});
		} else {
			container.removeAllChildren();
			container.appendChild(document.createTextNode(address_type));
			address.address_type = address_type;
			t.onchange.fire(t);
			layout.changed(container);
		}
	};
	
	this._createTable();
}
