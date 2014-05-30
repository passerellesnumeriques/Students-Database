/**
 * @param {Number} id exam subject ID
 * @param {String} name exam subject name
 * @param {Number} max_score exam subject score
 * @param {Array} parts array of Exam Subject Part
 */
function ExamSubject (id, name, max_score, parts){
	this.id = id;
	this.name = name;
	this.max_score = max_score;
	this.parts = parts;
}

/**
 * @param {Number} id exam subject part ID
 * @param {Number} index exam subject part index within subject
 * @param {String} name exam subject part name
 * @param {Number} max_score part max score
 * @param {Array} questions array of Exam Subject Question
 */
function ExamSubjectPart(id, index, name, max_score, questions){
	this.id = id;
	this.index = index;
	this.name = name;
	this.max_score = max_score;
	this.questions = questions;
}

/**
 * @param {Number} id question ID
 * @param {Number} index question index within the part
 * @param {Number} max_score question score
 * @param {String} correct_answer correct answer
 * @param {Number} choices number of possible answers
 */
function ExamSubjectQuestion(id, index, max_score, correct_answer, choices){
	this.id = id;
	this.index = index;
	this.max_score = max_score;
	this.correct_answer = correct_answer;
	this.choices = choices;
}
