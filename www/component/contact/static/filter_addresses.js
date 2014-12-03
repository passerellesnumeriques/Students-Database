/* #depends[/static/widgets/typed_filter/typed_filter.js] */

function filter_addresses(data, config, editable) {
	if (!data)
		data = {types:arrayCopy(config.types),area_path:null};
	typed_filter.call(this, data, config, editable);
	
	var t=this;
	require("select_checkboxes.js", function() {
		var span = document.createElement("SPAN");
		span.style.verticalAlign = "middle";
		span.appendChild(document.createTextNode("having type "))
		t.element.appendChild(span);
		t.select_type = new select_checkboxes(t.element);
		t.select_type.getHTMLElement().style.verticalAlign = "middle";
		for (var i = 0; i < config.types.length; ++i) {
			t.select_type.add(config.types[i], document.createTextNode(config.types[i]));
		}
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
			if (data.area_path == null)
				t.link.innerHTML = "Anywhere";
			else
				window.top.geography.getCountryData(window.top.default_country_id, function(country_data) {
					t.link.innerHTML = "";
					t.link.appendChild(document.createTextNode(window.top.geography.getGeographicAreaTextFromId(country_data, data.area_path[data.area_path.length-1]).text));
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
					var s = new geographic_area_selection(p.content, window.top.default_country_id, data.area_path ? data.area_path[data.area_path.length-1] : null, 'vertical', true, function(s) {
						p.addOkButton(function() {
							var area_id = s.getSelectedArea();
							if (area_id > 0) {
								var path = [area_id];
								window.top.geography.getCountryData(window.top.default_country_id, function(country_data) {
									var area = window.top.geography.searchArea(country_data, area_id);
									while (area.area_parent_id > 0) {
										area = window.top.geography.getParentArea(country_data, area);
										path.splice(0,0,area.area_id);
									}
									data.area_path = path;
									updateText();
									t.onchange.fire(t);
								});
							} else {
								data.area_path = null;
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
	});
	if (editable) require(["geographic_area_selection.js","mini_popup.js"]);
	this.isActive = function() {
		if (data.types.length < config.types.length) return true;
		if (data.area_path != null) return true;
		return false;
	};
}
filter_addresses.prototype = new typed_filter;
filter_addresses.prototype.constructor = filter_addresses;
