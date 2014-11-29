/* #depends[/static/widgets/typed_field/typed_field.js] */

function field_geographic_area(data,editable,config) {
	typed_field.call(this, data, editable, config);
}
field_geographic_area.prototype = new typed_field();
field_geographic_area.prototype.constructor = field_geographic_area;		
field_geographic_area.prototype._create = function(data) {
	if (this.editable) {
		this._text = document.createElement("A");
		this._text.className = "black_link";
		this._text.href = "#";
		var t=this;
		this._text.onclick = function(ev) {
			require(["popup_window.js","geographic_area_selection.js"], function() {
				var content = document.createElement("DIV");
				content.style.backgroundColor = "white";
				content.style.padding = "10px";
				var sel = new geographic_area_selection(content, window.top.default_country_id, t.getCurrentData(), 'vertical', true);
				var popup = new popup_window("Geographic Area", "/static/geography/geography_16.png", content);
				popup.addOkCancelButtons(function() {
					t.setData(sel.getSelectedArea());
					popup.close();
				});
				popup.show();
			});
			stopEventPropagation(ev);
			return false;
		};
	} else {
		this._text = document.createElement("SPAN");
	}
	this._setData = function(data) {
		data = data ? parseInt(data) : null;
		if (isNaN(data)) data = null;
		this._text.removeAllChildren();
		if (data == null) {
			this._text.appendChild(document.createTextNode("Not specified"));
			this._text.style.fontStyle = "italic";
		} else {
			var t = document.createTextNode("... loading ...");
			this._text.appendChild(t);
			window.top.geography.getCountryData(window.top.default_country_id, function(country_data) {
				if (window.closing || !layout) return;
				var area = window.top.geography.searchArea(country_data, data);
				if (!area)
					t.nodeValue = "Unknown";
				else {
					var text = window.top.geography.getGeographicAreaText(country_data, area);
					t.nodeValue = text.text;
				}
				layout.changed(t.parentNode);
			});
			this._text.style.fontStyle = "normal";
		}
		return data;
	};
	this._text.style.whiteSpace = "nowrap";
	this.element.appendChild(this._text);
	this._setData(data);
};
