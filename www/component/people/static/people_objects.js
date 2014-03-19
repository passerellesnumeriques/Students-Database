/**
 * People
 * @param {Number} id
 * @param {String} first_name
 * @param {String} last_name
 * @param {String} middle_name
 * @param {String} sex M or F
 * @param {Date} birthdate
 */
function People(id, first_name, last_name, middle_name, sex, birthdate, can_edit){
	this.id = id;
	this.first_name = first_name;
	this.last_name = last_name;
	this.middle_name = middle_name;
	this.sex = sex;
	this.birthdate = birthdate;
	this.can_edit = can_edit;
}