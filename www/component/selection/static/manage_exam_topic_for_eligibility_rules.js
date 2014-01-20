function manage_exam_topic_for_eligibility_rules(topic, container, can_add, can_edit, can_remove, other_topics, all_parts){
	var t = this;
	if(typeof(container) == "string")
		container = document.getElementById(container);
	t.table = document.createElement("table");
	t.total_score = 0;
	t.total_parts = 0;
	
	t.reset = function(){
		container.removeChild(t.table);
		delete t.table;
		t.table = document.createElement("table");
		t._resetTotalAttributes();
		t._init();
	}
	
	t._resetTotalAttributes = function(){
		t.total_score = 0;
		t.total_parts = 0;
	}
		
	t._init = function(){
		t._setTableHeader();
		t._setTableBody();
		t._setTableFooter();
		container.appendChild(t.table);
		// alert(service.generate_input(all_parts));
	}
	
	t._setTableHeader = function(){
		var thead = document.createElement("thead");
		var th = document.createElement("th");
		var text = topic.name.uniformFirstLetterCapitalized();
		text += " - "+topic.subjects.length+" "+getGoodSpelling("subject",topic.subjects.length);
		text += " - "+t.total_parts+" "+getGoodSpelling("part",t.total_parts);
		text += " - "+t.total_score+" "+getGoodSpelling("point",t.total_score);
		th.innerHTML = text;
		thead.appendChild(th);
		th.colSpan = 2;
		t.table.appendChild(thead);
	}
	
	t._setTableBody = function(){
		var tbody = document.createElement("tbody");
		//display the selected parts
		if(topic.subjects.length == 0){
			//TODO
		} else {
			for(var i = 0; i < topic.subjects.length; i++){
				var tr_subject_header = document.createElement("tr");
				t._setSubjectHeader(tr_subject_header, i);
				tbody.appendChild(tr_subject_header);
				var ordered_parts = t._getPartsOrdered(topic.subjects[i]);
				for(var j = 0; j < ordered_parts.length; j++){
					var td = document.createElement("td");
					var tr = document.createElement("tr");
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
		t.table.appendChild(tbody);
		
		//display the other parts (only the free ones)
	}
	
	t._createPartRemovable = function(subject_index, part_index, td, td2){
		var div = document.createElement("div");
		new manage_exam_subject_part_questions(topic.subjects[subject_index].parts[part_index], div, false, false, false, false, false,false,0,true);
		td.appendChild(div);
		var remove_button = document.createElement("div");
		remove_button.subject_index = subject_index;
		remove_button.part_index = part_index;
		remove_button.className = "button";
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
	}
	
	t._createPartNotRemovable = function(subject_index, part_index, td){
		var div = document.createElement("div");
		new manage_exam_subject_part_questions(topic.subjects[subject_index].parts[part_index], div, false, false, false, false, false,false,0,true);
		td.appendChild(div);
		td.colSpan = 2;
	}
	
	t._setSubjectHeader = function(tr, index){
		//TODO
		//oncheck: get all the parts from a subject
		// after having checked that they are not in any other topic
		var td = document.createElement("td");
		var th = document.createElement("th");
		th.innerHTML = topic.subjects[index].name.uniformFirstLetterCapitalized();
		td.innerHTML = "Full Subject:";
		var check = document.createElement("input");
		check.type = "checkbox";
		check.value = true;
		check.index = index;
		if(topic.subjects[index].full_subject)
			check.checked = true;
		check.onchange = function(){
			if(this.checked){
				var can_do = t._canSetSubjectAsFullSubject(this.index);
				if(can_do != null){
					error_dialog(can_do);
					this.checked = false;
				} else {
					t._addAllMissingPartsFromASubject();
					t.reset();
				}
			}
		};
		td.appendChild(check);
		tr.appendChild(th);
		tr.appendChild(td);
	}
	
	t._setTableFooter = function(){
		//TODO
	}
	
	
	t._addAllMissingPartsFromASubject = function(){
		//TODO
	}
	
	/** @method _canSetSubjectAsFullSubject
	 * @param {integer} subject_index in topic.subjects array
	 * @return {string} null if no part of this exam appear in any other topic
	 * else error message to display
	 */
	t._canSetSubjectAsFullSubject = function(subject_index){
		var id = topic.subjects[subject_index].id;
		var index_in_all_parts = t._findSubjectIndexInAllParts(id);
		var error = null;
		var first = true;
		for(var i = 0; i < all_parts[index_in_all_parts].parts.length; i++){
			var temp = t._isPartInOtherTopics(all_parts[index_in_all_parts].parts[i].id);
			if(!temp.res){
				if(first)
					error = "<ul> Some parts from this subject already appear in other topics:";
				first = false;
				error += "<li>"+temp.text+"</li>";
			}
		}
		if(!first)
			error += "</ul>";
		return error;
	}
	
	t._removePart = function(subject_index, part_index){
		// delete from topic object
		topic.subjects[subject_index].parts.splice(part_index,1);
		if(topic.subjects[subject_index].parts.length == 0)//remove the subject from topic
			topic.subjects.splice(subject_index,1);
		// reset
		t.reset();
	}
		
	/** @method _getPartsOrdered
	 * Return the parts ordered according to their index (can accept skipping indexes)
	 * @param {object} subject object
	 * @return {array} parts indexes in order
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
	}
	
	t._getPartIndexFromIndexAttribute = function(subject,index_attribute){
		var index = null;
		for(var i = 0; i < subject.parts.length; i++){
			if(subject.parts[i].index == index_attribute){
				index = i;
				break;
			}
		}
		return index;
	}
	
	t._isPartInOtherTopics = function(part_id){
		var res = false;
		var text = null;
		for(var i = 0; i < other_topics.length; i++){
			for(var j = 0; j < other_topics[i].subjects.length; j++){
				for(var k = 0; k < other_topics[i].subjects[j].parts.length; k++){
					if(other_topics[i].subjects[j].parts[k].id == part_id){
						res = true;
						text = "Part "+other_topics[i].subjects[j].parts[k].index+", in "+other_topics[i].name.uniformFirstLetterCapitalized();
						break;
					}
				}
			}
		}
		var r = {};
		r.res = res;
		r.text = text;
		return r;
	}
	
	t._findSubjectIndexInAllParts = function(subject_id){
		var index = null;
		for(var i = 0; i < all_parts.length; i++){
			if(all_parts[i].id == subject_id){
				index = i;
				break;
			}
		}
		return index;
	}
	
	require("manage_exam_subject_part_questions.js",function(){
		t._init();
	});
}