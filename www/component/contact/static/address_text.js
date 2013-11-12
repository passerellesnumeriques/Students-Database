if (typeof window.top.require != 'undefined')
	window.top.require("geography.js");

function address_text(address){
	this.element = document.createElement("DIV");

	var empty = true;
	if (address.building != null || address.unit != null) {
		var text = "";
		var first = true;
		if(address.building != null){
			text += "Building "+address.building;
			first = false;
		}
		if(address.unit != null){
			if(!first) text += ", ";
			text += "Unit "+address.unit;
			first = false;
		}
		var div = document.createElement("DIV");
		this.element.appendChild(div);
		div.appendChild(document.createTextNode(text));
		empty &= text.trim().length > 0;
	}
	if(address.street_name != null || address.street_number != null) {
		var text = "";
		var first = true;
		if(address.street_number != null){
			text += address.street_number;
			first = false;
		}
		if(address.street_name != null){
			if(!first) text += ", ";
			text += address.street_name;
			first = false;
		}
		var div = document.createElement("DIV");
		this.element.appendChild(div);
		div.appendChild(document.createTextNode(text));
		empty &= text.trim().length > 0;
	}
	
	if(address.additional != null){
		var div = document.createElement("DIV");
		this.element.appendChild(div);
		div.appendChild(document.createTextNode(address.additional));
		empty &= address.additional.trim().length > 0;
	}

	if (address.geographic_area != null) {
		var div = document.createElement("DIV");
		this.element.appendChild(div);
		div.appendChild(document.createTextNode(address.geographic_area.text));
		empty &= address.geographic_area.text.trim().length > 0;
	}

	if (address.country != null) {
		var div = document.createElement("DIV");
		this.element.appendChild(div);
		window.top.require("geography.js",function() {
			window.top.geography.getCountryName(address.country,function(country_name){
				div.appendChild(document.createTextNode(country_name.uniformFirstLetterCapitalized()));
			});
			
		});
		empty = false;
	}
	
	if (empty) {
		var div = document.createElement("DIV");
		this.element.appendChild(div);
		div.appendChild(document.createTextNode("No information"));
		div.style.fontStyle = "italic";
		div.style.color = "#404040";
	}
	
}