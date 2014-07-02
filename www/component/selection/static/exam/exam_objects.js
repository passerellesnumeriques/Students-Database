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
 * @param {String} type question type (i.e mcq_single" or "mcq_multi" or "number" or "text)
 * @param {String} type_config 
 */
function ExamSubjectQuestion(id, index, max_score, type, type_config){
	this.id = id;
	this.index = index;
	this.max_score = max_score;
	this.type = type;
	this.type_config = type_config;
}

/* @function gridFieldType : get grid widget field type
* @param {ExamSubjectQuestion} question
* @returns {String} field type of grid widget
*/
function questionGridFieldType(question)
{
	var field_type;
    
    switch (question.type){
       case 'mcq_single':
	  field_type='field_enum';
	  break;
       case 'mcq_multi':
	  //TODO
	  break;
       case 'number':
	  field_type='field_decimal';
	  break;
       case 'text':
	  field_type='field_text';
	  break;
    }
    
    return field_type;
}

 /* @function gridFieldArgs : get grid widget field args
   * @param {ExamSubjectQuestion} question 
   * @returns {Object} field args of grid widget
  */
function questionGridFieldArgs(question){
	switch (question.type){
	 case 'mcq_single':
	    var field_args={
	     "possible_values":[],
	      "can_be_empty":false};            
	    //type_config contains the number of possible choices
	     for(j=0;j<question.type_config;++j)
	    {
	       var car=String.fromCharCode(j+65);// 65 is for letter 'A'
	       field_args.possible_values.push(car);
	    }
	    break;
	 case 'mcq_multi':
	    //TODO
	    break;
	 case 'number':
	    /* assuming field type_config is formatted as "integer_digits,decimal_digits"*/
	    var str=question.type_config.split(",");
	    var field_args={
	       can_be_null:true,
	       integer_digits:parseInt(str[0]),
	       decimal_digits:parseInt(str[1]),
	       };
	       break;
	
	 case 'text':
	    //TODO
	    break;
	}
	
	return field_args;
}
