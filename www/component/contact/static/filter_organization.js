/* #depends[/static/widgets/typed_filter/typed_filter.js] */

if (typeof require != 'undefined') require("field_organization.js");

function filter_organization(data, config, editable) {
	typed_filter.call(this, data, config, editable);

	this.isActive = function() {
		return this.data !== null;
	};
	this._setText = function(container, data) {
		container.removeAllChildren();
		if (data === null || data.length == 0)
			container.innerHTML = "All";
		else {
			var s = "";
			if (data.indexOf("NOT_NULL") >= 0) {
				s = "All specified (not blank)";
			} else {
				var nb = data.length;
				if (data.indexOf("NULL") >= 0) {
					s = "All not specified (blank)";
					nb--;
				}
				if (nb == 1) {
					var org = null;
					for (var i = 0; i < data.length; ++i) if (data[i] != "NULL") { org = data[i]; break; }
					if (s.length > 0) s += ", ";
					s += getOrganizationNameFromID(org, this.config.list);
				} else if (nb > 0) {
					if (s.length > 0) s += ", ";
					s += nb+" selected";
				}
			}
			container.appendChild(document.createTextNode(s));
		}
	};
	
	if (this.editable) {
		var t=this;
		require("field_organization.js", function() {
			t.link = document.createElement("A");
			t.link.href = "#";
			t.link.className = "black_link";
			t.element.appendChild(t.link);
			t._setText(t.link, data);
			t.link.onclick = function() {
				require("mini_popup.js", function() {
					var p = new mini_popup("Select "+t.config.name, true);
					p.content.style.display = "flex";
					p.content.style.flexDirection = "column";
					var selected = [];
					if (t.data === null || t.data.indexOf("NOT_NULL") >= 0)
						for (var i = 0; i < t.config.list.length; ++i) selected.push(t.config.list[i].id);
					else
						for (var i = 0; i < t.data.length; ++i)
							if (t.data[i] != "NULL")
								selected.push(t.data[i]);
					var s = new OrganizationSelectionPopupContent(t.config.list, true, selected);
					s.content.style.flex = "1 1 auto";
					var cb_n, cb_nn;
					var container = p.content;
					if (t.config.can_be_null) {
						var div;
						div = document.createElement("DIV");
						div.style.flex = "none";
						cb_n = document.createElement("INPUT");
						cb_n.type = "checkbox";
						cb_n.style.verticalAlign = "middle";
						cb_n.style.marginRight = "3px";
						div.appendChild(cb_n);
						div.appendChild(document.createTextNode("All not specified (blank)"));
						cb_n.checked = t.data === null || t.data.indexOf("NULL") >= 0 ? "checked" : "";
						p.content.appendChild(div);
						div = document.createElement("DIV");
						div.style.flex = "none";
						cb_nn = document.createElement("INPUT");
						cb_nn.type = "checkbox";
						cb_nn.style.verticalAlign = "middle";
						cb_nn.style.marginRight = "3px";
						div.appendChild(cb_nn);
						div.appendChild(document.createTextNode("All specified (not blank)"));
						cb_nn.checked = t.data === null || t.data.indexOf("NOT_NULL") >= 0 ? "checked" : "";
						cb_nn.onchange = function() {
							s.checkAll(this.checked);
						};
						p.content.appendChild(div);

						container = document.createElement("DIV");
						container.style.display = "flex";
						container.style.flexDirection = "column";
						container.style.flex = "1 1 auto";
						p.content.appendChild(container);
					}
					container.appendChild(s.content);
					s.onchange.add_listener(function() {
						if (s.selected_ids.length == t.config.list.length) {
							if (cb_nn) cb_nn.checked = "checked";
						} else {
							if (cb_nn) cb_nn.checked = "";
						}
					});
					p.addOkButton(function() {
						t.data = [];
						if (cb_n) {
							if (cb_n.checked && cb_nn.checked) t.data = null;
							else if (cb_n.checked) t.data.push("NULL");
							else if (cb_nn.checked) t.data.push("NOT_NULL");
						} else if (s.selected_ids.length == t.config.list.length)
							t.data = null;
						if (t.data !== null && (!cb_nn || !cb_nn.checked))
							for (var i = 0; i < s.selected_ids.length; ++i)
								t.data.push(s.selected_ids[i]);
						t._setText(t.link, t.data);
						t.onchange.fire(t);
						p.close();
					});
					p.showBelowElement(t.link);
					s.focus();
				});
			};
			require("mini_popup.js");
		});
	} else {
		this._setText(this.element, data);
	}
}
filter_organization.prototype = new typed_filter;
filter_organization.prototype.constructor = filter_organization;
