/**
 * @param {integer}id
 * @param {string}name
 * @param {float}max_score
 * @param {array}parts array of Exam Subject Part
 */
function ExamSubject (id, name, max_score, parts){
	this.id = id;
	this.name = name;
	this.max_score = max_score;
	this.parts = parts;
}

/**
 * @param {integer}id
 * @param {integer}index
 * @param {string}name
 * @param {float}max_score
 * @param {array}questions array of Exam Subject Question
 */
function ExamSubjectPart(id, index, name, max_score, questions){
	this.id = id;
	this.index = index;
	this.name = name;
	this.max_score = max_score;
	this.questions = questions;
}

/**
 * @param {integer}id
 * @param {integer}index
 * @param {float}max_score
 * @param {string}correct_answer
 * @param {integer}choices
 */
function ExamSubjectQuestion(id, index, max_score, correct_answer, choices){
	this.id = id;
	this.index = index;
	this.max_score = max_score;
	this.correct_answer = correct_answer;
	this.choices = choices;
}

/* Exam center objects */

/**
 * @param {number} id
 * @param {number | null} geographic_area the id of the geographic area where the Exam Center is located. Once host is set, must be the area of the host_address 
 * @param {string} name the name of the ExamCenter (the one displayed in the datalist). By default, this name is the geographic area text
 * @param {Array} partners containing the ExamCenterPartner objects
 * @param {ExamCenterISLinked} IS_linked the informations sessions linked to this exam center
 */
function ExamCenterData(id, geographic_area, name, partners, IS_linked){
	this.id = id;
	this.geographic_area = geographic_area;
	this.name = name;
	this.partners = partners;
	this.IS_linked = IS_linked;
}

/**
 * @param {number} organization the id of the partner organization 
 * @param {string} organization_name
 * @param {boolean} host. If true, the center address is picked from this partner
 * @param {number} host_address the id of the host address selected (an organization can have several addresses)
 * @param {ExamCenterPartnersContactPoints} contact_points_selected the ids of the contacts points selected for the ExamCenter
 * */
function ExamCenterPartner(organization, organization_name, host, host_address, contact_points_selected){
	this.organization = organization;
	this.organization_name = organization_name;
	this.host = host;
	this.host_address = host_address;
	this.contact_points_selected = contact_points_selected;
}

/**
 * @param {Array} information_sessions IDs of the informations sessions linked to the ExamCenter
 */
function ExamCenterISLinked(information_sessions){
	this.information_sessions = information_sessions;
}

/**
 * @param {Array} contact_points coming from contact/service/get_json_contact_points_no_address
 * for each ExamCenterPartner (host or not)
 */
function ExamCenterPartnersContactPoints(contact_points){
	this.contact_points = contact_points;
}

/**
 * @param {Array} rooms array of ExamCenterRoom objects
 */
function ExamCenterRooms(rooms){
	this.rooms = rooms;
}

/**
 * @param {Number} id the room id
 * @param {String} name the room name
 * @param {Number} capacity the number of applicants that can be assigned to this room per session
 */
function ExamCenterRoom(id, name, capacity){
	this.id = id;
	this.name = name;
	this.capacity = capacity;
}

/** Exam sessions objects */

/**
 * @param {Number} exam_center ID of the exam center where the session takes place
 * @param {CalendarEvent} event related to this session
 * @param {Array} supervisors array of ExamSessionSupervisor objects
 */
function ExamSession(exam_center,event,supervisors){
	this.exam_center = exam_center;
	this.event = event;
	this.supervisors = supervisors;
}

/**
 * @param {People} people object
 */
function ExamSessionSupervisor(people){
	for(a in people){
		this[a] = people[a];
	}
}