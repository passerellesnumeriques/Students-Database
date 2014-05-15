function ExamCenterApplicant(people, exam_session_id, exam_room_id) {
	this.people = people;
	this.exam_session_id = exam_session_id;
	this.exam_room_id = exam_room_id;
}

function ExamCenterRoom(center_id, id, name, capacity) {
	this.center_id = center_id;
	this.id = id;
	this.name = name;
	this.capacity = capacity;
}