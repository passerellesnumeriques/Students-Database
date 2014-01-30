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
 */
function PostalAddress(id, country_id, geographic_area, street, street_number, building, unit, additional, address_type) {
	this.id = id;
	this.country_id = country_id;
	if(geographic_area)
		this.geographic_area = geographic_area;
	else{
		this.geographic_area = {};
		this.geographic_area.id = null;
		this.geographic_area.text = null;
	}
	this.street = street;
	this.street_number = street_number;
	this.building = building;
	this.unit = unit;
	this.additional = additional;
	this.address_type = address_type;
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

/**
 * Object representing an organization, used by organization.js
 * @param {Number} id organization ID in database
 * @param {String} name organization name
 * @param {String} creator used to identify on which part of the application the organization belongs to, and so which users can access it (i.e. selection, or external relations...)
 * @param {Array} types_ids list of organization type ids
 * @param {Array} contacts list of Contact
 * @param {Array} addresses list of PostalAddress
 * @param {Array} contact_points list of ContactPoint
 * @returns
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
 * @param {Number} people_id people id of this contact point
 * @param {String} first_name first name of the people (taken from the people table in database)
 * @param {String} last_name last name of the people (taken from the people table in database)
 * @param {String} designation designation of this people in the organization (i.e. director, IT manager...)
 */
function ContactPoint(people_id, first_name, last_name, designation) {
	this.people_id = people_id;
	this.first_name = first_name;
	this.last_name = last_name;
	this.designation = designation;
}