/** Information about a geographic area, including its text to be displayed to the user
 * @param {Number} country_id country
 * @param {Number} division_id division
 * @param {Number} id area id
 * @param {String} text the text to be displayed
 */
function GeographicAreaText(country_id, division_id, id, text) {
	this.country_id = country_id;
	this.division_id = id;
	this.id = id;
	this.text = text;
}

/**
 * Structure used to represent a geographic area, in window.top.geography
 * @param {Number} id the id
 * @param {String} name the name
 * @param {Number} parent_id the parent or null
 */
function GeographicArea(id, name, parent_id) {
	this.area_id = id;
	this.area_name = name;
	this.area_parent_id = parent_id;
}

/**
 * Information about a country
 * @param {Number} country_id the id
 * @param {String} country_code the code
 * @param {String} country_name the name
 * @param {Number} north the north
 * @param {Number} west the west
 * @param {Number} south the south
 * @param {Number} east the east
 */
function CountryInfo(country_id, country_code, country_name, north, west, south, east) {
	this.country_id = country_id;
	this.country_code = country_code;
	this.country_name = country_name;
	this.north = north;
	this.west = west;
	this.south = south;
	this.east = east;
}
