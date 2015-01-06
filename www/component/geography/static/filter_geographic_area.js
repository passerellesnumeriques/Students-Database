/* #depends[/static/widgets/typed_filter/typed_filter.js] */

function filter_geographic_area(data, config, editable) {
	typed_filter.call(this, data, config, editable);
	var t=this;
	this.updateText = function() {
		this.link.removeAllChildren();
		if (this.data == null || this.data.length == 0)
			this.link.appendChild(document.createTextNode("Anywhere"));
		else window.top.geography.getCountryData(window.top.default_country_id, function(country_data) {
			var area_id = t.data[0];
			var text = window.top.geography.getGeographicAreaTextFromId(country_data, area_id);
			t.link.appendChild(document.createTextNode(text.text));
		});
	};
	this.isActive = function() {
		return this.data !== null && this.data.length > 0;
	};
	if (editable) {
		this.link = document.createElement("A");
		this.link.href = "#";
		this.link.className = "black_link";
		this.link.onclick = function(ev) {
			require(["mini_popup.js","geographic_area_selection.js"], function() {
				var p = new mini_popup("Restrict to Geographic Area");
				new geographic_area_selection(p.content, window.top.default_country_id, t.data ? t.data[0] : null, 'vertical', true, function(s) {
					p.addOkButton(function() {
						var area_id = s.getSelectedArea();
						if (area_id > 0) {
							t.data = [area_id];
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
										t.data.push(list[j].area_id);
									}
								}
								areas = children;
							}
							t.updateText();
							t.onchange.fire(t);
						} else {
							t.data = null;
							t.updateText();
							t.onchange.fire(t);
						}
						return true;
					});
					p.showBelowElement(t.link);
				});
			});
			stopEventPropagation(ev);
			return false;
		};
		require(["mini_popup.js","geographic_area_selection.js"]);
	} else {
		this.link = document.createElement("SPAN");
	}
	this.updateText();
	this.element.appendChild(this.link);
}
filter_geographic_area.prototype = new typed_filter;
filter_geographic_area.prototype.constructor = filter_geographic_area;
