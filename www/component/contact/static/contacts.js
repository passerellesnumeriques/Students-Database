if (typeof require != 'undefined')
	require("contact_type.js");

function contacts(container, table_join, join_key, join_value, contacts, can_edit, can_add, can_remove, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
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
				var we = getWidth(t.email.tbody.childNodes[i].childNodes[0]);
				if (we > w) w = we;
			}
			if (t.phone)
			for (var i = 0; i < t.phone.tbody.childNodes.length; ++i) {
				var we = getWidth(t.phone.tbody.childNodes[i].childNodes[0]);
				if (we > w) w = we;
			}
			if (t.im)
			for (var i = 0; i < t.im.tbody.childNodes.length; ++i) {
				var we = getWidth(t.im.tbody.childNodes[i].childNodes[0]);
				if (we > w) w = we;
			}
			if (t.email.col2) t.email.col2.style.width = "";
			if (t.phone.col2) t.phone.col2.style.width = "";
			if (t.im.col2) t.im.col2.style.width = "";
			if (t.email.col1) t.email.col1.style.width = w+"px";
			if (t.phone.col1) t.phone.col1.style.width = w+"px";
			if (t.im.col1) t.im.col1.style.width = w+"px";
		}, 1);
	};
	
	t._ready_count = 0;
	t._ready = function() {
		if (++t._ready_count == 3) {
			container.appendChild(t.email.table);
			container.appendChild(t.phone.table);
			container.appendChild(t.im.table);
			t._updateCol1();
			if (onready) onready(t);
		}
	};
	t._init_table = function(contact, contact_type, contact_type_name, colorBorder, colorBg) {
		contact.table.style.border = "1px solid "+colorBorder;
		contact.table.style.width = "100%";
		contact.table.style.borderSpacing = "0";
		contact.table.style.marginBottom = "3px";
		setBorderRadius(contact.table, 5, 5, 5, 5, 5, 5, 5, 5);
		var tr_head = document.createElement("tr");
		var th_head = document.createElement("th");
		th_head.colSpan = 2;
		th_head.style.textAlign = "left";
		th_head.style.padding = "2px 5px 2px 5px";
		th_head.innerHTML = "<img src='/static/contact/"+contact_type+"_16.png' style='vertical-align:bottom;padding-right:3px'/>"+contact_type_name;
		th_head.style.backgroundColor = colorBg;
		setBorderRadius(th_head, 5, 5, 5, 5, 0, 0, 0, 0);
		tr_head.appendChild(th_head);
		contact.thead.appendChild(tr_head);
	};
	
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
	
	t.onchange = new Custom_Event();
	
	require("contact_type.js",function() {
		var emails = [], phones = [], im = [];
		for (var i = 0; i < contacts.length; ++i)
			switch (contacts[i].type) {
			case "email": emails.push(contacts[i]); break;
			case "phone": phones.push(contacts[i]); break;
			case "IM": im.push(contacts[i]); break;
			}
		new contact_type("email", "EMail", table_join, join_key, join_value, emails, can_edit, can_add, can_remove, t._updateCol1, function(email){
			t._init_table(email, "email", "EMail", "#304060", "#D8D8F0");
			t.email = email;
			email.onchange.add_listener(function(){ t.onchange.fire(t); });
			t._ready();
		});
		new contact_type("phone", "Phone", table_join, join_key, join_value, phones, can_edit, can_add, can_remove, t._updateCol1, function(phone){
			t._init_table(phone, "phone", "Phone", "#3080b8", "#D0E0FF");
			t.phone = phone;
			phone.onchange.add_listener(function(){ t.onchange.fire(t); });
			t._ready();
		});
		new contact_type("IM", "Instant Messaging", table_join, join_key, join_value, im, can_edit, can_add, can_remove, t._updateCol1, function(im){
			t._init_table(im, "IM", "Instant Messaging", "#70a840", "#D8F0D8");
			t.im = im;
			im.onchange.add_listener(function(){ t.onchange.fire(t); });
			t._ready();
		});
	});
}