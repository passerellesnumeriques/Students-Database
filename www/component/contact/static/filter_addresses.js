/* #depends[/static/widgets/typed_filter/typed_filter.js] */

function filter_addresses(data, config, editable) {
	typed_filter.call(t, data, config, editable);
	var existing_types;
	var t = this;
	var wait = new AsynchLoadListener(2,function() {
		if (!data)
			t.data = data = {types:existing_types,areas:null};
		var span = document.createElement("SPAN");
		span.style.verticalAlign = "middle";
		span.appendChild(document.createTextNode("having type "))
		t.element.appendChild(span);
		t.select_type = new select_checkboxes(t.element);
		t.select_type.getHTMLElement().style.verticalAlign = "middle";
		for (var i = 0; i < existing_types.length; ++i)
			t.select_type.add(existing_types[i], document.createTextNode(existing_types[i]));
		t.select_type.setSelection(data.types);
		t.select_type.onchange = function() {
			data.types = t.select_type.getSelection();
			t.onchange.fire(t);
		};
		if (!editable) t.select_type.disable();
		span = document.createElement("SPAN");
		span.style.verticalAlign = "middle";
		span.appendChild(document.createTextNode(" and located in "));
		t.element.appendChild(span);
		var updateText = function() {
			if (data.areas == null) {
				t.link.innerHTML = "Anywhere";
				layout.changed(t.link);
			} else
				window.top.geography.getCountryData(window.top.default_country_id, function(country_data) {
					t.link.innerHTML = "";
					t.link.appendChild(document.createTextNode(window.top.geography.getGeographicAreaTextFromId(country_data, data.areas[0]).text));
					layout.changed(t.link);
				});
		};
		if (editable) {
			t.link = document.createElement("A");
			t.link.href = "#";
			t.link.className = "black_link";
			t.link.style.verticalAlign = "middle";
			t.link.onclick = function(ev) {
				require(["geographic_area_selection.js","mini_popup.js"], function() {
					var p = new mini_popup("Located in which area ?");
					var s = new geographic_area_selection(p.content, window.top.default_country_id, data.areas ? data.areas[0] : null, 'vertical', true, function(s) {
						p.addOkButton(function() {
							var area_id = s.getSelectedArea();
							if (area_id > 0) {
								data.areas = [area_id];
								var area = window.top.geography.searchArea(s.country_data, area_id);
								var division_index = window.top.geography.getAreaDivisionIndex(s.country_data, area);
								var areas = [area];
								while (division_index < s.country_data.length-1) {
									division_index++;
									var children = [];
									for (var i = 0; i < areas.length; ++i) {
										var list = window.top.geography.getAreaChildren(s.country_data, division_index, areas[i].area_id);
										for (var j = 0; j < list.length; ++j) {
											children.push(list[j]);
											data.areas.push(list[j].area_id);
										}
									}
									areas = children;
								}
								updateText();
								t.onchange.fire(t);
							} else {
								data.areas = null;
								updateText();
								t.onchange.fire(t);
							}
							p.close();
						});
						p.showBelowElement(t.link);
					});
				});
				stopEventPropagation(ev);
				return false;
			};
		} else {
			t.link = document.createElement("SPAN");
		}
		t.element.appendChild(t.link);
		updateText();
		if (editable) require(["geographic_area_selection.js","mini_popup.js"]);
	});
	require("select_checkboxes.js",function() { wait.operationDone(); });
	service.json("contact", "get_existing_address_types",{type:config.type},function(list) {
		existing_types = list;
		wait.operationDone();
	});
	this.isActive = function() {
		if (!data) return false;
		if (data.types.length < config.types.length) return true;
		if (data.areas != null) return true;
		return false;
	};
}
filter_addresses.prototype = new typed_filter;
filter_addresses.prototype.constructor = filter_addresses;
