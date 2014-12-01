/* #depends[/static/widgets/typed_field/typed_field.js] */

function field_geographic_area(data,editable,config) {
	typed_field.call(this, data, editable, config);
}
field_geographic_area.prototype = new typed_field();
field_geographic_area.prototype.constructor = field_geographic_area;
field_geographic_area.prototype.canBeNull = function() { if (this.config && (typeof this.config.can_be_null != 'undefined')) return this.config.can_be_null; return true; };
field_geographic_area.prototype._create = function(data) {
	if (this.editable) {
		this._text = document.createElement("A");
		this._text.className = "black_link";
		this._text.href = "#";
		var t=this;
		t._edited = data;
		this._text.onclick = function(ev) {
			require(["popup_window.js","geographic_area_selection.js"], function() {
				var content = document.createElement("DIV");
				content.style.backgroundColor = "white";
				content.style.padding = "10px";
				var sel = new geographic_area_selection(content, window.top.default_country_id, t.getCurrentData(), 'vertical', true);
				var popup = new popup_window("Geographic Area", "/static/geography/geography_16.png", content);
				popup.addOkCancelButtons(function() {
					t.setData(t._edited = sel.getSelectedArea());
					popup.close();
				});
				popup.show();
			});
			stopEventPropagation(ev);
			return false;
		};
		this._getEditedData = function() { return t._edited; };
		this.validate = function() {
			if (!this.config) this.signal_error(null);
			else if (typeof this.config.can_be_null == 'undefined') this.signal_error(null);
			else if (this.config.can_be_null) this.signal_error(null);
			else if (this._edited == null) this.signal_error("Please choose a geographic area");
			else this.signal_error(null);
		};
		this.signal_error = function(error) {
			this.error = error;
			this._text.style.color = error ? "red" : "";
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
			this._text.innerHTML = "<img src='"+theme.icons_10.loading+"'/>";
			var t=this;
			window.top.geography.getCountryData(window.top.default_country_id, function(country_data) {
				if (window.closing || !layout) return;
				t._text.removeAllChildren();
				var area = window.top.geography.searchArea(country_data, data);
				if (!area)
					t._text.innerHTML = "Unknown";
				else {
					var text = window.top.geography.getGeographicAreaText(country_data, area);
					t._text.appendChild(document.createTextNode(text.text));
				}
				layout.changed(t._text);
			});
			this._text.style.fontStyle = "normal";
		}
		return data;
	};
	this._text.style.whiteSpace = "nowrap";
	this.element.appendChild(this._text);
	this._setData(data);
};
field_geographic_area.prototype.helpFillMultipleItems = function() {
	var helper = {
		title: 'Set the geographic area for all',
		content: document.createElement("DIV"),
		apply: function(field) {
			field.setData(this.geo.getSelectedArea());
		}
	};
	require("geographic_area_selection.js", function() {
		helper.geo = new geographic_area_selection(helper.content, window.top.default_country_id, null, 'vertical', true, function() { layout.changed(helper.content); });
	});
	return helper;
};