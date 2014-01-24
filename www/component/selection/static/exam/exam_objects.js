/**
 * Exam Subject
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
 * Exam Subject Part
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
 * Exam Subject Question
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