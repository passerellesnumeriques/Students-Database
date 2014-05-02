/**
 * Create a table to manage an exam subject part
 * @param {Object} part
 * @param {HTMLElement} container the container to fill up
 * @param {Boolean} can_edit
 * @param {Boolean} can_remove
 * @param {Boolean} can_add
 * @param {Boolean} display_questions_detail a row is added for each question of this part
 * @param {Boolean} display_correct_answer if true a column is added for the question correct answer
 * @param {Boolean} display_choices if true a column is added for the number of choices
 * @param {Number} question_index_before the number of questions already present before this part within the exam subject<br/>
 * This figure is used to set the index of the question row (the index attribute of a question is the one into its belonging part)
 * @param {Boolean} no_question if yes, no question is taken into account (so no need to give the part.questions array) when the table is generated, so only the header is displayed.<br/>
 * Note: in this mode, the part cannot be editable
 * @param {HTMLElement} element_ending_title an element to add at the end of the header row (usually the change part index button, see manage_exam_subject.js)
 * @param {Boolean} td_head_instead_of_th if true, the header row contains td elements instead of tr elements, so no bold letters
 */
function manage_exam_subject_part_questions(part, container, can_edit, can_remove, can_add, display_questions_detail, display_correct_answer,display_choices, question_index_before, no_question, element_ending_title, td_head_instead_of_th){	
	var t = this;
	t.table = document.createElement("table");
	t.ordered = null;
	t.onmanagerow = new Custom_Event();
	t.onupdatescore = new Custom_Event();
	t.focusonagiveninput = new Custom_Event();
	//question_index_before is set as an attribute 
	// that way it can be updated from outside via reset method
	t.question_index_before = question_index_before;
	
	/**
	 * Launch the process. <br/>
	 * Set the table header and the table body, if display_questions_detail == true
	 */
	t._init = function(){
		t._setTableHeader();
		if(display_questions_detail)
			t._setTableBody();
		container.appendChild(t.table);
	};
	
	/**
	 * Set the table header<br/>
	 * Create a thead element containing the main data about the part (name, index, number of questions, score)<br/>
	 * This method only add the thead to the table and calls the _setHeaderContent method to set its content
	 */
	t._setTableHeader = function(){
		thead = document.createElement("thead");
		t.tr_head = document.createElement("tr");
		if(!td_head_instead_of_th)
			t.th_head = document.createElement("th");
		else
			t.th_head = document.createElement("td");
		t._setHeaderContent();
		t.tr_head.appendChild(t.th_head);
		thead.appendChild(t.tr_head);
		t.table.appendChild(thead);
	};
	
	/**
	 * Set the content of the header<br/>
	 * If the display_questions_detail is set as false, the number of questions within the part is added at the end of the title
	 */
	t._setHeaderContent = function(){
		if(t.th_head.parentNode == t.tr_head){
			t.tr_head.removeChild(t.th_head);
			delete t.th_head;
			if(!td_head_instead_of_th)
				t.th_head = document.createElement("th");
			else
				t.th_head = document.createElement("td");
			t.tr_head.appendChild(t.th_head);
		}
		if(display_correct_answer && (can_edit || can_remove || can_add)){
			if(!display_choices)
				t.th_head.colSpan = 5;
			else
				t.th_head.colSpan = 4;
		}
		else if(!display_correct_answer && !can_edit && !can_remove && !can_add){
			if(!display_choices)
				t.th_head.colSpan = 3;
			else
				t.th_head.colSpan = 2;
		}
		else{
			if(!display_choices)
				t.th_head.colSpan = 4;
			else
				t.th_head.colSpan = 3;
		}
		t.th_head.style.whiteSpace = "nowrap";
		t.th_head.style.textAlign = "left";
		
		var max_score = typeof part.max_score == "number" ? part.max_score : parseFloat(part.max_score);
		max_score = max_score.toFixed(2);
		var text1, text2, input;
		if(can_edit){
			text1 = document.createTextNode("PART "+part.index+" - ");
			input = document.createElement("input");
			input.type = "text";
			inputAutoresize(input,7);
			if(part.name == null || (part.name != null && !part.name.checkVisible())){
				part.name = null;
				input.value = "Part name";
				input.style.fontStyle = "italic";
				input.style.color = "#808080";
			} else {
				input.value = part.name.uniformFirstLetterCapitalized();
			}
			input.onfocus = function(){
				if(this.value == "Part name")
					this.value = null;
				this.style.color = "";
				this.style.fontStyle = "";
			};
			input.onblur = function(){
				if(this.value == null){
					input.value = "Part name";
					input.style.fontStyle = "italic";
					input.style.color = "#808080";
					part.name = null;
				} else if (!this.value.checkVisible()){
					input.value = "Part name";
					input.style.fontStyle = "italic";
					input.style.color = "#808080";
					part.name = null;
				} else {
					part.name = this.value.uniformFirstLetterCapitalized();
					this.value = this.value.uniformFirstLetterCapitalized();
				}
			};
		} else {
			//temporary update the part name not to display "null"
			if(part.name == null)
				part.name = "";
		}
		if(display_questions_detail){
			if(!can_edit)
				t.th_head.innerHTML = "PART "+part.index+" - "+part.name+" - "+max_score+" "+getGoodSpelling("point",part.max_score);
			else{
				text2 = document.createTextNode(" - "+max_score+" "+getGoodSpelling("point",part.max_score));
			}
		} else {
			
			if(!no_question){
				if(can_edit){
					text2 = document.createTextNode(" - "+max_score+" "+getGoodSpelling("point",part.max_score)+" - "+part.questions.length+" "+getGoodSpelling("question", part.questions.length));
				} else
					t.th_head.innerHTML = "PART "+part.index+" - "+part.name+" - "+max_score+" "+getGoodSpelling("point",part.max_score)+" - "+part.questions.length+" "+getGoodSpelling("question", part.questions.length);
			} else {
				if(can_edit){
					text2 = document.createTextNode(" - "+max_score+" "+getGoodSpelling("point",part.max_score));
				} else
					t.th_head.innerHTML = "PART "+part.index+" - "+part.name+" - "+max_score+" "+getGoodSpelling("point",part.max_score);
			}
		}
		if(can_edit){
			t.th_head.appendChild(text1);
			t.th_head.appendChild(input);
			t.th_head.appendChild(text2);
		} else {
			if(part.name == "")
				part.name = null;
		}
		if(element_ending_title)
			t.th_head.appendChild(element_ending_title);
	};
	
	/**
	 * Set the body of the table<br/>
	 * A row is added for each question<br/>
	 * The index displayed in the first row cell is the one of the question into the whole subject<br/>
	 * If editable, the data are displayed into inputs<br/>
	 * Remove, insert before / after buttons are added at the end of the row, based on the user rights<br/>
	 * The colspan of the data displayed are adapted to the config mode<br/>
	 * A first row containing the caption is also added ("Score"...)
	 */
	t._setTableBody = function(){
		var tbody = document.createElement("tbody");		
		t.ordered = t._getOrderedQuestionsIndexInQuestions();
		if(t.ordered.length > 0){
			for(var i = 0; i < t.ordered.length; i++){
				if(i == 0){
					var tr_head = document.createElement("tr");
					tr_head.appendChild(document.createElement("TH"));
					var th1 = document.createElement("th");
					th1.innerHTML = "Score";
					tr_head.appendChild(th1);
				}
				var tr = document.createElement("tr");
				var td0 = document.createElement("td");
				td0.innerHTML = "Question " + (parseInt(t.question_index_before) + i + 1) +" - ";
				td0.style.textAlign = "right";
				tr.appendChild(td0);
				var td1 = document.createElement("td");
				if(can_edit){
					var input = document.createElement("input");
					input.type = 'text';
					input.size = 5;
					//give a unique id to the input, to be able to get it at anytime
					input.id = "question"+part.index+"."+part.questions[t.ordered[i]].index;
					input.value = part.questions[t.ordered[i]].max_score;
					input.style.textAlign = "right";
					input.index_in_ordered = i;
					input.oninput = function(){
						var temp = parseFloat(this.value);
						if(isNaN(temp))
							part.questions[t.ordered[this.index_in_ordered]].max_score = 0;
						else
							part.questions[t.ordered[this.index_in_ordered]].max_score = temp;
						//update the total score of the part
						t._updateTotalScore();
						t.onupdatescore.fire();
					};
					input.onkeypress = function(e) {
						var ev = getCompatibleKeyEvent(e);
						if (ev.isEnter){
							var new_index = parseInt(part.questions[t.ordered[this.index_in_ordered]].index)+1;
							var new_input_id = "question"+part.index+"."+new_index;
							t._onInsertAfter(this.index_in_ordered);
							t.onupdatescore.fire();
							t.onmanagerow.fire(t.question_index_before);
							t.focusonagiveninput.fire(new_input_id);
						}
					};
					td1.appendChild(input);
					td1.appendChild(document.createTextNode("pt(s)"));
				} else {
					td1.innerHTML += part.questions[t.ordered[i]].max_score + "pt(s)";
				}
				tr.appendChild(td1);
				if(display_correct_answer)
					t._addOptionalData("correct_answer", i, tr_head, tr);
				if(display_choices)
					t._addOptionalData("choices", i , tr_head,tr);
				if(can_remove || can_add){
					var td3 = document.createElement("td");
					tr.menu = [];
					if(can_remove){
						var remove = t._createButton("remove");
						remove.index_in_ordered = i;
						remove.onclick = function(){
							var new_index = parseInt(part.questions[t.ordered[this.index_in_ordered]].index)-1;
							var new_input_id = "question"+part.index+"."+new_index;
							t._onRemove(this.index_in_ordered, t.ordered[this.index_in_ordered]);
							t.onmanagerow.fire(t.question_index_before);
							t.focusonagiveninput.fire(new_input_id);
						};
						td3.appendChild(remove);
						tr.menu.push(remove);
						remove.style.visibility = "hidden";
					}
					if(can_add){
						var before = t._createButton("before");
						before.index_in_ordered = i;
						before.onclick = function(){
							t._onInsertBefore(this.index_in_ordered);
							t.onmanagerow.fire(t.question_index_before);
							var new_input_id = "question"+part.index+"."+part.questions[t.ordered[this.index_in_ordered]].index;
							t.focusonagiveninput.fire(new_input_id);
						};
						var after = t._createButton("after");
						after.index_in_ordered = i;
						after.onclick = function(){
							t._onInsertAfter(this.index_in_ordered);
							t.onmanagerow.fire(t.question_index_before);
							var new_index = parseInt(part.questions[t.ordered[this.index_in_ordered]].index)+1;
							var new_input_id = "question"+part.index+"."+new_index;
							t.focusonagiveninput.fire(new_input_id);
						};
						td0.insertBefore(before, td0.childNodes[0]);
						td3.appendChild(after);
						before.style.visibility = "hidden";
						after.style.visibility = "hidden";
						tr.menu.push(before);
						tr.menu.push(after);
					}
					tr.appendChild(td3);
					/**
					 * Display the remove, and inserts buttons only when the mouse is over the tr
					 */
					require("animation.js",function() {
						animation.appearsOnOver(tr, tr.menu);
					});
				}
				if(i == 0)
					tbody.appendChild(tr_head);
				tbody.appendChild(tr);
			}
		} else {
			var tr = document.createElement("tr");
			var td1 = document.createElement("td");
			tr.appendChild(td1);
			td1.innerHTML = "<i>This part is empty</i>";
			if(can_add){
				td1.colSpan = 1;
				var td2 = document.createElement("td");
				var content = "<img src = '"+theme.icons_16.add+"'/> Insert";
				var insert = t._createButton(content);
				insert.onclick = function(){
					t._onFirstInsert();
					t.onmanagerow.fire(t.question_index_before);
					var new_input_id = "question"+part.index+".1";
					t.focusonagiveninput.fire(new_input_id);
				};
				td2.appendChild(insert);
				tr.appendChild(td2);
				//match colSpan with the one of thead
				if(display_correct_answer){
					if(!display_choices)
						td2.colSpan = 3;
					else
						td2.colSpan = 2;
				}
				else{
					if(!display_choices)
						td2.colSpan = 2;
					else
						td2.colSpan = 1;
				}
			} else {
				if(display_correct_answer){
					if(!display_choices)
						td1.colSpan = 4;
					else
						td1.colSpan = 3;
				} else {
					if(!display_choices)
						td1.colSpan = 3;
					else
						td1.colSpan = 2;
				}
			}
			tbody.appendChild(tr);
		}
		t.table.appendChild(tbody);
	};
	
	/**
	 * Add the optional data on a question row (number of choices| correct answer fields)<br/>
	 * The caption row is alos updated
	 * @param {String} attribute the name of the attribute (correct_answer|choices)
	 * @param {Number} i the index of the question into the t.ordered array
	 * @param {HTMLElement} tr_head the tr element of the caption row
	 * @param {HTMLElement} tr the row element
	 */
	t._addOptionalData = function(attribute, i, tr_head, tr){
		if(i == 0){
			var th2 = document.createElement("th");
			if(attribute == "correct_answer")
				th2.innerHTML = "Correct Answer";
			else if(attribute == "choices")
				th2.innerHTML = "Choices";
			tr_head.appendChild(th2);
		}
		var td2 = document.createElement("td");
		if(can_edit){
			var input = document.createElement("input");
			input.type = "text";
			inputAutoresize(input, 5);
			input.value = part.questions[t.ordered[i]][attribute];
			input.index_in_ordered = i;
			input.style.textAlign = "right";
			input.oninput = function(){
				if(this.value.checkVisible() && this.value != "")
					part.questions[t.ordered[this.index_in_ordered]][attribute] = this.value;
				else
					part.questions[t.ordered[this.index_in_ordered]][attribute] = null;
				// t.onupdatescore.fire();
			};
			input.onkeypress = function(e) {
				var ev = getCompatibleKeyEvent(e);
				if (ev.isEnter){
					var new_index = parseInt(part.questions[t.ordered[this.index_in_ordered]].index)+1;
					var new_input_id = "question"+part.index+"."+new_index;
					t._onInsertAfter(this.index_in_ordered);
					t.onmanagerow.fire(t.question_index_before);
					t.focusonagiveninput.fire(new_input_id);
				}
			};
			td2.appendChild(input);
		} else {
			var div = document.createElement("div");
			div.innerHTML = part.questions[t.ordered[i]][attribute];
			td2.appendChild(div);
		}
		td2.style.textAlign = "center";
		tr.appendChild(td2);
	};
	
	/**
	 * Method called by the insert question button, when the part is empty.<br/>
	 * A first question is inserted and the table is reseted
	 */
	t._onFirstInsert = function(){
		//create the question
		t._createQuestion(1);
		//reset table
		t.reset();
	};
	
	/**
	 * Method called by the remove question button<br/>
	 * The question is removed from part.questions<br/>
	 * The index of all the other questions are updated<br/>
	 * The table is reseted
	 * @param {Number} index_in_ordered the index of the question to remove into the t.ordered array
	 * @param {Number} index_in_questions the index of the question to remove into the part.questions array
	 */
	t._onRemove = function(index_in_ordered,index_in_questions){
		//update the index attribute of the following questions
		t._decreaseIndexAttribute(index_in_ordered);
		//remove the question
		part.questions.splice(index_in_questions,1);
		//reset table
		t.reset();
	};
	
	/**
	 * Method called by the inser question before button<br/>
	 * A question is inserted before the given one<br/>
	 * The index of all the other questions are updated<br/>
	 * The table is reseted
	 * @param {Number} index_in_ordered the index of the question before which a question will be added, into the t.ordered array
	 */
	t._onInsertBefore = function(index_in_ordered){
		//update the index attribute of the following questions
		if(typeof(index_in_ordered != "number"))
			index_in_ordered = parseInt(index_in_ordered);
		t._increaseIndexAttribute(index_in_ordered -1); //we insert before so the index attribute of the current question shall be increased too
		//create the question object
		t._createQuestion(index_in_ordered +1);
		//reset table
		t.reset();
	};
	
	/**
	 * Method called by the inser question after button<br/>
	 * A question is inserted after the given one<br/>
	 * The index of all the other questions are updated<br/>
	 * The table is reseted
	 * @param {Number} index_in_ordered the index of the question after which a question will be added, into the t.ordered array
	 */
	t._onInsertAfter = function(index_in_ordered){
		if(typeof(index_in_ordered) != "number")
			index_in_ordered = parseInt(index_in_ordered);
		//update the index attribute of the following questions
		t._increaseIndexAttribute(index_in_ordered);
		//create the question object
		t._createQuestion(index_in_ordered +2);
		//reset table
		t.reset();
	};
	
	/**
	 * Create a question into the part.questions array
	 * @param {Number} index_attribute_value the index attribute of the question to create 
	 */
	t._createQuestion = function(index_attribute_value){
		var index = part.questions.length;
		//the question is created with 1 as default score
		part.questions[index] = new ExamSubjectQuestion(-1,index_attribute_value,1,null,null);
	};
	
	/**
	 * Decrease the questions index attributes of 1 starting from a given question
	 * @param {Number} index_in_ordered the index in the t.ordered array of the question from which the process is started (this question is included)
	 */
	t._decreaseIndexAttribute = function(index_in_ordered){
		if(typeof(index_in_ordered != "number"))
			index_in_ordered = parseInt(index_in_ordered);
		//if last question, nothing to do
		if(index_in_ordered != t.ordered.length -1){
			var j = index_in_ordered;
			i = 1;
			while(j != t.ordered.length){
				var temp = index_in_ordered + i;
				part.questions[t.ordered[index_in_ordered + i]].index = parseInt(part.questions[t.ordered[index_in_ordered + i]].index) -1;
				i++;
				j = index_in_ordered + i;
			}
		}
	};
	
	/**
	 * Increase the questions index attributes of 1 starting from a given question
	 * @param {Number} index_in_ordered the index in the t.ordered array of the question from which the process is started (this question is NOT included)
	 */
	t._increaseIndexAttribute = function(index_in_ordered){
		index_in_ordered = parseInt(index_in_ordered);
		//if last question, nothing to do
		if(index_in_ordered != t.ordered.length -1){
			var j = index_in_ordered;
			i = 1;
			while(j != t.ordered.length){
				part.questions[t.ordered[index_in_ordered + i]].index = parseInt(part.questions[t.ordered[index_in_ordered + i]].index) +1;
				i++;
				j = index_in_ordered + i;
			}
		}
	};
	
	/**
	 * Reset the table by restarting the process
	 * @param {Number|null} new_questions_before the value of the questions_before attribute<br/>
	 * This value may have been updated, for instance on the manage_exam_subject page when an other part object (before this one) has been updated
	 */
	t.reset = function(new_questions_before){
		if(typeof(new_questions_before) != "undefined")
			t.question_index_before = new_questions_before;
		container.removeChild(t.table);
		delete t.table;
		t.table = document.createElement("table");
		delete t.ordered;
		t.ordered = null;
		t._updateTotalScore();
		t._init();
	};
	
	/**
	 * Get the display_questions_detail value
	 * @returns {Boolean} display_questions_detail
	 */
	t.getDisplayQuestionDetail = function(){
		return display_questions_detail;
	};
	
	/**
	 * Update the total score attribute and the header row<br/>
	 * Method called when the user updates a question score
	 */
	t._updateTotalScore = function(){
		var total = 0;
		for(var i = 0; i < part.questions.length; i++){
			if(part.questions[i].max_score == null || part.questions[i].max_score == "")
				continue;
			if(typeof(part.questions[i].max_score != "number"))
				part.questions[i].max_score = parseFloat(part.questions[i].max_score);
			if(isNaN(part.questions[i].max_score))
				part.questions[i].max_score = 0;
			total = total + part.questions[i].max_score;
		}
		part.max_score = total;
		t._setHeaderContent();
	};
	
	/**
	 * Create a div with the className button
	 * @param {String} the innerHTML of the button
	 * @returns {HTMLElement} button_verysoft
	 */
	t._createButton = function(content){
		var button = document.createElement("BUTTON");
		if(content == "before"){
			button.className = "flat small_icon";
			button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
			button.title = "Insert a question before";
		} else if(content == "after"){
			button.className = "flat small_icon";
			button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
			button.title = "Insert a question after";
		} else if(content == "remove"){
			button.className = "flat";
			button.innerHTML = "<img src = '"+theme.icons_16.remove+"'/>";
			button.title = "Remove question";
			// button.onmouseover = function(){
				// this.innerHTML = "<img src = '"+theme.icons_16.remove_black+"'/>";
			// };
			// button.onmouseout = function(){
				// this.innerHTML = "<img src = '"+theme.icons_16.remove+"'/>";
			// };
		}
		else
			button.innerHTML = content;
		return button;
	};
	
	/**
	 * Get an array containing the questions index within the part.questions array, ordered according to their index attributes
	 * @returns {Array} ordered
	 */
	t._getOrderedQuestionsIndexInQuestions = function(){
		var ordered = [];
		for(var i = 0; i < part.questions.length; i++){
			// create an array containing the questions index in questions array set in order
			ordered[i] = t._getQuestionIndexInQuestions(i+1); // question_index must start at 1
		}
		return ordered;
	};
	
	/**
	 * Get the question index in the part.questions array, from its index attribute
	 * @param {Number} question_index_attribute, the question index attribute
	 * @returns {Number|Null} null if not found, else the seeked index
	 */
	t._getQuestionIndexInQuestions = function(question_index_attribute){
		var index = null;
		for(var i = 0; i < part.questions.length; i++){
			if(part.questions[i].index == question_index_attribute){
				index = i;
				break;
			}
		}
		return index;
	};
	
	/**
	 * Launch the process after including all the required javascripts files.
	 */
	require(["input_utils.js","exam_objects.js"],function(){
		t._init();
	});
}