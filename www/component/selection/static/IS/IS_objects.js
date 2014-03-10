/**
 * ISData (for IS profile page)
 * @param {number} id
 * @param {number | null} geographic_area the id of the geographic area where this IS takes place. Once host is set, must be the area of the host_address 
 * @param {number | null} date the id of the IS calendar event
 * @param {string} name the name of the IS (the one displayed in the datalist). By default, this name is the geograpihic area text
 * @param {number} number_boys_expected
 * @param {number}number_boys_real
 * @param {number} number_girls_expected
 * @param {number} number_girls_real
 * @param {array} partners containing the ISpartner objects
 */
function ISData (id, geographic_area, date, name, number_boys_expected, number_boys_real, number_girls_expected, number_girls_real, partners){
	this.id = id;
	this.geographic_area = geographic_area;
	this.date = date;
	this.number_boys_expected = number_boys_expected;
	this.number_boys_real = number_boys_real;
	this.number_girls_real = number_girls_real;
	this.number_girls_expected = number_girls_expected;
	this.partners = partners;
}

/**
 * ISPartner (for ISData object)
 * @param {number} organization the id of the partner organization 
 * @param {string} organization_name
 * @param {boolean} host. If true, the IS address is picked from this partner
 * @param {number} host_address the id of the host address selected (an organization can have several addresses)
 * @param {ISPartnersContactPoints} contact_points_selected the ids of the contacts points selected for the information sessions
 */
function ISPartner(organization, organization_name, host, host_address, contact_points_selected){
	this.organization = organization;
	this.organization_name = organization_name;
	this.host = host;
	this.host_address = host_address;
	this.contact_points_selected = contact_points_selected;
}

/**
 * ISPartnersContactPoints
 * @param {array} contact_points coming from contact/service/get_json_contact_points_no_address
 * for each ISpartner (host or not)
 */
function ISPartnersContactPoints(contact_points){
	this.contact_points = contact_points;
}
