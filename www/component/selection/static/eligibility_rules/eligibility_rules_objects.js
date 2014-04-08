/**
 * Objects about exam topics
 */

/**
 * ExamTopicForEligibilityRules object
 * @param {Number} id exam topic ID
 * @param {String} name exam topic name
 * @param {Number} max_score max score of the topic (based on subjects parts ones)
 * @param {Number} number_questions number of questions within the topic
 * @param {Array} subjects of ExamSubjectForTopic objects
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
 * @param {Number} id exam subject ID
 * @param {String} name exam subject name
 * @param {Number} max_score max score of the subject
 * @param {Array} parts of ExamSubjectPartForTopic objects 
 * @param {Boolean} full_subject true this subject is declared as full subject for the topic
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
 * @param {Number} id exam subject part ID
 * @param {Number} index index of the part into the subject
 * @param {String} name exam subject part name
 * @param {Number} max_score exam subject part max score 
 */
function ExamSubjectPartForTopic(id, index, name, max_score){
	var t = this;
	require("exam_objects.js",function(){
		t = new ExamSubjectPart(id, index, name, max_score, []);
	});
}

/**
 * EligibilityRule object
 * @param {Number} id eligibility rule ID
 * @param {Number|NULL} parent the id of the parent rule, null if root level
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