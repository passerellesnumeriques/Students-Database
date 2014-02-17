/**
 * Create the page to manage any exam topic for eligibility rules
 * @param {Object} topic JSON object
 * @param {HTMLElement|String} container 
 * @param {Boolean} can_add 
 * @param {Boolean} can_edit
 * @param {Boolean} can_remove
 * @param {Object} other_topics from SelectionJSON#getJsonAllTopics method
 * @param {Object} all_parts from SelectionJSON#getJsonAllParts method
 * @param {Number|null} db_lock the databaselock id, if ever
 * Otherwize this page handles the databaselock
 * @param {Object} header a page_header object in which the buttons back, save, remove, edit / unedit will be managed 
 * @param {Number} campaign_id the current campaign id because sum_model is required for the databaselock services
 * @param {Boolean} read_only if true, the page is initialized in a read_only mode
 */
function manage_exam_topic_for_eligibility_rules(topic, container, can_add, can_edit, can_remove, other_topics, all_parts, db_lock, header, campaign_id, read_only){
	var t = this;
	if(typeof(container) == "string")
		container = document.getElementById(container);
	t.table = document.createElement("table");
	t.table_edit = document.createElement("table");
	t.total_score = 0;
	t.total_parts = 0;
	t.global_can_edit = true;
	t.global_can_add = true;
	t.global_can_remove = true;
	t.db_lock = db_lock;
	
	if(typeof header == "string")
		t.header = document.getElementById(header);
	else
		t.header = header;
	
	/**
	 * Method called each time the user does anything on the page (input, clicking buttons...)
	 * This way the displayed data is always up to date and matches with the data object content(topic, other_topics, all_parts)
	 */
	t.reset = function(){
		container.removeChild(t.table);
		delete t.table;
		if(t.table_edit.parentNode == container)
			container.removeChild(t.table_edit);
		delete t.table_edit;
		t.table_edit = document.createElement("table");
		t.table = document.createElement("table");
		t._resetTotalAttributes();
		t._resetFreePartsToAdd();
		t.header.resetMenu();
		t._init();
	};
	
	/**
	 * Set the total score and parts to 0
	 * Method called each time a part is added / deleted from the topic, since each time the page content is generated those attributes are increased
	 */
	t._resetTotalAttributes = function(){
		t.total_score = 0;
		t.total_parts = 0;
	};
	
	/**
	 * Launch the page building<br/>
	 * Get the last values of the global_rights and of the db_lock<br/>
	 * The HTMLElements are added to the container by this method 
	 */
	t._init = function(){
		//get the updated values of global rights and db_lock
		t.global_can_edit = t.editable_manager.getCanEdit();
		t.global_can_add = t.editable_manager.getCanAdd();
		t.global_can_remove = t.editable_manager.getCanRemove();
		t.db_lock = t.editable_manager.getDBLock();
		t._setButtons(); // manage the back, save and remove buttons
		t.editable_manager.manageButtons(); // manage the edit and unedit buttons
		t._setTableHeader();
		t._setTableBody();
		if(t.global_can_edit)
			t._setTableEdit();
		t._setTableFooter();
		container.appendChild(t.table);
		if(t.global_can_edit)
			container.appendChild(t.table_edit);
		container.style.display = "inline-block";
		t._updateTheadText();
//		t._manageButtons();
		t._setStyle(t.table);
		t._setStyle(t.table_edit);
		//If the topic is new, focus on the name input
		if(topic.id == -1 && (topic.name == null || topic.name == ""))
			t._focusOnInput("topic_name_input");
		t._setCommonTableSize();
	};
	
	/**
	 * Set the back, save, and remove buttons:
	 * <ul>
	 * <li>The content of the buttons is set</li>
	 * <li>The onclick function are set</li>
	 * <li>The buttons are added to the menu according to the global rights</li>
	 * </ul>
	 */
	t._setButtons = function(){
		var back = document.createElement("div");
		back.className = "button";
		back.innerHTML = "<img src = '"+theme.icons_16.back+"'/> Back";
		back.onclick = function(){
			location.assign('/dynamic/selection/page/exam/main_page');
		};
		t.header.addMenuItem(back);
		
		if(t.global_can_edit && (t.global_can_add || (topic.id != -1 && topic.id != "-1"))){
			var save = document.createElement("div");
			save.className = "button";
			save.innerHTML = "<img src = '"+theme.icons_16.save+"'/> Save";
			save.onclick = t._save;
			t.header.addMenuItem(save);
		}
		
		if(t.global_can_remove){
			var remove = document.createElement("div");
			remove.className = "button";
			remove.innerHTML = "<img src = '"+theme.icons_16.remove+"'/> Remove";
			remove.onclick = t._removeTopic;
			t.header.addMenuItem(remove);
		}
	};
	
	/**
	 * Update the text displayed in the header of the t.table object<br/>
	 * This method is used to be sure that the score / number of parts displayed are ok
	 */
	t._updateTheadText = function(){
		if(t.thead_text_node.parentNode == t.thead_text_container){
			t.thead_text_container.removeChild(t.thead_text_node);
			delete t.thead_text_node;
			t.thead_text = topic.subjects.length+" "+getGoodSpelling("subject",topic.subjects.length);
			t.thead_text += " - "+t.total_parts+" "+getGoodSpelling("part",t.total_parts);
			t.thead_text += " - "+t.total_score+" "+getGoodSpelling("point",t.total_score);
			t.thead_text_node = document.createTextNode(t.thead_text);
			t.thead_text_container.appendChild(t.thead_text_node);
		}
	};
	
	/**
	 * Method to called to set the layout of the t.table header
	 * <ul>
	 * <li>If t.global_can_edit, an input containing the topic name is created<br/> The properties of this input are set (oninput...)</li>
	 * <li>Else a text node is created</li>
	 * </ul>
	 */
	t._setTableHeader = function(){
		var thead = document.createElement("thead");
		var th = document.createElement("th");
		t.thead_text_container = document.createElement("div");
		t.thead_text_container.style.paddingBottom = "20px";
		t.thead_text = "";
		if(t.global_can_edit){
			var input = document.createElement("input");
			input.type = "text";
			input.style.textAlign = "center";
			input.style.fontWeight = "bold";
			input.style.fontSize = "large";
			input.id = "topic_name_input";
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
			new autoresize_input(input,7);
			var tr_input = document.createElement("tr");
			var td_input = document.createElement("td");
			td_input.colSpan = 2;
			td_input.style.textAlign = "center";
			td_input.appendChild(input);
			tr_input.appendChild(td_input);
			thead.appendChild(tr_input);
		} else {
			var name = topic.name.uniformFirstLetterCapitalized();
			if (name == null)
				name = ""; //avoid displaying "null"
			var tr_title = document.createElement("tr");
			var th_title = document.createElement("th");
			th_title.appendChild(document.createTextNode(name));
			th_title.colSpan = 2;
			th_title.style.textAlign = "center";
			th_title.style.fontSize = "x-large";
			tr_title.appendChild(th_title);
			thead.appendChild(tr_title);
			//only set fontSize as large in this case
			t.thead_text_container.style.fontSize = "large";
		}
		t.thead_text_node = document.createTextNode(t.thead_text);
		t.thead_text_container.appendChild(t.thead_text_node);
		th.appendChild(t.thead_text_container);
		var tr_head = document.createElement("tr");
		tr_head.appendChild(th);
		thead.appendChild(tr_head);
		th.colSpan = 2;
		t.table.appendChild(thead);
	};
	
	/**
	 * Set the content of the t.table HTMLElement<br/>
	 * For the Editable mode:<br/>
	 * <ul>
	 * <li>All the parts are grouped by exam subject</li>
	 * <li>Beside each exam subject name is added a td element containing the full_subject attribute, represented by a check box<br/>
	 * If the full_subject = true, all the parts of the subject are displayed on the following row, and they cannot be removed (otherwize not a full_subject topic...)<br/>
	 * Else only the selected parts are displayed. In that case, when the user checks the full_subject check_box there are two cases:
	 * 	<ul>
	 * 		<li>no part of this exam subject is set in any other topic: all the missing parts are added</li>
	 * 		<li>Else, an error is displayed and nothing is done (and check box is unchecked)</li>
	 * 	</ul>
	 * </li>
	 * </ul>
	 */
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
					if(topic.subjects[i].full_subject || !t.global_can_edit)
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
	};
	
	/**
	 * Set the content of the table displayed beside the main table<br/>
	 * This table contains all the subject containing free parts and all the free parts, grouped by subject.<br/>
	 */
	t._setTableEdit = function(){
		var tr_head = document.createElement("tr");
		var th = document.createElement("th");
		th.innerHTML = "Free parts data";
		th.colSpan = 2;
		th.style.paddingBottom = "20px";
		th.style.fontSize = "large";
		tr_head.appendChild(th);
		t.table_edit.appendChild(tr_head);
		var tbody = document.createElement("tbody");
		t.table_edit.appendChild(tbody);
		t._setAllFreeFullSubjectList(tbody);
		t._setAllPartsList(tbody);
	};
	
	/**
	 * Create a row for the given part, that can be removed
	 * @param {Number} subject_index the subject index in topic object
	 * @param {Number} part_index the part index in topic.subjects[subject_index].parts array
	 * @param {HTMLElement} td where the manage_exam_subject_part_questions object shall be inserted
	 * @param {HTMLElement} td2 where the remove button shall be inserted
	 */
	t._createPartRemovable = function(subject_index, part_index, td, td2){
		new manage_exam_subject_part_questions(topic.subjects[subject_index].parts[part_index], td, false, false, false, false, false,false,0,true,null,true);
		var remove_button = document.createElement("div");
		remove_button.subject_index = subject_index;
		remove_button.part_index = part_index;
		remove_button.className = "button_verysoft";
		remove_button.innerHTML = "<img src = '"+theme.icons_16.remove+"'/>";
		remove_button.onclick = function(){
			t._removePart(this.subject_index, this.part_index);
		};
		td2.appendChild(remove_button);
	};
	
	/**
	 * Create a row for the given part, that cannot be removed
	 * @param {Number} subject_index the subject index in topic object
	 * @param {Number} part_index the part index in topic.subjects[subject_index].parts array
	 * @param {HTMLElement} td where the manage_exam_subject_part_questions object shall be inserted
	 */
	t._createPartNotRemovable = function(subject_index, part_index, td){
		new manage_exam_subject_part_questions(topic.subjects[subject_index].parts[part_index], td, false, false, false, false, false,false,0,true,null,true);
		td.colSpan = 2;
	};
	
	/**
	 * Create the row containing the exam subject name in the t.table element
	 * @param {HTMLElement} tr the row to set
	 * @param {Number} index the subject index in the topic object
	 */
	t._setSubjectHeader = function(tr, index){
		var td = document.createElement("td");
		var th = document.createElement("th");
		th.innerHTML = topic.subjects[index].name.uniformFirstLetterCapitalized();
		th.style.textAlign = "left";
		td.innerHTML = "Full Subject:";
		if(t.global_can_edit){
			var check = document.createElement("input");
			check.title = "Check to add all the parts from this subject";
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
						t.reset();
					}
				} else {
					topic.subjects[this.index].full_subject = false;
					t.reset();
				}
			};
			td.appendChild(check);
			td.style.verticalAlign = "bottom";
		} else {
			var text;
			if(topic.subjects[index].full_subject)
				text = " Yes";
			else
				text = " No";
			td.appendChild(document.createTextNode(text));
			td.style.fontStyle = "italic";
		}
		tr.appendChild(th);
		tr.appendChild(td);
	};
	
	/**
	 * Set a list of all the exam subjects that can be set as full subject topic<br/>
	 * in the t.table_edit element<br/>
	 * <ul>
	 * <li>A row is added for each exam subject that is having free parts</li>
	 * <li>Each row is ended by a td containing a check box<br/>
	 * If checked, all the free parts (displayed under, cf _setAllPartsList method) are checked.<br/>
	 * This exam subject is also added to the potentially_full_subject list. Since the user wanted to check all the parts from this subject, he maybe wants to<br/>
	 * set the subject as a full subject topic. But we still have to check that all the parts of this subject are free
	 * </li>
	 * @param {HTMLElement} table where the list shall be inserted
	 */
	t._setAllFreeFullSubjectList = function(table){
		var free_full_subjects_ids = [];
		t.potential_full_subjects = [];
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
		th_head.innerHTML = "Subjects containing free parts";
		th_head.style.fontStyle = "italic";
		th_head.style.textAlign = "center";
		th_head.colSpan = 2;
		th_head.style.textAlign = "left";
		tr_head.appendChild(th_head);
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
				input.type = "checkbox";
				input.title = "Check all the free parts from this subject";
				input.onchange = function(){
					var free_parts = t._getAllFreePartsIdsAndNamesForASubject(this.subject_id);
					//get the free parts
					if(this.checked){
						if(!t.potential_full_subjects.contains(this.subject_id))
							t.potential_full_subjects.push(this.subject_id);
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
	
//	t._updateAddFullSubjectVisibility = function(){
//		if(t.potential_full_subjects.length > 0)
//			t.add_full_subjects.style.visibility = "visible";
//		else
//			t.add_full_subjects.style.visibility = "hidden";
//	};
//	
	/**
	 * Add all the free parts that the user can pick up<br/>
	 * The list as the same structure as the t.table one (parts grouped by subject) <br/>
	 * The row is ended by a checkbox to enable the user to pick this part
	 * @param {HTMLElement} table where the list shall be inserted
	 */
	t._setAllPartsList = function(table){
		var free_parts = t._getAllFreeParts();
		t.free_parts_to_add = {};
		
		// set the header
		var tr_head = document.createElement("tr");
		var th_head = document.createElement("th");
		th_head.style.textAlign = "left";
		th_head.innerHTML = "Free parts";
		th_head.style.paddingTop = "20px";
		th_head.colSpan = 2;
		th_head.style.fontStyle = "italic";
		th_head.style.textAlign = "center";
		tr_head.appendChild(th_head);
//		var td2 = document.createElement("td");
//		
//		td2.appendChild(t.add_free_parts);
//		tr_head.appendChild(td2);
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
				th.style.textAlign = "left";
				th.style.paddingTop = "10px";
				th.colSpan = 2;
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
			// Add the footer (button transfer)
			t.add_free_parts = document.createElement("div");
			t.add_free_parts.className = "button_soft";
			t.add_free_parts.innerHTML = "<img src = '"+theme.icons_16.left+"'/> Add the parts";
			t.add_free_parts.title = "Add the selected parts to the current topic";
			t.add_free_parts.onclick = function(){
				//update potential_full_subjects attribute
				t._cleanPotentialFullSubjects();
				if(t.potential_full_subjects.length > 0){
					var pop = new popup_window("Add the parts",theme.icons_16.question,"Do you want to set the \"Whole subject\" selected as full subject (only the fully free ones)?<br/><i>If yes, when a part is added to the subject, it is automatically added to the topic</i>");
					pop.addButton("<img src='"+theme.icons_16.yes+"' style='vertical-align:bottom'/> Yes", 'yes', function(){
						//add the parts and the full subjects
						t._addFreeParts(true);
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
			t.add_free_parts.style.visibility = "hidden";
			var tr_foot = document.createElement("tr");
			var td_foot = document.createElement("td");
			td_foot.colSpan = 2;
			td_foot.appendChild(t.add_free_parts);
			td_foot.style.paddingTop = "20px";
			tr_foot.appendChild(td_foot);
			table.appendChild(tr_foot);
		}
	};
	
	/**
	 * Only keep the subjects ids that can really be set as full subjects from pential_full_subjects list
	 */
	t._cleanPotentialFullSubjects = function(){
		for(var i = 0; i < t.potential_full_subjects.length; i++){
			if(t._cannotSetSubjectAsFullSubject(t.potential_full_subjects[i]))
				t.potential_full_subjects.remove(t.potential_full_subjects[i]);
		}
	};
	
	/**
	 * Method called when the user wants to add the selected parts to the topic <br/>
	 * All the selected parts are added<br/>
	 * If add_potential_full_subjects, this method will also set the full_subject attribute at true for<br/>
	 * all the potential_full_subject list<br/>
	 * The reset method is called at the end of the process
	 * @param {Boolean} add_potential_full_subjects
	 */
	t._addFreeParts = function(add_potential_full_subjects){
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
			if(add_potential_full_subjects){
				//set the selected subjects as full_subjects
				for(var i = 0; i < t.potential_full_subjects.length; i++){
					if(t.potential_full_subjects[i] == subject_id){
						var index = t._findSubjectIndexInTopic(t.potential_full_subjects[i]);
						topic.subjects[index].full_subject = true;
					}
				}
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
		//reset
		t.reset();
	};
	
	/**
	 * Reset the free_parts_to_add attribute
	 */
	t._resetFreePartsToAdd = function(){
		delete t.free_parts_to_add;
		t.free_parts_to_add = {};
	};
	
	/**
	 * Method called when the checkbox related to a free part is checked
	 * The part id is added to the free_parts_to_add object
	 * @param {String} subject_name the subject_name that contains this part
	 * @param {String} part_id the id of the part to add
	 */
	t._oncheckFreePartsCheckBox = function(subject_name, part_id){
		if(typeof(t.free_parts_to_add[subject_name]) == "undefined")
			t.free_parts_to_add[subject_name] = [];
		if(!t.free_parts_to_add[subject_name].contains(part_id))
			t.free_parts_to_add[subject_name].push(part_id);
	};
	
	/**
	 * Method called when the checkbox related to a free part is unchecked
	 * The part id is removed from the free_parts_to_add object
	 * @param {String} subject_name the subject_name that contains this part
	 * @param {String} part_id the id of the part to remove
	 */
	t._onuncheckFreePartsCheckBox = function(subject_name, part_id){
		if(t.free_parts_to_add[subject_name].contains(part_id)){
			t.free_parts_to_add[subject_name].remove(part_id);
		}
		//if the parts array is empty for this subject, remove
		if(t.free_parts_to_add[subject_name].length == 0)
			delete t.free_parts_to_add[subject_name];
	};
	
	/**
	 * Get the subject id from its name
	 * @param {String} name the subject name
	 * @returns {Number} id
	 */
	t._getSubjectIdFromName = function(name){
		for(var i = 0; i < all_parts.length; i++){
			if(all_parts[i].name.uniformFirstLetterCapitalized() == name.uniformFirstLetterCapitalized())
				return all_parts[i].id;
		}
	};
	
	/**
	 * Get a subject name from its given id
	 * @param {Number} id
	 * @returns {String} name
	 */
	t._getSubjectNameFromId = function(id){
		for(var i = 0; i < all_parts.length; i++){
			if(all_parts[i].id == id)
				return all_parts[i].name;
		}
	};
	
	/**
	 * Method called each time a free parts checkbox is checked into the t.table_edit table
	 * It the add_free_parts object is not empty, the button add_free_parts is visible, else it is hidden
	 */
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
	
	/**
	 * Get all the free parts and ids for the given subject id
	 * @param {Number} subject_id
	 * @returns {Array} free_parts containing objects {id: the part id, subject_name: the name of the realted exam subject} 
	 */
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
	
	/**
	 * Set the footer of the t.table element
	 * A button is added. Clicking on this button will popup a window
	 * that contains the list of all the parts already in the other topics
	 */
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
			div.innerHTML = "Parts in other topics";
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
			td.style.paddingTop = "20px";
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
	 * Check that all the parts linked to the given subject are free (not in any topic)
	 * @param {Integer} subject_id the exam subject id
	 * @returns {Null|String} null if no part of this exam appear in any other topic
	 * else error message to display
	 */
	t._cannotSetSubjectAsFullSubject = function(subject_id){
		var index_in_all_parts = t._findSubjectIndexInAllParts(subject_id);
		var error = null;
		var first = true;
		for(var i = 0; i < all_parts[index_in_all_parts].parts.length; i++){
			var in_other_topic = t._isPartInOtherTopics(all_parts[index_in_all_parts].parts[i].id);
			if(in_other_topic.res){
				if(first)
					error = "Some parts from this subject already appear in other topics: <ul>";
				first = false;
				error += "<li>"+in_other_topic.text+"</li>";
			}
		}
		if(!first)
			error += "</ul><br/> So you cannot set it as a full subject for this topic";
		return error;
	};
	
	/**
	 * Remove a part from the topic object<br/>
	 * This method calls the table reseter at the end of the process
	 * @param {Number} subject_index the exam subject index in the topic.subjects array
	 * @param {Number} part_index the index of the part in the topic.subjects[subject_index].parts array
	 */
	t._removePart = function(subject_index, part_index){
		// delete from topic object
		topic.subjects[subject_index].parts.splice(part_index,1);
		if(topic.subjects[subject_index].parts.length == 0)//remove the subject from topic
			topic.subjects.splice(subject_index,1);
		// reset
		t.reset();
	};
		
	/**
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
	
	/**
	 * Get the part index in a subject object from its index attribute
	 * @param {Object} a JSON exam subject object
	 * @param {Number} index_attribute
	 * @returns {Null|Number} null if the index was not found, else the required index
	 */
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
	
	/**
	 * Check that the given part appears in any other topic
	 * @param {Number} part_id
	 * @return {Object} r with two attributes:<ul><li><code>res</code> {Boolean} true if the part is in any other topic</li><li><code>text</code> {String} the list of its occurences</li></ul>
	 */
	t._isPartInOtherTopics = function(part_id){
		var res = false;
		var text = null;
		for(var i = 0; i < other_topics.length; i++){
			for(var j = 0; j < other_topics[i].subjects.length; j++){
				for(var k = 0; k < other_topics[i].subjects[j].parts.length; k++){
					if(other_topics[i].subjects[j].parts[k].id == part_id){
						res = true;
						if(other_topics[i].subjects[j].parts[k].name != null && other_topics[i].subjects[j].parts[k].index != "")
							text = "Part "+other_topics[i].subjects[j].parts[k].index+" - "+other_topics[i].subjects[j].parts[k].name+" - from "+other_topics[i].subjects[j].name+" subject, in "+other_topics[i].name+ " topic";
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
	
	/**
	 * Check that the given part is already in the current topic
	 * @param {Number} part_id
	 * @returns {Boolean}
	 */
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
	
	/**
	 * Check that the given subject is already in the current topic
	 * @param {Number} subject_id
	 * @returns {Boolean}
	 */
	t._isSubjectInCurrentTopic = function(subject_id){
		for(var i = 0; i < topic.subjects.length; i++){
			if(topic.subjects[i].id == subject_id)
				return true;
		}
		return false;
	};
	
	/**
	 * Get a subject index in the all_parts array
	 * @param {Number} subject_id
	 * @returns {Null|Number} null if the subject was not found, else the index
	 */
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
	
	/**
	 * Get a subject index in the topic.subjects array
	 * @param {Number} subject_id
	 * @returns {Null|Number} null if the subject was not found, else the index
	 */
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
	
	/**
	 * Method called when the save button is pressed<br/>
	 * First this method checks the topic name. If the result is ok, save the topic data and update all the related data
	 */
	t._save = function(){
		//check the name
		var error_name = t._isNameOK();
		if(error_name != null){
			error_dialog(error_name);
			var input = document.getElementById("topic_name_input");
			if(input){
				input.focus();
				input.select();
			}
		} else {
			var locker = lock_screen();
			//The lock id given is the one related to the ExamTopicForEligibilityRule table
			service.json("selection","eligibility_rules/save_topic",{topic:topic, db_lock:db_lock[0]},function(res){
				if(!res){
					unlock_screen(locker);
					error_dialog("An error occured, your informations were not saved");
				} else {
					topic = res;
					//update other_topics
					service.json("selection","eligibility_rules/get_json_all_topics",{exclude_id:topic.id},function(r){
						if(!r){
							error_dialog("An error occured and the page must be reloaded");
							setTimeout(function(){
								location.reload();
							},5000);
						} else {
							window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "Your informations have been successfuly saved!", [{action:"close"}], 5000));
							other_topics = r;
							// No need to update the all_parts object
							t.reset();
							unlock_screen(locker);
						}
					});
				}
			});
		}
	};
	
	/**
	 * Check that the topic name has been set and is unique
	 * @returns {Null|String} null if ok, else the error message to display
	 */
	t._isNameOK = function(){
		if(topic.name == "New Topic" || topic.name == "" || topic.name == null)
			return 'You must set a topic name';
		else {
			for(var i = 0; i < other_topics.length; i++){
				if(other_topics[i].name.uniformFirstLetterCapitalized() == topic.name.uniformFirstLetterCapitalized())
					return "An exam topic for eligibility rules is already named "+topic.name.uniformFirstLetterCapitalized();
			}
			return;
		}
	};
	
	/**
	 * Popup a confirm dialog when remove topic button is pressed<br/>
	 * If confirmed, the remove is performed and the user is redirected
	 */
	t._removeTopic = function(){
		new confirm_dialog("Do you really want to remove this topic for eligibility rules?",function(res){
			if(res){
				service.json("selection","eligibility_rules/remove_topic",{id:topic.id},function(r){
					if(!r){
						error_dialog("An error occured, the data were not removed");
					} else {
						location.assign("/dynamic/selection/page/exam/main_page");
						window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "The topic has been succesfully removed", [{action:"close"}], 5000));
					}
				});
			}
		});
	};
	
	/**
	 * Set the style of the given table
	 * @param {HTMLElement} table
	 */
	t._setStyle = function(table){
		table.style.backgroundColor = "#FFFFFF";
		table.style.marginLeft = "10px";
		table.style.marginTop = "10px";
		table.style.marginBottom = "5px";
		table.style.paddingLeft = "20px";
		table.style.paddingRight = "20px";
		table.style.paddingTop = "20px";
		table.style.paddingBottom = "20px";
		setBorderRadius(table, 5, 5, 5, 5, 5, 5, 5, 5);
		table.style.border = "1px solid";
		table.style.display = "inline-block";
	};
	
	/**
	 * Focus and select the text (if not empty) on the given input
	 * @param {String} id the id of the input
	 */
	t._focusOnInput = function(id){
		var input = document.getElementById(id);
		if(input != null){
			input.focus();
			input.select();
		}
	};
	
	/**
	 * Set a common height to the t.table and t.table_input tables
	 */
	t._setCommonTableSize = function(){
		var h1 = getHeight(t.table);
		var h2 = getHeight(t.table_edit);
		var h = null;
		if(h1 >= h2)
			h = h1;
		else
			h = h2;
		setHeight(t.table, h);
		setHeight(t.table_edit, h);
	};
	
	require(["manage_exam_subject_part_questions.js","autoresize_input.js","popup_window.js","exam_objects.js","editable_read_only_manager.js"],function(){
		t.editable_manager = new editable_read_only_manager(
				can_edit,
				can_add,
				can_remove,
				t.global_can_edit,
				t.global_can_add,
				t.global_can_remove,
				["table","table"],
				["ExamTopicForEligibilityRule","ExamSubjectPart"],
				[null,null],
				[null,null],
				[campaign_id,campaign_id],
				t.db_lock,
				function(){return true;},
				t.reset,
				function(){service.json("selection","eligibility_rules/get_topic",{id:topic.id},function(r){if(r) topic = r},true);}, //must wait for the service before reseting
				function(){if(topic.id == -1) error_dialog("You cannot go to uneditable mode because the topic has never been saved yet"); else return true;},
				t.header
				
		);
		t.editable_manager.setGlobalRights(read_only);
		t._init();
//		t.save_button.onclick = t._save;
//		t.remove_button.onclick = t._removeTopic;
	});
}