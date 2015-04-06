if (typeof require != 'undefined')
	require("contact_type.js");

/**
 * UI Control to display all contacts of a people or organization
 * @param {Element} container where to display
 * @param {String} owner_type "people" or "organization"
 * @param {Number} owner_id people ID or organization ID
 * @param {Array} contacts list of Contact
 * @param {Object} additional_info additional data to be sent to services
 * @param {Boolean} compact true for a compact display (no title text)
 * @param {Boolean} can_edit indicates if the user can edit an existing contact
 * @param {Boolean} can_add indicates if the user can create a new contact attached to the owner
 * @param {Boolean} can_remove indicates if the user can remove an existing contact
 * @param {Function} onready called when the display is ready
 */
function contacts(container, owner_type, owner_id, contacts, additional_info, compact, can_edit, can_add, can_remove, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	/** Update width of 1rst column, containing the contact sub type, so all rows are aligned */
	t._updateCol1 = function() {
		if (t.email.col1) t.email.col1.style.width = "";
		if (t.phone.col1) t.phone.col1.style.width = "";
		if (t.im.col1) t.im.col1.style.width = "";
		if (t.email.col2) t.email.col2.style.width = "100%";
		if (t.phone.col2) t.phone.col2.style.width = "100%";
		if (t.im.col2) t.im.col2.style.width = "100%";
		setTimeout(function() {
			var w = 0;
			if (t.email)
			for (var i = 0; i < t.email.tbody.childNodes.length; ++i) {
				var we = getWidth(t.email.tbody.childNodes[i].childNodes[0],[]);
				if (we > w) w = we;
			}
			if (t.phone)
			for (var i = 0; i < t.phone.tbody.childNodes.length; ++i) {
				var we = getWidth(t.phone.tbody.childNodes[i].childNodes[0],[]);
				if (we > w) w = we;
			}
			if (t.im)
			for (var i = 0; i < t.im.tbody.childNodes.length; ++i) {
				var we = getWidth(t.im.tbody.childNodes[i].childNodes[0],[]);
				if (we > w) w = we;
			}
			if (t.email.col2) t.email.col2.style.width = "";
			if (t.phone.col2) t.phone.col2.style.width = "";
			if (t.im.col2) t.im.col2.style.width = "";
			if (t.email.col1) t.email.col1.style.width = w+"px";
			if (t.phone.col1) t.phone.col1.style.width = w+"px";
			if (t.im.col1) t.im.col1.style.width = w+"px";
			layout.changed(container);
		}, 1);
	};
	
	/** counter to check if everything is ready */
	t._ready_count = 0;
	/** called when something is ready, to check if everything is now ready */
	t._ready = function() {
		if (++t._ready_count == 3) {
			if (compact) {
				container.appendChild(t.email.container_element);
				container.appendChild(t.phone.container_element);
				container.appendChild(t.im.container_element);
			} else {
				container.appendChild(t.email.table);
				container.appendChild(t.phone.table);
				container.appendChild(t.im.table);
			}
			t._updateCol1();
			layout.changed(container);
			if (onready) onready(t);
		}
	};
	/**
	 * Create the table for a type of contact (email, phone, or IM)
	 * @param {contact_type} contact the contact_type control
	 * @param {String} contact_type type of contact
	 * @param {String} contact_type_name how to display the type of contact
	 * @param {String} color_border color of the border
	 * @param {String} color_background background color of the title
	 */
	t._initTable = function(contact, contact_type, contact_type_name, color_border, color_background) {
		if (compact) {
			contact.container_element = document.createElement("TABLE");
			contact.container_element.style.border = "1px solid "+color_border;
			contact.container_element.style.width = "100%";
			contact.container_element.style.borderSpacing = "0";
			contact.container_element.style.marginBottom = "3px";
			setBorderRadius(contact.container_element, 5, 5, 5, 5, 5, 5, 5, 5);
			var tr = document.createElement("TR");
			contact.container_element.appendChild(tr);
			var td = document.createElement("TD");
			tr.appendChild(td);
			td.innerHTML = "<img src='/static/contact/"+contact_type.toLowerCase()+"_16.png' style='vertical-align:bottom;padding-right:3px' onload='layout.changed(this);'/>";
			td.style.backgroundColor = color_background;
			td.style.verticalAlign = "top";
			td.title = contact_type_name;
			td.style.width = "18px";
			td.style.maxWidth = "18px";
			td.style.minWidth = "18px";
			setBorderRadius(td, 5, 5, 0, 0, 5, 5, 0, 0);
			td = document.createElement("TD");
			tr.appendChild(td);
			td.appendChild(contact.table);
			td.style.verticalAlign = "top";
			td.style.backgroundColor = "white";
			setBorderRadius(td, 0, 0, 5, 5, 0, 0, 5, 5);
		} else {
			contact.table.style.border = "1px solid "+color_border;
			contact.table.style.width = "100%";
			contact.table.style.borderSpacing = "0";
			contact.table.style.marginBottom = "3px";
			setBorderRadius(contact.table, 5, 5, 5, 5, 5, 5, 5, 5);
			var tr_head = document.createElement("tr");
			var th_head = document.createElement("th");
			th_head.colSpan = 2;
			th_head.style.textAlign = "left";
			th_head.style.padding = "2px 5px 2px 5px";
			th_head.innerHTML = "<img src='/static/contact/"+contact_type.toLowerCase()+"_16.png' style='vertical-align:bottom;padding-right:3px' onload='layout.changed(this);'/>"+contact_type_name;
			th_head.style.backgroundColor = color_background;
			setBorderRadius(th_head, 5, 5, 5, 5, 0, 0, 0, 0);
			tr_head.appendChild(th_head);
			contact.thead.appendChild(tr_head);
		}
	};
	
	/** Get all contacts displayed
	 * @returns Array list of Contact
	 */
	t.getContacts = function() {
		var contacts = [];
		var list = t.email.getContacts();
		for (var i = 0; i < list.length; ++i) contacts.push(list[i]);
		var list = t.phone.getContacts();
		for (var i = 0; i < list.length; ++i) contacts.push(list[i]);
		var list = t.im.getContacts();
		for (var i = 0; i < list.length; ++i) contacts.push(list[i]);
		return contacts;
	};
	
	/** Called each type something changed */
	t.onchange = new Custom_Event();
	
	require("contact_type.js",function() {
		var emails = [], phones = [], im = [];
		for (var i = 0; i < contacts.length; ++i)
			switch (contacts[i].type) {
			case "email": emails.push(contacts[i]); break;
			case "phone": phones.push(contacts[i]); break;
			case "IM": im.push(contacts[i]); break;
			}
		t.emails = new contact_type("email", "EMail", owner_type, owner_id, emails, additional_info, can_edit, can_add, can_remove, false, t._updateCol1, function(email){
			t._initTable(email, "email", "EMail", "#304060", "#D8D8F0");
			t.email = email;
			email.onchange.addListener(function(){ t.onchange.fire(t); });
			t._ready();
		});
		t.phones = new contact_type("phone", "Phone", owner_type, owner_id, phones, additional_info, can_edit, can_add, can_remove, false, t._updateCol1, function(phone){
			t._initTable(phone, "phone", "Phone", "#3080b8", "#D0E0FF");
			t.phone = phone;
			phone.onchange.addListener(function(){ t.onchange.fire(t); });
			t._ready();
		});
		t.im = new contact_type("IM", "Instant Messaging", owner_type, owner_id, im, additional_info, can_edit, can_add, can_remove, false, t._updateCol1, function(im){
			t._initTable(im, "IM", "Instant Messaging", "#70a840", "#D8F0D8");
			t.im = im;
			im.onchange.addListener(function(){ t.onchange.fire(t); });
			t._ready();
		});
	});
}