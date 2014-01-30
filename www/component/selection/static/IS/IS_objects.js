/**
 * ISData (for IS profile page)
 * @param {integer} id
 * @param {integer | null} address, id of the custom address (not from the partner ones). If not null, no partner can be set as the host
 * @param {integer | null} fake_organization id of the fake organization created at the same time as the custom address, because a postal address must be linked to a people or to an organization
 * @param {integer | null} date the id of the IS calendar event
 * @param {string} name the name of the IS (the one displayed in the datalist). By default, this name is the geograpihic area text
 * @param {integer} number_boys_expected
 * @param {integer}number_boys_real
 * @param {integer} number_girls_expected
 * @param {integer} number_girls_real
 * @param {array} partners containing the ISpartner objects
 */
function ISData (id, address, fake_organization, date, name, number_boys_expected, number_boys_real, number_girls_expected, number_girls_real, partners){
	this.id = id;
	this.address = address;
	this.fake_organization = fake_organization;
	this.date = date;
	this.number_boys_expected = number_boys_expected;
	this.number_boys_real = number_boys_real;
	this.number_girls_real = number_girls_real;
	this.number_girls_expected = number_girls_expected;
	this.partners = partners;
}

/**
 * ISPartner (for ISData object)
 * @param {integer} organization the id of the partner organization 
 * @param {string} organization_name
 * @param {boolean} host. If true, the IS address is picked from this partner
 * @param {integer} host_address the id of the host address selected (an organization can have several addresses)
 * @param {array} contact_points_selected the ids of the contacts points selected for the information sessions
 */
function ISPartner(organization, organization_name, host, host_address, contact_points_selected){
	this.organization = organization;
	this.organization_name = organization_name;
	this.host = host;
	this.host_address = host_address;
	this.contact_points_selected = contact_points_selected;
}

/**
 * ISOrganizationContacts
 * @param {array} contacts. Contains the contacts object coming from contact/service/get_contacts
 * for each ISpartner
 */
function ISOrganizationContacts(contacts){
	this.contacts = contacts;
}

/**
 * ISPartnersContactPoints
 * @param {array} contact_points coming from contact/service/get_json_contact_points_no_address
 * for each ISpartner
 */
function ISPartnersContactPoints(contact_points){
	this.contact_points = contact_points;
}
