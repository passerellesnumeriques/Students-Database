/* #depends[/static/widgets/typed_field/typed_field.js] */
/**
 * Configuration for a field_contact_type
 * @param {String} contact_type one of: "email", "phone", or "IM"
 */
function field_contact_type_config(contact_type) {
	this.contact_type = contact_type;
}

/**
 * A field that can display/edit a list of contacts
 * @param {ContactsData} data the list of contacts together with their owner
 * @param {Boolean} editable initialized as editable or not
 * @param {field_contact_type_config} config indicates which kind of contacts to display (email, phone or IM)
 */
function field_contact_type(data,editable,config) {
	typed_field_multiple.call(this, data, editable, config);
}
field_contact_type.prototype = new typed_field_multiple();
field_contact_type.prototype.constructor = field_contact_type;		
field_contact_type.prototype._create = function(data) {
	if (this.editable) {
		var t=this;
		require("contact_type.js",function() {
			var contact_type_name = "?";
			switch (t.config.type) {
			case "email": contact_type_name = "EMail"; break;
			case "phone": contact_type_name = "Phone"; break;
			case "IM": contact_type_name = "Instant Messaging"; break;
			}
			t.control = new contact_type(t.config.type, contact_type_name, data.type, data.type_id, data.contacts, true, true, true, true, null, null);
			t.control.onchange.add_listener(function() { t._datachange(); });
			t.element.appendChild(t.control.table);
		});
		this.addData = function(new_data) {
			var contact;
			if (typeof new_data == 'object')
				contact = new_data;
			else
				contact = new Contact(-1,t.config.type,"Professional",new_data);
			var finalize = function() {
				if (t.control)
					t.control.createContact(contact);
				else
					setTimeout(finalize,10);
			};
			finalize();
		};
		this.getNbData = function() {
			if (!t.control) return 0;
			return t.control.getContacts().length;
		};
		this.resetData = function() {
			var nb = t.control.getContacts().length;
			for (var i = nb-1; i >= 0; --i)
				t.control.removeContact(t.control.getContacts()[i]);
		};
	} else {
		if (!data) return;
		for (var i = 0; i < data.contacts.length; ++i) {
			if (i > 0) this.element.appendChild(document.createTextNode(", "));
			var span = document.createElement("SPAN");
			span.style.whiteSpace = "nowrap";
			var e = document.createElement("SPAN");
			e.appendChild(document.createTextNode(data.contacts[i].contact));
			span.appendChild(e);
			e = document.createElement("SPAN");
			e.style.fontStyle = "italic";
			e.style.color = "#808080";
			e.appendChild(document.createTextNode(" ("+data.contacts[i].sub_type+")"));
			span.appendChild(e);
			this.element.appendChild(span);
			layout.invalidate(this.element);
		}
	}
};