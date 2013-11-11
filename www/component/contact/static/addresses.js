if (typeof require != 'undefined') {
	require("address_text.js");
}

function addresses(container, header, table_join, join_key, join_value, addresses, can_edit, can_add, can_remove, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	this.addresses = addresses;
	var t=this;
	
	this.getAddresses = function() {
		return this.addresses;
	};
	this.onchange = new Custom_Event();
	
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
			td_foot_2.onclick = function(){t.createAddress();};
			tr_foot.appendChild(td_foot_1);
			tr_foot.appendChild(td_foot_2);
			this.tfoot.appendChild(tr_foot);
		}
		
		if (onready) onready(this);
	};
	
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
				if (join_value == -1 || can_edit) {
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
								var copy = object_copy(div_data.address);
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
									if (join_value != -1) {
										service.json("data_model","save_entity",{
											table: "Postal_address",
											key: edit.address.id,
											lock: lock_id,
											field_country: edit.address.country,
											field_geographic_area: edit.address.geographic_area ? edit.address.geographic_area.id : null,
											field_street: edit.address.street_name,
											field_street_number: edit.address.street_number,
											field_building: edit.address.building,
											field_unit: edit.address.unit,
											field_additional: edit.address.additional
										},function(res) {
											if (!res) { p.unfreeze(); return; }
											window.database_locks.remove_lock(lock_id);
											end();
										});
									} else
										end();
								});
								p.show();
							};
							if (join_value != -1) {
								service.json("data_model", "lock_row", {
									table: "Postal_address",
									row_key: div_data.address.id
								}, function(res) {
									if (!res) return;
									window.database_locks.add_lock(res.lock);
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
		t.createCategoryField(td_category,address);
		if(join_value == -1 || can_remove){
			var div_remove = document.createElement('div');
			div_remove.style.display = 'inline-block';
			this.addRemoveButton(address,div_remove);
			td_data.appendChild(div_remove);
		}
		this.tbody.appendChild(tr);
	};
	
	this.createAddress = function(){
		if (join_value != -1) {
			service.json("contact","add_address",{
				table:table_join,
				column:join_key,
				key:join_value,
				country:null,
				geographic_area:null,
				street:null,
				street_number:null,
				building:null,
				unit:null,
				additional:null, 
				address_type:"Work"
			},function(res){
				if(!res) return;
				/* Update the result object */
				var l = t.addresses.length;
				t.addresses[l] = {id:res.id, country:null, geographic_area:null, street_name:null, street_number:null, building:null, unit:null, additional:null, address_type:"Work"};
				/* Update the table */
				t.addAddress(t.addresses[l], true);
				t.onchange.fire(t);
			});
		} else {
			/* Update the result object */
			var l = t.addresses.length;
			t.addresses[l] = {id:-1, country:null, geographic_area:null, street_name:null, street_number:null, building:null, unit:null, additional:null, address_type:"Work"};
			/* Update the table */
			t.addAddress(t.addresses[l], true);
			t.onchange.fire(t);
		}
	};
	
	this.addRemoveButton = function (address, container){
		var remove_button = document.createElement('img');
		remove_button.src = theme.icons_16.remove;
		remove_button.onmouseover = function(e){this.src = theme.icons_16.remove_black; stopEventPropagation(e);};
		remove_button.onmouseout = function(e){this.src = theme.icons_16.remove; stopEventPropagation(e);};
		remove_button.style.cursor = 'pointer';
		// remove_button.style.verticalAlign = 'bottom';
		remove_button.onclick = function(){
			confirm_dialog("Are you sure you want to remove this address?", function(text){if(text) t.removeAddress(address);});
		};
		container.appendChild(remove_button);
	};
	
	this.removeAddress = function (address){
		if (join_value != -1) {
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
	
	this.createCategoryField = function (container,address){
		this.context = null;
		container.innerHTML = address.address_type;
		container.style.cursor = "pointer";
		container.onclick = function(){t.addContext(container,address);};
	};
	
	/**
	 * @method addContext Create the context_menu displayed below the category
	 *         field after clicking
	 * @param container
	 * @param index
	 *            the index in the result object of the address to which this
	 *            category is linked
	 */
	this.addContext = function(container,address){
		require('context_menu.js',function(){
			if(!t.context){
				t.context = new context_menu();
				t.context.onclose = function() {t.context = null;};
			}
			t.context.clearItems();
			t.setContext(container, "Work", address);
			t.setContext(container, "Home", address);
			t.setContext(container, "Custom", address);
			
			t.context.showBelowElement(container);
		});
	};
	
	/**
	 * @method setContext Add an item to the category context_menu
	 * @param container
	 *            the one which contains the category field
	 * @param {string}
	 *            data the value of the item
	 * @param address
	 *            In the custom case, an input field is created
	 */
	this.setContext = function(container, data, address){
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
					t.saveSubType(address, input.value.uniformFirstLetterCapitalized(),container);
				}
			};
			input.onkeypress = function(e){var ev = getCompatibleKeyEvent(e);
									if(ev.isEnter) t.context.hide();
								};
		}
		else{
			item.onclick = function(){
				t.saveSubType(address,data,container);
			};
		}
		item.className = "context_menu_item";
		t.context.addItem(item);
		if(data == "Custom") item.onclick = null;
	};
	
	/**
	 * @method saveSubType Method called by the items of the category context
	 *         menu on click Update the database, the result object and the
	 *         displayed table
	 * @param address_id
	 *            the id of the contact to update
	 * @param sub_type
	 *            the updated one
	 * @param container
	 *            the one which contains the category field
	 */
	this.saveSubType = function(address, sub_type,container){
		if (join_value != -1) {
			service.json("data_model","save_entity",{table:"Postal_address",key:address.id, field_address_type:sub_type, lock:-1},function(res){
				if(!res) return;
				container.innerHTML = sub_type;
				address.address_type = sub_type;
				t.onchange.fire(t);
			});
		} else {
			container.innerHTML = sub_type;
			address.address_type = sub_type;
			t.onchange.fire(t);
		}
	};
	
	this._createTable();
}
