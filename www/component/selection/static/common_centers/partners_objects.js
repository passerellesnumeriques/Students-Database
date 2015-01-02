/**
 * Represent a partner organisation
 * @param {Number} center_id id of the center (IS,ExamCenter, or InterviewCenter)
 * @param {Organization} organization organization
 * @param {Boolean} host indicates if this is the host
 * @param {Number|null} host_address_id if this is the host, indicates which address of the host is actually hosting the event
 * @param {Array} selected_contact_points_id list of contact points which are selected for the event
 */
function SelectionPartner(center_id, organization, host, host_address_id, selected_contact_points_id) {
	this.center_id = center_id;
	this.organization = organization;
	this.host = host;
	this.host_address_id = host_address_id;
	this.selected_contact_points_id = selected_contact_points_id;
}