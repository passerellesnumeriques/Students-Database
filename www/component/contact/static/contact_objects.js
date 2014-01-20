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
	this.geographic_area = geographic_area;
	this.street = street;
	this.street_number = street_number;
	this.building = building;
	this.unit = unit;
	this.additional = additional;
	this.address_type = address_type;
}
