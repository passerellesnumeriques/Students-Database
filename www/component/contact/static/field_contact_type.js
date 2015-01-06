/* #depends[/static/widgets/typed_field/typed_field.js] */
/**
 * A field that can display/edit a list of contacts
 * @param {ContactsData} data the list of contacts together with their owner
 * @param {Boolean} editable initialized as editable or not
 * @param {Object} config must contain <code>type</code> which is one of "email", "phone", or "IM"
 */
function field_contact_type(data,editable,config) {
	typed_field_multiple.call(this, data, editable, config);
	if (typeof config.sub_data_index != 'undefined' && config.sub_data_index == 0) {
		this.helpFillMultipleItems = function() {
			var helper = {
				title: "Fill contact type",
				content: document.createElement("DIV"),
				apply: function(field) {
					var type = this.input.value.trim();
					var choice;
					for (var i = 0; i < this.radios.length; ++i)
						if (this.radios[i].checked) { choice = this.radios[i].value; break; }
					if (choice == "new") {
						field.addData(type);
					} else {
						var index;
						if (choice == "last") {
							index = field.getNbData()-1;
						} else {
							index = parseInt(choice)-1;
						}
						if (index >= 0 && index < field.getNbData())
							field.setDataIndex(index, type);
					}
				}
			};
			var div = document.createElement("DIV");
			helper.content.appendChild(div);
			div.appendChild(document.createTextNode("Contact Type: "));
			helper.input = document.createElement("INPUT");
			helper.input.size = 15;
			helper.input.maxLength = 100;
			div.appendChild(helper.input);
			helper.input.onclick = function() {
				require("contact_objects.js", function() {
					showContactTypeMenu(helper.input,config.type,helper.input.value,false,function(new_type) {
						helper.input.value = new_type;
					});
				});
			};
			helper.radios = [];
			var addRadio = function(text, value, selected) {
				var div = document.createElement("DIV");
				var radio = document.createElement("INPUT");
				radio.type = "radio";
				radio.value = value;
				radio.name = "contact_type_choice";
				helper.radios.push(radio);
				div.appendChild(radio);
				div.appendChild(document.createTextNode(" "+text));
				if (selected) radio.checked = 'checked';
				helper.content.appendChild(div);
			};
			addRadio("Create a new contact with this type", "new", true);
			addRadio("Set the first contact with this type (if one exists)", "1");
			addRadio("Set the second contact with this type (if at least two exist)", "2");
			addRadio("Set the third contact with this type (if at least three exist)", "3");
			addRadio("Set the fourth contact with this type (if at least four exist)", "4");
			addRadio("Set the fifth contact with this type (if at least five exist)", "5");
			addRadio("Set the last contact with this type (if at least one exists)", "last");
			return helper;
		};
	}
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
				return this._data.contacts.length;
			};
			this.resetData = function() {
				this._data.contacts = [];
				t.setData(this._data, true);
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
					var t=this;
					require("contact_objects.js", function() {
						var contact = new Contact(-1, t.config.type, new_data, "");
						t._data.contacts.push(contact);
						t.setData(t._data, true);
					});
				};
				this.getDataIndex = function(index) {
					return this._data.contacts[index].sub_type;
				};
				this.setDataIndex = function(index, new_data) {
					this._data.contacts[index].sub_type = new_data;
					this.setData(this._data, true);
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
					var t=this;
					require("contact_objects.js", function() {
						var contact = new Contact(-1, t.config.type, "", new_data);
						t._data.contacts.push(contact);
						t.setData(t._data, true);
					});
				};
				this.getDataIndex = function(index) {
					return this._data.contacts[index].contact;
				};
				this.setDataIndex = function(index, new_data) {
					this._data.contacts[index].contact = new_data;
					this.setData(this._data, true);
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