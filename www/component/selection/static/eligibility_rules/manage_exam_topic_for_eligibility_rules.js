function manage_exam_topic_for_eligibility_rules(topic, container, can_add, can_edit, can_remove, other_topics, all_parts, db_lock){
	var t = this;
	if(typeof(container) == "string")
		container = document.getElementById(container);
	t.table = document.createElement("table");
	t.total_score = 0;
	t.total_parts = 0;
	
	//TODO global rights? (edit/read_only)
	
	t.reset = function(){
		container.removeChild(t.table);
		delete t.table;
		t.table = document.createElement("table");
		t._resetTotalAttributes();
		t._init();
	};
	
	t._resetTotalAttributes = function(){
		t.total_score = 0;
		t.total_parts = 0;
	};
		
	t._init = function(){
		t._setTableHeader();
		t._setTableBody();
		t._setTableFooter();
		container.appendChild(t.table);
		t._updateTheadText();
	};
	
	t._updateTheadText = function(){
		if(t.thead_text_node.parentNode == t.thead_text_container){
			t.thead_text_container.removeChild(t.thead_text_node);
			delete t.thead_text_node;
			t.thead_text = " - "+topic.subjects.length+" "+getGoodSpelling("subject",topic.subjects.length);
			t.thead_text += " - "+t.total_parts+" "+getGoodSpelling("part",t.total_parts);
			t.thead_text += " - "+t.total_score+" "+getGoodSpelling("point",t.total_score);
			t.thead_text_node = document.createTextNode(t.thead_text);
			t.thead_text_container.appendChild(t.thead_text_node);
		}
	};
	
	t._setTableHeader = function(){
		var thead = document.createElement("thead");
		var th = document.createElement("th");
		t.thead_text_container = document.createElement("div");
		t.thead_text = "";
//		t._updateTheadText();
		if(can_edit){
			var input = document.createElement("input");
			input.type = "text";
			input.style.textAlign = "right";
			input.style.fontWeight = "bold";
			new autoresize_input(input,7);
			if(topic.name && topic.name.checkVisible())
				input.value = topic.name.uniformFirstLetterCapitalized();
			else {
				input.value = "New Topic";
				input.style.color = "#808080";
				input.style.fontStyle = "italic";
			}
			input.onfocus = function(){
				if(this.value == "New Topic"){
					this.value = null;
					this.style.color = "";
					this.style.fontStyle = "";
				}
			};
			input.onblur = function(){
				if(this.value && this.value.checkVisible()){
					topic.name = this.value.uniformFirstLetterCapitalized();
					this.value = this.value.uniformFirstLetterCapitalized();
				} else {
					topic.name = "";
					this.value = "New Topic";
					this.style.color = "#808080";
					this.style.fontStyle = "italic";
				}
			};
			t.thead_text_container.appendChild(input);
		} else {
			var name = topic.name.uniformFirstLetterCapitalized();
			if (name == null)
				name = ""; //avoid displaying "null"
			t.thead_text_container.appendChild(document.createTextNode(name));
		}
		t.thead_text_node = document.createTextNode(t.thead_text);
		t.thead_text_container.appendChild(t.thead_text_node);
		th.appendChild(t.thead_text_container);
		thead.appendChild(th);
		th.colSpan = 2;
		t.table.appendChild(thead);
	};
	
	t._setTableBody = function(){
		var tbody = document.createElement("tbody");
		//display the selected parts
		if(topic.subjects.length == 0){
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			td.innerHTML = "This topic contains no part yet";
			td.style.color = "#808080";
			td.style.fontStyle = "italic";
			tr.appendChild(td);
			tbody.appendChild(tr);
		} else {
			for(var i = 0; i < topic.subjects.length; i++){
				var tr_subject_header = document.createElement("tr");
				t._setSubjectHeader(tr_subject_header, i);
				tbody.appendChild(tr_subject_header);
				var ordered_parts = t._getPartsOrdered(topic.subjects[i]);
				for(var j = 0; j < ordered_parts.length; j++){
					var td = document.createElement("td");
					var tr = document.createElement("tr");
					tr.style.height = "30px";
					tr.appendChild(td);
					if(topic.subjects[i].full_subject)
						t._createPartNotRemovable(i, ordered_parts[j], td);
					else {
						td2 = document.createElement("td");
						t._createPartRemovable(i, ordered_parts[j], td, td2);
						tr.appendChild(td2);
					}
					t.total_score = t.total_score + parseFloat(topic.subjects[i].parts[ordered_parts[j]].max_score);
					t.total_parts++;
					tbody.appendChild(tr);
				}
			}
		}
		t._setAllFreeFullSubjectList(tbody);
		t._setAllPartsList(tbody);
		t.table.appendChild(tbody);
	};
	
	t._createPartRemovable = function(subject_index, part_index, td, td2){
		new manage_exam_subject_part_questions(topic.subjects[subject_index].parts[part_index], td, false, false, false, false, false,false,0,true,null,true);
		var remove_button = document.createElement("div");
		remove_button.subject_index = subject_index;
		remove_button.part_index = part_index;
		remove_button.className = "button_verysoft";
		remove_button.innerHTML = "<img src = '"+theme.icons_16.remove+"'/>";
		// remove_button.onmouseover = function(){
			// this.innerHTML = "<img src = '"+theme.icons_16.remove_black+"'/>";
		// };
		// remove_button.onmouseout = function(){
			// this.innerHTML = "<img src = '"+theme.icons_16.remove+"'/>";
		// };
		remove_button.onclick = function(){
			t._removePart(this.subject_index, this.part_index);
		};
		td2.appendChild(remove_button);
	};
	
	t._createPartNotRemovable = function(subject_index, part_index, td){
		new manage_exam_subject_part_questions(topic.subjects[subject_index].parts[part_index], td, false, false, false, false, false,false,0,true,null,true);
		td.colSpan = 2;
	};
	
	t._setSubjectHeader = function(tr, index){
		//TODO
		//oncheck: get all the parts from a subject
		// after having checked that they are not in any other topic
		var td = document.createElement("td");
		var th = document.createElement("th");
		th.innerHTML = topic.subjects[index].name.uniformFirstLetterCapitalized();
		th.style.textAlign = "left";
		td.innerHTML = "Full Subject:";
		var check = document.createElement("input");
		check.type = "checkbox";
		check.value = true;
		check.index = index;
		check.subject_id = topic.subjects[index].id;
		if(topic.subjects[index].full_subject)
			check.checked = true;
		check.onchange = function(){
			if(this.checked){
				var can_do = t._cannotSetSubjectAsFullSubject(this.subject_id);
				if(can_do != null){
					error_dialog(can_do);
					this.checked = false;
				} else {
					topic.subjects[this.index].full_subject = true;
					t._addAllMissingPartsFromASubject(topic.subjects[this.index].id);
					//TODO delete added parts from t.parts_to_add
					t.reset();
				}
			} else {
				topic.subjects[this.index].full_subject = false;
				t.reset();
			}
		};
		td.appendChild(check);
		td.style.verticalAlign = "bottom";
		tr.appendChild(th);
		tr.appendChild(td);
	};
	
	/**
	 * Set a list of all the exam subjects that can be set as full subject topic
	 */
	t._setAllFreeFullSubjectList = function(table){
		var free_full_subjects_ids = [];
		t.potential_full_subjects = [];
		//get all the subjects that can be set as full subjects and that are not already in the current topic
//		for(var i = 0; i < all_parts.length; i++){
//			if(!t._cannotSetSubjectAsFullSubject(all_parts[i].id) && !t._isSubjectInCurrentTopic(all_parts[i].id)){
//				var temp = {};
//				temp.id = all_parts[i].id;
//				temp.name = all_parts[i].name;
//				free_full_subjects_ids.push(temp);
//			}
//		}
		for(var i = 0; i < all_parts.length; i++){
			var parts = t._getAllFreePartsIdsAndNamesForASubject(all_parts[i].id);
			if(parts.length > 0){
				var temp = {};
				temp.id = all_parts[i].id;
				temp.name = all_parts[i].name;
				free_full_subjects_ids.push(temp);
			}
		}
		var tr_head = document.createElement("tr");
		var th_head = document.createElement("th");
		th_head.innerHTML = "Whole subject";
//		th_head.style.fontSize = "large";
		tr_head.appendChild(th_head);
//		var td2 = document.createElement("td");
//		t.add_full_subjects = document.createElement("div");
//		t.add_full_subjects.className = "button_verysoft";
//		t.add_full_subjects.onclick = function(){
//			//add the subjects to the topic
//			for(var i = 0; i < t.potential_full_subjects.length; i++){
//				var l = topic.subjects.length;
//				var index = t._findSubjectIndexInAllParts(t.potential_full_subjects[i]);
//				var name = all_parts[index].name;
//				topic.subjects[l] = new ExamSubject(t.potential_full_subjects[i], name, 0, []);
//				topic.subjects[l].full_subject = true;
//				//add the parts
//				t._addAllMissingPartsFromASubject(t.potential_full_subjects[i]);
//			}
//			//reset t.potential_full_subjects
//			delete t.potential_full_subjects;
//			t.potential_full_subjects = [];
//			//reset table
//			t.reset();
//		};
//		t.add_full_subjects.innerHTML = "<img src = '/static/selection/exam/arrow_up_16.png'/>";
//		t.add_full_subjects.title = "Set the selected subjects as full subject for this topic";
//		t.add_full_subjects.style.visibility = "hidden";
//		td2.appendChild(t.add_full_subjects);
//		tr_head.appendChild(td2);
		table.appendChild(tr_head);
		if(free_full_subjects_ids.length == 0){
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			td.innerHTML = "There is no free subject";
			td.style.fontStyle = "italic";
			td.style.color = "#808080";
			tr.appendChild(td);
			table.appendChild(tr);
		} else {
			for(var i = 0; i < free_full_subjects_ids.length; i++){
				var tr = document.createElement("tr");
				var td = document.createElement("td");
				td.innerHTML = free_full_subjects_ids[i].name;
				var td2 = document.createElement("td");
				var input = document.createElement("input");
				input.subject_id = free_full_subjects_ids[i].id;
				//TODO? if already in potential_full_subjects, pre check the check box (after reset..)
				input.type = "checkbox";
				input.onchange = function(){
					var free_parts = t._getAllFreePartsIdsAndNamesForASubject(this.subject_id);
					//get the free parts
					if(this.checked){
						if(!t.potential_full_subjects.contains(this.subject_id))
							t.potential_full_subjects.push(this.subject_id);
						//TODO if full subject to add, check the full subject checkbox and update object
//						t._updateAddFullSubjectVisibility();
						
						//Check all the matching checkboxes
						for(var k = 0; k < free_parts.length; k++){
							var c = document.getElementById("checkbox_"+free_parts[k].subject_name.toLowerCase()+"."+free_parts[k].id);
							if(c){
								c.checked = true;
								//fire the oncheck
								t._oncheckFreePartsCheckBox(free_parts[k].subject_name.uniformFirstLetterCapitalized(), free_parts[k].id);
							}
						}
					} else {
						t.potential_full_subjects.remove(this.subject_id);
//						t._updateAddFullSubjectVisibility();
						//Uncheck all the matching checkboxes (only from the free parts)
						for(var k = 0; k < free_parts.length; k++){
							var c = document.getElementById("checkbox_"+free_parts[k].subject_name.toLowerCase()+"."+free_parts[k].id);
							if(c){
								c.checked = false;
								//fire the onuncheck
								t._onuncheckFreePartsCheckBox(free_parts[k].subject_name.uniformFirstLetterCapitalized(), free_parts[k].id);
							}
						}
					}
					t._updateAddPartsVisibility();
				};
				td2.appendChild(input);
				tr.appendChild(td);
				tr.appendChild(td2);
				table.appendChild(tr);
			}
		}
	};
	
	t._updateAddFullSubjectVisibility = function(){
		if(t.potential_full_subjects.length > 0)
			t.add_full_subjects.style.visibility = "visible";
		else
			t.add_full_subjects.style.visibility = "hidden";
	};
	
	/**
	 * Add all the free parts that the user can pick up
	 */
	t._setAllPartsList = function(table){
		var free_parts = t._getAllFreeParts();
		t.free_parts_to_add = {};
		
		// set the header
		var tr_head = document.createElement("tr");
		var th_head = document.createElement("th");
		th_head.style.textAlign = "left";
		th_head.innerHTML = "Free parts";
//		th_head.style.fontSize = "large";
		tr_head.appendChild(th_head);
		var td2 = document.createElement("td");
		t.add_free_parts = document.createElement("div");
		t.add_free_parts.className = "button_verysoft";
		t.add_free_parts.innerHTML = "<img src = '/static/selection/exam/arrow_up_16.png'/>";
		t.add_free_parts.title = "Add the selected parts to the current topic";
		t.add_free_parts.onclick = function(){
			if(t.potential_full_subjects.length > 0){
				var pop = new popup_window("Add the parts",theme.icons_16.question,"Do you want to set the \"Whole subject\" selected as full subject?<br/><i>If yes, when a part is added to the subject, it is automatically added to the topic</i>");
				pop.addButton("<img src='"+theme.icons_16.yes+"' style='vertical-align:bottom'/> Yes", 'yes', function(){
					//set the selected subjects as full_subjects
						for(var i = 0; i < t.potential_full_subjects.length; i++){
							var index = t._findSubjectIndexInTopic(t.potential_full_subjects[i]);
							topic.subjects[index].full_subject = true;
						}
					//add the parts
					t._addFreeParts();
					pop.close();
				});
				pop.addButton("<img src='"+theme.icons_16.no+"' style='vertical-align:bottom'/> No", 'no', function() {
					//add the parts
					t._addFreeParts();
					pop.close();
				});
				pop.show();
			} else {
				//only add the parts
				t._addFreeParts();
			}
		};
		td2.appendChild(t.add_free_parts);
		t.add_free_parts.style.visibility = "hidden";
		tr_head.appendChild(td2);
		table.appendChild(tr_head);
		if(getObjectSize(free_parts) == 0){
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			var text = document.createElement("div");
			text.style.fontStyle = "italic";
			text.style.color = "#808080";
			text.innerHTML = "There is no free part remaining";
			td.appendChild(text);
			tr.appendChild(td);
			table.appendChild(tr);
		} else {
			//Set the content
			for(s in free_parts){
				// add the subject name as a section title
				var tr_title = document.createElement("tr");
				var th = document.createElement("th");
				th.innerHTML = s.uniformFirstLetterCapitalized();
				tr_title.appendChild(th);
				table.appendChild(tr_title);
				
				// add the free parts
				for(var i = 0; i < free_parts[s].length; i++){
					var tr = document.createElement("tr");
					var td = document.createElement("td");
					var td2 = document.createElement("td");
					new manage_exam_subject_part_questions(free_parts[s][i], td, false, false, false, false, false,false,0,true,null,true);
					var check = document.createElement("input");
					check.type = "checkbox";
					//TODO if already in free_parts to add (after reset)
					check.subject_name = s; //no need to work with the subject id because subject name is unique (cf datamodel)
					check.part_id = free_parts[s][i].id;
					check.id = "checkbox_"+s.toLowerCase()+"."+free_parts[s][i].id;
					check.onchange = function(){
						if(this.checked){
							t._oncheckFreePartsCheckBox(this.subject_name, this.part_id);
						} else {
							t._onuncheckFreePartsCheckBox(this.subject_name, this.part_id);
						}
						t._updateAddPartsVisibility();
					};
					td2.appendChild(check);
					tr.appendChild(td);
					tr.appendChild(td2);
					table.appendChild(tr);
				}
			}
		}
	};
	
	t._addFreeParts = function(){
		for(s in t.free_parts_to_add){
			var subject_id = t._getSubjectIdFromName(s);
//			var subject_index = t._findSubjectIndexInTopic(subject_id);
			var index = t._findSubjectIndexInTopic(subject_id);
			if(index == null){
				//insert the subject
				var subject = new ExamSubject(subject_id, s, 0, []);
				subject.full_subject = false;
				index = topic.subjects.length;
				topic.subjects.push(subject);
			}
			var index_in_all_parts = t._findSubjectIndexInAllParts(subject_id);
			//insert the parts
			for(var i = 0; i < t.free_parts_to_add[s].length; i++){
				var part_index_in_all_parts = null;
				for(var j = 0; j < all_parts[index_in_all_parts].parts.length; j++){
					if(all_parts[index_in_all_parts].parts[j].id == t.free_parts_to_add[s][i]){
						part_index_in_all_parts = j;
						break;
					}
				}
				topic.subjects[index].parts.push(all_parts[index_in_all_parts].parts[part_index_in_all_parts]);
			}
		}
		//reset free_parts_to_add
		delete t.free_parts_to_add;
		t.free_parts_to_add = {};
		//reset
		t.reset();
	};
	
	t._oncheckFreePartsCheckBox = function(subject_name, part_id){
		if(typeof(t.free_parts_to_add[subject_name]) == "undefined")
			t.free_parts_to_add[subject_name] = [];
		if(!t.free_parts_to_add[subject_name].contains(part_id))
			t.free_parts_to_add[subject_name].push(part_id);
	};
	
	t._onuncheckFreePartsCheckBox = function(subject_name, part_id){
		if(t.free_parts_to_add[subject_name].contains(part_id)){
			t.free_parts_to_add[subject_name].remove(part_id);
		}
		//if the parts array is empty for this subject, remove
		if(t.free_parts_to_add[subject_name].length == 0)
			delete t.free_parts_to_add[subject_name];
	};
	
	t._getSubjectIdFromName = function(name){
		for(var i = 0; i < all_parts.length; i++){
			if(all_parts[i].name.uniformFirstLetterCapitalized() == name.uniformFirstLetterCapitalized())
				return all_parts[i].id;
		}
	};
	
	t._getSubjectNameFromId = function(id){
		for(var i = 0; i < all_parts.length; i++){
			if(all_parts[i].id == id)
				return all_parts[i].name;
		}
	};
	
	t._updateAddPartsVisibility = function(){
		if(getObjectSize(t.free_parts_to_add) == 0)
			t.add_free_parts.style.visibility = "hidden";
		else
			t.add_free_parts.style.visibility = "visible";
	};
	
	/**
	 * Get from all_parts the free ones
	 * @returns {Object} free_parts = {subject_name: [free_parts_ids],...}
	 */
	t._getAllFreeParts = function(){
		var free_parts = {};
		for(var i = 0; i < all_parts.length; i++){
			for(var j = 0; j < all_parts[i].parts.length; j++){
				if(!t._isPartInOtherTopics(all_parts[i].parts[j].id).res && !t._isPartInCurrentTopic(all_parts[i].parts[j].id)){
					if(typeof free_parts[all_parts[i].name] == "undefined")
						free_parts[all_parts[i].name] = [];
					free_parts[all_parts[i].name].push(all_parts[i].parts[j]);
				}
			}
		}
		return free_parts;
	};
	
	t._getAllFreePartsIdsAndNamesForASubject = function(subject_id){
		var index = t._findSubjectIndexInAllParts(subject_id);
		var free_parts = [];
		for(var k = 0; k < all_parts[index].parts.length; k++){
			if(!t._isPartInOtherTopics(all_parts[index].parts[k].id).res && !t._isPartInCurrentTopic(all_parts[index].parts[k].id)){
				var part = {};
				part.id = all_parts[index].parts[k].id;
				//the subject name will be used to get the checkbox elements by id
				part.subject_name = t._getSubjectNameFromId(subject_id).toLowerCase();
				free_parts.push(part);
			}
		}
		return free_parts;
	};
	
	t._setTableFooter = function(){
		var text = "";
		var first = true;
		for(var i = 0; i < all_parts.length; i++){
			for(var j = 0; j < all_parts[i].parts.length; j++){
				var in_other_topic = t._isPartInOtherTopics(all_parts[i].parts[j].id);
				if(in_other_topic.res){
					if(first) text += "<ul>";
					first = false;
					text += "<li>"+in_other_topic.text+"</li>";
				}
			}
		}
		if(text != ""){
			text += "</ul>";
			var div = document.createElement("div");
			div.innerHTML = "Not free parts list";
			div.className = "button_soft";
			div.text = text;
			div.onclick = function(){
				var cont = document.createElement("div");
				cont.innerHTML = this.text;
				var pop = new popup_window("Parts already in other topics","",cont);
				pop.show();
			};
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			td.appendChild(div);
			tr.appendChild(td);
			t.table.appendChild(tr);
		} else {
			var text_no_part = document.createElement("div");
			text_no_part.innerHTML = "There is no part in any other topic";
			text_no_part.style.fontStyle = "italic";
			text_no_part.style.color = "#808080";
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			td.appendChild(text_no_part);
			tr.appendChild(td);
			t.table.appendChild(tr);
		}
	};
	
	/**
	 * Method to call when a subject becomes a full_subject for this topic
	 * This method checks that the parts are free before adding to topic
	 * @param {Number} subject_id
	 */
	t._addAllMissingPartsFromASubject = function(subject_id){
		var index = t._findSubjectIndexInTopic(subject_id);
		var index_in_all_parts = t._findSubjectIndexInAllParts(subject_id);
		if(index == null){
			//Add the subject
			var subject = new ExamSubject(subject_id,all_parts[index_in_all_parts].name,0,[]);
			subject.full_subject = true;
			index = topic.subjects.length;
			topic.subjects.push(subject);
		}
		//add the parts
		for(var i = 0; i < all_parts[index_in_all_parts].parts.length; i++){
			if(!t._isPartInCurrentTopic(all_parts[index_in_all_parts].parts[i].id) && !t._isPartInOtherTopics(all_parts[index_in_all_parts].parts[i].id).res)
				topic.subjects[index].parts.push(all_parts[index_in_all_parts].parts[i]);
		}
	};
	
	/**
	 * @param {Integer} subject_id the exam subject id
	 * @returns {String} null if no part of this exam appear in any other topic
	 * else error message to display
	 */
	t._cannotSetSubjectAsFullSubject = function(subject_id){
//		var id = topic.subjects[subject_index].id;
		var index_in_all_parts = t._findSubjectIndexInAllParts(subject_id);
		var error = null;
		var first = true;
		for(var i = 0; i < all_parts[index_in_all_parts].parts.length; i++){
			var in_other_topic = t._isPartInOtherTopics(all_parts[index_in_all_parts].parts[i].id);
			if(in_other_topic.res){
				if(first)
					error = "<ul> Some parts from this subject already appear in other topics:";
				first = false;
				error += "<li>"+in_other_topic.text+"</li>";
			}
		}
		if(!first)
			error += "</ul>";
		return error;
	};
	
	t._removePart = function(subject_index, part_index){
		// delete from topic object
		topic.subjects[subject_index].parts.splice(part_index,1);
		if(topic.subjects[subject_index].parts.length == 0)//remove the subject from topic
			topic.subjects.splice(subject_index,1);
		// reset
		t.reset();
	};
		
	/** @method _getPartsOrdered
	 * Return the parts ordered according to their index (can accept skipping indexes)
	 * @param {Object} subject object
	 * @returns {Array} parts indexes in order
	 */
	t._getPartsOrdered = function(subject){
		var ordered = [];
		var ordered_indexes = [];
		// get all the indexes attributes
		for(var i = 0; i < subject.parts.length; i++)
			ordered.push(subject.parts[i].index);
		if(ordered.length == 1)//nothing to do
			return [0];
		else {
			var i = 0;
			while(i != ordered.length -1){
				if(parseInt(ordered[i]) < parseInt(ordered[i+1]))
					i++;
				else {
					var temp = ordered[i+1];
					ordered[i+1] = ordered[i];
					ordered[i] = temp;
					i++;
				}
			}
		}
		//now get the matching parts indexes in subject object
		for(var j = 0; j < ordered.length; j++)
			ordered_indexes.push(t._getPartIndexFromIndexAttribute(subject,ordered[j]));
		return ordered_indexes;
	};
	
	t._getPartIndexFromIndexAttribute = function(subject,index_attribute){
		var index = null;
		for(var i = 0; i < subject.parts.length; i++){
			if(subject.parts[i].index == index_attribute){
				index = i;
				break;
			}
		}
		return index;
	};
	
	t._isPartInOtherTopics = function(part_id){
		var res = false;
		var text = null;
		for(var i = 0; i < other_topics.length; i++){
			for(var j = 0; j < other_topics[i].subjects.length; j++){
				for(var k = 0; k < other_topics[i].subjects[j].parts.length; k++){
					if(other_topics[i].subjects[j].parts[k].id == part_id){
						res = true;
						if(other_topics[i].subjects[j].parts[k].name != null && other_topics[i].subjects[j].parts[k].index != "")
							text = "Part "+other_topics[i].subjects[j].parts[k].index+" ("+other_topics[i].subjects[j].parts[k].name+") from "+other_topics[i].subjects[j].name+" subject, in "+other_topics[i].name+ " topic";
						else
							text = "Part "+other_topics[i].subjects[j].parts[k].index+" from "+other_topics[i].subjects[j].name+" subject, in "+other_topics[i].name+ " topic";
						break;
					}
				}
			}
		}
		var r = {};
		r.res = res;
		r.text = text;
		return r;
	};
	
	t._isPartInCurrentTopic = function(part_id){
		for(var i = 0; i < topic.subjects.length; i++){
			for(var j = 0; j < topic.subjects[i].parts.length; j++){
				if(topic.subjects[i].parts[j].id == part_id){
					return true;
				}
			}
		}
		return false;
	};
	
	t._isSubjectInCurrentTopic = function(subject_id){
		for(var i = 0; i < topic.subjects.length; i++){
			if(topic.subjects[i].id == subject_id)
				return true;
		}
		return false;
	};
	
	t._findSubjectIndexInAllParts = function(subject_id){
		var index = null;
		for(var i = 0; i < all_parts.length; i++){
			if(all_parts[i].id == subject_id){
				index = i;
				break;
			}
		}
		return index;
	};
	
	t._findSubjectIndexInTopic = function(subject_id){
		var index = null;
		for(var i = 0; i < topic.subjects.length; i++){
			if(topic.subjects[i].id == subject_id){
				index = i;
				break;
			}
		}
		return index;
	};
	
	require(["manage_exam_subject_part_questions.js","autoresize_input.js","popup_window.js","exam_objects.js"],function(){
		t._init();
	});
}