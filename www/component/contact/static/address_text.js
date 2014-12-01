if (typeof window.top.require != 'undefined')
	window.top.require("geography.js");

/**
 * Creates a DIV containing read-only text representing the given postal address
 * @param {PostalAddress} address the postal address
 */
function address_text(address){
	/** DIV containing everything */ 
	this.element = document.createElement("DIV");
	var empty = true;
	if (address.building != null || address.unit != null) {
		var text = "";
		var first = true;
		if(address.building != null && address.building.trim().length > 0){
			text += "Building "+address.building;
			first = false;
		}
		if(address.unit != null && address.unit.trim().length > 0){
			if(!first) text += ", ";
			text += "Unit "+address.unit;
			first = false;
		}
		if (text.trim().length > 0) {
			var div = document.createElement("DIV");
			div.style.whiteSpace = "nowrap";
			this.element.appendChild(div);
			div.appendChild(document.createTextNode(text));
			empty = false;
		}
	}
	if(address.street != null || address.street_number != null) {
		var text = "";
		var first = true;
		if(address.street_number != null && address.street_number.trim().length > 0){
			text += address.street_number;
			first = false;
		}
		if(address.street != null && address.street.trim().length > 0){
			if(!first) text += ", ";
			text += address.street;
			first = false;
		}
		if (text.trim().length > 0) {
			var div = document.createElement("DIV");
			div.style.whiteSpace = "nowrap";
			this.element.appendChild(div);
			div.appendChild(document.createTextNode(text));
			empty = false;
		}
	}
	
	if(address.additional != null && address.additional.trim().length > 0){
		var div = document.createElement("DIV");
		div.style.whiteSpace = "nowrap";
		this.element.appendChild(div);
		div.appendChild(document.createTextNode(address.additional));
		empty = false;
	}

	if (address.geographic_area != null && address.geographic_area.text != null && address.geographic_area.text.trim().length > 0) {
		var div = document.createElement("DIV");
		div.style.whiteSpace = "nowrap";
		this.element.appendChild(div);
		div.appendChild(document.createTextNode(address.geographic_area.text));
		empty = false;
	}

	if (address.country_id != null && address.country_id != window.top.default_country_id) {
		var div = document.createElement("DIV");
		div.style.whiteSpace = "nowrap";
		this.element.appendChild(div);
		window.top.require("geography.js",function() {
			window.top.geography.getCountryName(address.country_id,function(country_name){
				div.appendChild(document.createTextNode(country_name.uniformFirstLetterCapitalized()));
				layout.changed(div);
			});
			
		});
		empty = false;
	}
	
	if (empty) {
		var div = document.createElement("DIV");
		div.style.whiteSpace = 'nowrap';
		this.element.appendChild(div);
		div.appendChild(document.createTextNode("No information"));
		div.style.fontStyle = "italic";
		div.style.color = "#404040";
	}
	
}