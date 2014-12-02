// #depends[/static/geography/geography_objects.js]

if (typeof require != 'undefined') require("geography_objects.js");

/**
 * Postal address
 * @param {Number} id Postal address ID
 * @param {Number} country_id Country ID
 * @param {GeographicAreaText} geographic_area geographic area
 * @param {String} street street name
 * @param {String} street_number street number
 * @param {String} building building name
 * @param {String} unit unit name
 * @param {String} additional any additional text
 * @param {String} address_type type (Word, Home...)
 * @param {Number} lat latitude
 * @param {Number} lng longitude
 */
function PostalAddress(id, country_id, geographic_area, street, street_number, building, unit, additional, address_type, lat, lng) {
	this.id = id;
	this.country_id = country_id;
	if(geographic_area)
		this.geographic_area = geographic_area;
	else{
		this.geographic_area = {};
		this.geographic_area.id = null;
		this.geographic_area.country_id = country_id;
		this.geographic_area.text = null;
	}
	this.street = street;
	this.street_number = street_number;
	this.building = building;
	this.unit = unit;
	this.additional = additional;
	this.address_type = address_type;
	this.lat = lat;
	this.lng = lng;
}

window.default_address_types = {
	'people':['Home','Family','Birthplace','Work'],
	'organization':['Office']
};
function showAddressTypeMenu(below_element,type,current_type,show_other,onchanged) {
	require("context_menu.js",function() {
		if (below_element._context) below_element._context.hide();
		below_element._context = new context_menu();
		for (var i = 0; i < window.default_address_types[type].length; ++i) {
			var item = document.createElement('DIV');
			item.appendChild(document.createTextNode(window.default_address_types[type][i]));
			if (current_type == window.default_address_types[type][i])
				item.style.fontWeight = 'bold';
			item._type = window.default_address_types[type][i];
			item.onclick = function() { onchanged(this._type); };
			item.className = "context_menu_item";
			below_element._context.addItem(item);
		}
		if (show_other) {
			var item = document.createElement('DIV');
			item.appendChild(document.createTextNode("Other:"));
			var input = document.createElement("INPUT");
			input.type = 'text';
			input.maxLength = 100;
			input.size = 15;
			input.style.marginLeft = "5px";
			item.appendChild(input);
			below_element._context.onclose = function() {
				if (input.value.checkVisible())
					onchanged(input.value.trim());
				below_element._context = null;
			};
			input.onkeypress = function(e) {
				var ev = getCompatibleKeyEvent(e);
				if(ev.isEnter) below_element._context.hide();
			};
			below_element._context.addItem(item, true);
		} else {
			below_element._context.onclose = function() {
				below_element._context = null;
			};
		}
		below_element._context.showBelowElement(below_element);
	});
}

function parsePostalAddress(str, handler) {
	var names = str.split(",");
	for (var i = 0; i < names.length; ++i) {
		names[i] = names[i].trim();
		if (names[i].length == 0) {
			names.splice(i,1);
			i--;
		}
	}
	if (names.length == 0) {
		handler(null);
		return;
	}
	window.top.geography.getCountryData(window.top.default_country_id, function(country_data) {
		var remaining = [];
		var area = window.top.geography.searchAreaByNames(country_data, names, remaining);
		if (!area) {
			handler(null);
			return;
		}
		var addr = new PostalAddress(-1, window.top.default_country_id, window.top.geography.getGeographicAreaText(country_data, area), null, null, null, null, remaining.length == 0 ? null : remaining.join(", "), "");
		handler(addr);
	});
}

/**
 * Object representing addresses attached to a people or an organization: format used by field_addresses, given by AddressDataDisplay 
 * @param {String} type "people" or "organization"
 * @param {Number} type_id people ID or organization ID
 * @param {Array} addresses list of PostalAddress objects
 */
function PostalAddressesData(type, type_id, addresses) {
	this.type = type;
	this.type_id = type_id;
	this.addresses = addresses;
}


/**
 * Contact
 * @param {Number} id contact ID from database
 * @param {String} type one of "email", "phone", or "IM"
 * @param {String} sub_type type of contact (Work, Home...)
 * @param {String} contact the contact text (the email, or the phone, or the Instant Messager username) 
 */
function Contact(id, type, sub_type, contact) {
	this.id = id;
	this.type = type;
	this.sub_type = sub_type;
	this.contact = contact;
}

/**
 * Object representing contacts attached to a people or an organization: format used by field_contact_type, given by ContactDataDisplay 
 * @param {String} type "people" or "organization"
 * @param {Number} type_id people ID or organization ID
 * @param {Array} contacts list of Contact objects
 */
function ContactsData(type, type_id, contacts) {
	this.type = type;
	this.type_id = type_id;
	this.contacts = contacts;
}

window.default_contact_types = {
	'email': ["Professional","Personal"],
	'phone': ["Professional Mobile","Professional Landline","Personal Mobile","Personal Landline","Office"],
	'IM': ["Skype"]
};

function showContactTypeMenu(below_element,type,current_type,show_other,onchanged) {
	require("context_menu.js",function() {
		if (below_element._context) below_element._context.hide();
		below_element._context = new context_menu();
		for (var i = 0; i < window.default_contact_types[type].length; ++i) {
			var item = document.createElement('DIV');
			item.appendChild(document.createTextNode(window.default_contact_types[type][i]));
			if (current_type == window.default_contact_types[type][i])
				item.style.fontWeight = 'bold';
			item._type = window.default_contact_types[type][i];
			item.onclick = function() { onchanged(this._type); };
			item.className = "context_menu_item";
			below_element._context.addItem(item);
		}
		if (show_other) {
			var item = document.createElement('DIV');
			item.appendChild(document.createTextNode("Other:"));
			var input = document.createElement("INPUT");
			input.type = 'text';
			input.maxLength = 100;
			input.size = 15;
			input.style.marginLeft = "5px";
			item.appendChild(input);
			below_element._context.onclose = function() {
				if (input.value.checkVisible())
					onchanged(input.value.trim());
				below_element._context = null;
			};
			input.onkeypress = function(e) {
				var ev = getCompatibleKeyEvent(e);
				if(ev.isEnter) below_element._context.hide();
			};
			below_element._context.addItem(item, true);
		} else {
			below_element._context.onclose = function() {
				below_element._context = null;
			};
		}
		below_element._context.showBelowElement(below_element);
	});
}

/**
 * Object representing an organization, used by organization.js
 * @param {Number} id organization ID in database
 * @param {String} name organization name
 * @param {String} creator used to identify on which part of the application the organization belongs to, and so which users can access it (i.e. selection, or external relations...)
 * @param {Array} types_ids list of organization type ids
 * @param {Array} contacts list of Contact
 * @param {Array} addresses list of PostalAddress
 * @param {Array} contact_points list of ContactPoint
 */
function Organization(id, name, creator, types_ids, contacts, addresses, contact_points) {
	this.id = id;
	this.name = name;
	this.creator = creator;
	this.types_ids = types_ids;
	this.contacts = contacts;
	this.addresses = addresses;
	this.contact_points = contact_points;
}

/**
 * Contact point of an organization
 * @param {Number} organization_id ID of the organization this contact point belongs to
 * @param {People} people People object for this contact point
 * @param {String} designation designation of this people in the organization (i.e. director, IT manager...)
 */
function ContactPoint(organization_id, people, designation) {
	this.organization_id = organization_id;
	this.people = people;
	this.designation = designation;
}