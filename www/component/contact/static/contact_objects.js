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
	this.street = street ? street : "";
	this.street_number = street_number ? street_number : "";
	this.building = building ? building : "";
	this.unit = unit ? unit : "";
	this.additional = additional ? additional : "";
	this.address_type = address_type ? address_type : "";
	this.lat = lat;
	this.lng = lng;
}

/**
 * Copy information from a PostalAddress to another
 * @param {PostalAddress} addr the address to fill
 * @param {PostalAddress} from the address from which to fill
 */
function updatePostalAddress(addr, from) {
	addr.id = from.id;
	addr.country_id = from.country_id;
	addr.geographic_area = from.geographic_area;
	addr.street = from.street;
	addr.street_number = from.street_number;
	addr.building = from.building;
	addr.unit = from.unit;
	addr.additional = from.additional;
	addr.address_type = from.address_type;
	addr.lat = from.lat;
	addr.lng = from.lng;
}

/** Default address types to propose */
window.default_address_types = {
	/** For people */
	'people':['Home','Family','Birthplace','Work'],
	/** For organization */
	'organization':['Office']
};
/**
 * Show a context menu for the user to choose an address type
 * @param {Element} below_element where to display the context menu
 * @param {String} type either 'people' or 'organization'
 * @param {String} current_type the current address type to indicate in bold, or null
 * @param {Boolean} show_other if true, we will propose to the user to enter a new type
 * @param {Function} onchanged called with new new type as parameter, when the user select a type on the menu
 */
function showAddressTypeMenu(below_element,type,current_type,show_other,onchanged) {
	require("context_menu.js",function() {
		if (below_element._context) below_element._context.hide();
		below_element._context = new context_menu();
		var createItem = function(name) {
			var item = document.createElement('DIV');
			item.appendChild(document.createTextNode(name));
			if (current_type == name)
				item.style.fontWeight = 'bold';
			item._type = name;
			item.onclick = function() { onchanged(this._type); };
			item.className = "context_menu_item";
			below_element._context.addItem(item);
		}
		for (var i = 0; i < window.default_address_types[type].length; ++i)
			createItem(window.default_address_types[type][i]);
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
		service.json("contact","get_existing_address_types",{type:type},function(list) {
			if (!below_element._context) return;
			if (list.lenth > 0)
				below_element._context.addSeparator();
			for (var i = 0; i < list.length; ++i)
				if (window.default_address_types[type].indexOf(list[i]) < 0)
					createItem(list[i]);
			layout.changed(below_element._context.element);
		});
		below_element._context.showBelowElement(below_element);
	});
}

/**
 * Try to determine the different part of a PostalAddress from a simple string
 * @param {String} str the string to parse
 * @param {Function} handler called with the PostalAddress as parameter, or null if we cannot determine anything
 */
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

/** Default contact types to propose to the user */
window.default_contact_types = {
	/** For email */
	'email': ["Professional","Personal"],
	/** For phone */
	'phone': ["Professional Mobile","Professional Landline","Personal Mobile","Personal Landline","Office"],
	/** For Instant Messaging
	 * @no_name_check
	 */
	'IM': ["Skype"]
};

/**
 * Show a context menu for the user to choose a contact type
 * @param {Element} below_element where to display the context menu
 * @param {String} type either 'email', 'phone' or 'IM'
 * @param {String} current_type the current contact type to indicate in bold, or null
 * @param {Boolean} show_other if true, we will propose to the user to enter a new type
 * @param {Function} onchanged called with new new type as parameter, when the user select a type on the menu
 */
function showContactTypeMenu(below_element,type,current_type,show_other,onchanged) {
	require("context_menu.js",function() {
		if (below_element._context) below_element._context.hide();
		below_element._context = new context_menu();
		var createItem = function(name) {
			var item = document.createElement('DIV');
			item.appendChild(document.createTextNode(name));
			if (current_type == name)
				item.style.fontWeight = 'bold';
			item._type = name;
			item.onclick = function() { onchanged(this._type); };
			item.className = "context_menu_item";
			below_element._context.addItem(item);
		};
		for (var i = 0; i < window.default_contact_types[type].length; ++i)
			createItem(window.default_contact_types[type][i]);
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
		service.json("contact","get_existing_contact_types",{type:type},function(list) {
			if (!below_element._context) return;
			if (list.lenth > 0)
				below_element._context.addSeparator();
			for (var i = 0; i < list.length; ++i)
				if (window.default_contact_types[type].indexOf(list[i]) < 0)
					createItem(list[i]);
			layout.changed(below_element._context.element);
		});
	});
}

/**
 * Object representing an organization, used by organization.js
 * @param {Number} id organization ID in database
 * @param {String} name organization name
 * @param {String} creator used to identify on which part of the application the organization belongs to, and so which users can access it (i.e. selection, or external relations...)
 * @param {Array} types_ids list of organization type ids
 * @param {Array} general_contacts list of Contact
 * @param {Array} general_contact_points list of ContactPoint
 * @param {Array} locations list of OrganizationLocation
 */
function Organization(id, name, creator, types_ids, general_contacts, general_contact_points, locations) {
	this.id = id;
	this.name = name;
	this.creator = creator;
	this.types_ids = types_ids;
	this.general_contacts = general_contacts;
	this.general_contact_points = general_contact_points;
	this.locations = locations;
}

function OrganizationLocation(name, address, contacts, contact_points) {
	this.name = name;
	this.address = address;
	this.contacts = contacts;
	this.contact_points = contact_points;
}

/**
 * Contact point of an organization
 * @param {Number} organization_id ID of the organization this contact point belongs to
 * @param {People} people People object for this contact point
 * @param {String} designation designation of this people in the organization (i.e. director, IT manager...)
 */
function ContactPoint(organization_id, people, designation, contacts, attached_location) {
	this.organization_id = organization_id;
	this.people = people;
	this.designation = designation;
	this.contacts = contacts;
	this.attached_location = attached_location;
}