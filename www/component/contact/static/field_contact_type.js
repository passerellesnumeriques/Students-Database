/* #depends[/static/widgets/typed_field/typed_field.js] */
/**
 * A field that can display/edit a list of contacts
 * @param {ContactsData} data the list of contacts together with their owner
 * @param {Boolean} editable initialized as editable or not
 * @param {Object} config must contain <code>type</code> which is one of "email", "phone", or "IM"
 */
function field_contact_type(data,editable,config) {
	typed_field_multiple.call(this, data, editable, config);
}
field_contact_type.prototype = new typed_field_multiple();
field_contact_type.prototype.constructor = field_contact_type;	
field_contact_type.prototype.canBeNull = function() { return true; };
field_contact_type.prototype._create = function(data) {
	if (!data) return; // must be set
	if (typeof this.config.sub_data_index == 'undefined') {
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
				t.control.onchange.addListener(function() { t._datachange(); });
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
				layout.changed(this.element);
			}
		}		
	} else {
		window.top.sub_field_registry.register(window, this);
		this.onchange.addListener(function(f){
			window.top.sub_field_registry.changed(window, f);
		});
		this.element.style.height = "100%";
		if (this.editable) {
			this.getNbData = function() {
				return t._data.contacts.length;
			};
			this.resetData = function() {
				t._data.contacts = [];
				t.setData(t._data, true);
			};

			if (this.config.sub_data_index == 0) {
				// type
				this._setData = function(data) {
					this.element.removeAllChildren();
					if (data == null) return null;
					for (var i = 0; i < data.contacts.length; ++i) {
						var contact = data.contacts[i];
						var div = document.createElement("DIV");
						var input = document.createElement("INPUT")
						input.type = 'text';
						input.maxLength = 100;
						input.size = 10;
						input.style.color = "#606060";
						input.value = contact.sub_type;
						div.appendChild(input);
						this.element.appendChild(div);
						input._contact = contact;
						var t=this;
						input.onchange = function() {
							this._contact.sub_type = this.value;
							t._datachange(true);
						};
						input.onclick = function() {
							var input = this;
							require("contact_objects.js", function() {
								showContactTypeMenu(input,t.config.type,input.value,false,function(new_type) { input.value = new_type; input.onchange(); });
							});
						};
					}
					return data;
				};
				this._setData(data);
				
				this.addData = function(new_data) {
					require("contact_objects.js", function() {
						var contact = new Contact(-1, t.config.type, new_data, "");
						t._data.contacts.push(contact);
						t.setData(t._data, true);
					});
				};
				this.getDataIndex = function(index) {
					return t._data.contacts[index].sub_type;
				};
				this.setDataIndex = function(index, new_data) {
					t._data.contacts[index].sub_type = new_data;
					t.setData(t._data, true);
				};
			} else {
				// contact
				this._setData = function(data) {
					this.element.removeAllChildren();
					if (data == null) return null;
					var t=this;
					for (var i = 0; i < data.contacts.length; ++i) {
						var contact = data.contacts[i];
						var div = document.createElement("DIV");
						var input = document.createElement("INPUT")
						input.type = 'text';
						input.maxLength = 100;
						input.size = 20;
						input.value = contact.contact;
						div.appendChild(input);
						this.element.appendChild(div);
						input._contact = contact;
						input.onchange = function() {
							this._contact.contact = this.value;
							t._datachange(true);
						};
					}
					var add_button = document.createElement("BUTTON");
					add_button.className = "flat small_icon";
					add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
					add_button.title = "Add new contact";
					this.element.appendChild(add_button);
					add_button.onclick = function(event) {
						require("contact_objects.js", function() {
							var contact = new Contact(-1, t.config.type, "", "");
							t._data.contacts.push(contact);
							t.setData(t._data, true);
						});
						stopEventPropagation(event);
						return false;
					};
					return data;
				};
				this._setData(data);
				
				this.addData = function(new_data) {
					require("contact_objects.js", function() {
						var contact = new Contact(-1, t.config.type, "", new_data);
						t._data.contacts.push(contact);
						t.setData(t._data, true);
					});
				};
				this.getDataIndex = function(index) {
					return t._data.contacts[index].contact;
				};
				this.setDataIndex = function(index, new_data) {
					t._data.contacts[index].contact = new_data;
					t.setData(t._data, true);
				};
			}
		} else {
			this._setData = function(data) {
				this.element.removeAllChildren();
				if (data)
				for (var i = 0; i < data.contacts.length; ++i) {
					var contact = data.contacts[i];
					var div = document.createElement("DIV");
					div.style.whiteSpace = 'nowrap';
					if (this.config.sub_data_index == 0) {
						div.style.color = "#606060";
						div.appendChild(document.createTextNode(contact.sub_type));
					} else {
						div.appendChild(document.createTextNode(contact.contact));
					}
					this.element.appendChild(div);
				}
				return data;
			};
			this._setData(data);
		}
	}
};