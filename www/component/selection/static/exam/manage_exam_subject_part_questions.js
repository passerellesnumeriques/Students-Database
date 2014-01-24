function manage_exam_subject_part_questions(part, container, can_edit, can_remove, can_add, display_questions_detail, display_correct_answer,display_choices, question_index_before, no_question){	
	var t = this;
	t.table = document.createElement("table");
	t.ordered = null;
	t.onmanagerow = new Custom_Event();
	t.onupdatescore = new Custom_Event();
	t.focusonagiveninput = new Custom_Event();
	//question_index_before is set as an attribute 
	// that way it can be updated from outside via reset method
	t.question_index_before = question_index_before;
	
	t._init = function(){
		t._setTableHeader();
		if(display_questions_detail)
			t._setTableBody();
		container.appendChild(t.table);
	}
	
	t._setTableHeader = function(){
		var thead = document.createElement("thead");
		var tr = document.createElement("tr");
		t.th_head = document.createElement("th");
		t._setHeaderContent();
		if(display_correct_answer && (can_edit || can_remove || can_add)){
			if(!display_choices)
				t.th_head.colSpan = 4;
			else
				t.th_head.colSpan = 3;
		}
		else if(!display_correct_answer && !can_edit && !can_remove && !can_add){
			if(!display_choices)
				t.th_head.colSpan = 2;
			else
				t.th_head.colSpan = 1;
		}
		else{
			if(!display_choices)
				t.th_head.colSpan = 3;
			else
				t.th_head.colSpan = 2;
		}
		t.th_head.style.textAlign = "left";
		tr.appendChild(t.th_head);
		thead.appendChild(tr);
		t.table.appendChild(thead);
	}
	
	t._setHeaderContent = function(){
		var max_score = typeof part.max_score == "number" ? part.max_score : parseFloat(part.max_score);
		max_score = max_score.toFixed(2);
		if(display_questions_detail)
			t.th_head.innerHTML = "PART "+part.index+" - "+part.name+" - "+max_score+" "+getGoodSpelling("point",part.max_score);
		else {
			if(!no_question)
				t.th_head.innerHTML = "PART "+part.index+" - "+part.name+" - "+max_score+" "+getGoodSpelling("point",part.max_score)+" - "+part.questions.length+" "+getGoodSpelling("question", part.questions.length);
			else
				t.th_head.innerHTML = "PART "+part.index+" - "+part.name+" - "+max_score+" "+getGoodSpelling("point",part.max_score);
		}
	}
	
	t._setTableBody = function(){
		var tbody = document.createElement("tbody");		
		t.ordered = t._getOrderedQuestionsIndexInQuestions();
		if(t.ordered.length > 0){
			for(var i = 0; i < t.ordered.length; i++){
				if(i == 0){
					var tr_head = document.createElement("tr");
					th1 = document.createElement("th");
					th1.innerHTML = "Score";
					if(!can_edit)
						th1.style.textAlign = "left";
					tr_head.appendChild(th1);
				}
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				var div = document.createElement("div");
				div.innerHTML = parseInt(t.question_index_before) + i + 1 +" - ";
				if(can_edit){
					var input = document.createElement("input");
					autoresize_input(input, 5);
					//give a unique id to the input, to be able to get it at anytime
					input.id = "question"+part.index+"."+part.questions[t.ordered[i]].index;
					input.value = part.questions[t.ordered[i]].max_score;
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
					div.appendChild(input);
				} else {
					div.innerHTML += part.questions[t.ordered[i]].max_score;
				}
				td1.appendChild(div);
				if(can_edit)
					td1.style.textAlign = "center";
				tr.appendChild(td1);
				if(display_correct_answer)
					t._addOptionalData("correct_answer", i, tr_head, tr);
				if(display_choices)
					t._addOptionalData("choices", i , tr_head,tr);
				if(can_remove || can_add){
					var td3 = document.createElement("td");
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
						td3.appendChild(before);
						td3.appendChild(after);
					}
					tr.appendChild(td3);
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
	}
	
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
			autoresize_input(input, 5);
			input.value = part.questions[t.ordered[i]][attribute];
			input.index_in_ordered = i;
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
	}
	
	t._onFirstInsert = function(){
		//create the question
		t._createQuestion(1);
		//reset table
		t.reset();
	}
	
	t._onRemove = function(index_in_ordered,index_in_questions){
		//update the index attribute of the following questions
		t._decreaseIndexAttribute(index_in_ordered);
		//remove the question
		part.questions.splice(index_in_questions,1);
		//reset table
		t.reset();
	}
	
	t._onInsertBefore = function(index_in_ordered){
		//update the index attribute of the following questions
		if(typeof(index_in_ordered != "number"))
			index_in_ordered = parseInt(index_in_ordered);
		t._increaseIndexAttribute(index_in_ordered -1); //we insert before so the index attribute of the current question shall be increased too
		//create the question object
		t._createQuestion(index_in_ordered +1);
		//reset table
		t.reset();
	}
	
	t._onInsertAfter = function(index_in_ordered){
		if(typeof(index_in_ordered) != "number")
			index_in_ordered = parseInt(index_in_ordered);
		//update the index attribute of the following questions
		t._increaseIndexAttribute(index_in_ordered);
		//create the question object
		t._createQuestion(index_in_ordered +2);
		//reset table
		t.reset();
	}
	
	t._createQuestion = function(index_attribute_value){
		var index = part.questions.length;
		part.questions[index] = new ExamSubjectQuestion(-1,index_attribute_value,null,null,null);
//		part.questions[index].id = -1;
//		part.questions[index].index = index_attribute_value;
//		part.questions[index].max_score = null;
//		part.questions[index].correct_answer = null;
//		part.questions[index].choices = null;
	}
	
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
	}
	
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
	}
	
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
	}
	
	t.getDisplayQuestionDetail = function(){
		return display_questions_detail;
	}
	
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
	}
	
	t._createButton = function(content){
		var button = document.createElement("div");
		button.className = "button";
		if(content == "before")
			button.innerHTML = "<img src = '/static/selection/exam/arrow_up_16.png'/><img src = '"+theme.icons_10.add+"'/>";
		else if(content == "after")
			button.innerHTML = "<img src = '/static/selection/exam/arrow_down_16.png'/><img src = '"+theme.icons_10.add+"'/>";
		else if(content == "remove"){
			button.innerHTML = "<img src = '"+theme.icons_16.remove+"'/>";
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
	}
	
	t._getOrderedQuestionsIndexInQuestions = function(){
		var ordered = [];
		for(var i = 0; i < part.questions.length; i++){
			// create an array containing the questions index in questions array set in order
			ordered[i] = t._getQuestionIndexInQuestions(i+1); // question_index must start at 1
		}
		return ordered;
	}
	
	t._getQuestionIndexInQuestions = function(question_index_attribute){
		var index = null;
		for(var i = 0; i < part.questions.length; i++){
			if(part.questions[i].index == question_index_attribute){
				index = i;
				break;
			}
		}
		return index;
	}
	
	require(["autoresize_input.js","exam_objects.js"],function(){
		t._init();
	});
}