/**
 * People
 * @param {Number} id
 * @param {String} first_name
 * @param {String} last_name
 * @param {String} middle_name
 * @param {String} sex M or F
 * @param {Date} birthdate
 * @param {Nuber} picture_version
 */
function People(id, first_name, last_name, middle_name, sex, birthdate, picture_version){
	this.id = id;
	this.first_name = first_name;
	this.last_name = last_name;
	this.middle_name = middle_name;
	this.sex = sex;
	this.birthdate = birthdate;
	this.picture_version = picture_version;
}