function field_contact_type(data,editable,config) {
	typed_field.call(this, data, editable, config);
}
field_contact_type.prototype = new typed_field();
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
			t.control = new contact_type(t.config.type, contact_type_name, data.table, data.key_name, data.key_value, data.contacts, true, true, true, null, null);
			t.element.appendChild(t.control.table);
		});
	} else {
		for (var i = 0; i < data.contacts.length; ++i) {
			if (i > 0) this.element.appendChild(document.createTextNode(", "));
			var e = document.createElement("SPAN");
			e.appendChild(document.createTextNode(data.contacts[i].contact));
			this.element.appendChild(e);
			e = document.createElement("SPAN");
			e.style.fontStyle = "italic";
			e.style.color = "#808080";
			e.appendChild(document.createTextNode(" ("+data.contacts[i].sub_type+")"));
			this.element.appendChild(e);
		}
	}
};