/**
 * Room in an Exam Center
 * @param {Number} center_id exam center id
 * @param {Number} id room id
 * @param {String} name room name
 * @param {Number} capacity number of applicants the room can accomodate
 */
function ExamCenterRoom(center_id, id, name, capacity) {
	this.center_id = center_id;
	this.id = id;
	this.name = name;
	this.capacity = capacity;
}
