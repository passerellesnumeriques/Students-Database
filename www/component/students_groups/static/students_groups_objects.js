/**
 * A group
 * @param {Number} id id
 * @param {String} name name
 * @param {Number} type_id StudentsGroupType id
 * @param {Number} period_id BatchPeriod id
 * @param {Number} specialization_id Specialization id
 * @param {Number} parent_id parent group's id or null
 * @param {Boolean} can_have_sub_groups
 */
function StudentsGroup(id, name, type_id, period_id, specialization_id, parent_id) {
	this.id = id;
	this.name = name;
	this.type_id = type_id;
	this.period_id = period_id;
	this.specialization_id = specialization_id;
	this.parent_id = parent_id;
	this.sub_groups = [];
}