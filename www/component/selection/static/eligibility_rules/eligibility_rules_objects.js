/**
 * Objects about exam topics
 */

/**
 * ExamTopicForEligibilityRules object
 * @param {integer} id
 * @param {string} name
 * @param {number} max_score
 * @param {integer} number_questions
 * @param {array} subjects of ExamSubjectForTopic objects
 */
function ExamTopicForEligibilityRules(id, name, max_score, number_questions, subjects){
	this.id = id;
	this.name = name;
	this.max_score = max_score;
	this.number_questions = number_questions;
	this.subjects = subjects;
}

/**
 * ExamSubjectForTopic
 * Define a subject object as used in an ExamTopicForEligibilityRules object
 * In that context, a full_subject attribute is added
 * @param {integer} id
 * @param {string} name
 * @param {number} max_score
 * @param {array} parts of ExamSubjectPartForTopic objects 
 * @param {boolean} full_subject
 */
function ExamSubjectForTopic(id, name, max_score, parts, full_subject){
	var t = this;
	require("exam_objects.js",function(){
		t = new ExamSubject(id, name, max_score, parts);
		t.full_subject = full_subject;
	});
}

/**
 * ExamSubjectPartForTopic
 * Define a part object as used in an ExamTopicForEligibilityRules object
 * In that case, the questions attribute is set to []
 * @param {integer} id
 * @param {integer} index
 * @param {string} name
 * @param {number} max_score
 */
function ExamSubjectPartForTopic(id, index, name, max_score){
	var t = this;
	require("exam_objects.js",function(){
		t = new ExamSubjectPart(id, index, name, max_score, []);
	});
}

/**
 * EligibilityRule object
 * @param {Number} id
 * @param {Number|null} parent the id of the parent rule, null if root level
 * @param {Array} topics array of ExamTopicForEligibilityRule objects
 */
function EligibilityRule(id, parent, topics){
	this.id = id;
	this.parent = parent;
	this.topics = topics;
}

/**
 * ExamTopicForEligibilityRule<br/>
 * Defined a topic object as used in an EligibilityRule object<br/>
 * Componed of a topic object and a coefficient attribute
 * @param {Number} coefficient
 * @param {Number} expected the minimum grade expected for this topic
 * @param {Object} topic ExamTopicForEligibilityRules object
 */
function ExamTopicForEligibilityRule(coefficient, expected, topic){
	this.coefficient = coefficient;
	this.expected = expected;
	this.topic = topic;
}