/**
 * Create the section related to the exam subjects on the exam main page
 * @param {HTMLElement | String}container
 * @param {Boolean} can_see
 * @param {Boolean} can_manage
 * @param {Array} all_exams
 */
function exam_subject_main_page(container, can_see, can_manage, all_exams){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	t.table = document.createElement('table');
	t.can_see = can_see;
	t.can_manage = can_manage;
	t.all_exams = all_exams;
	
	/**
	 * Start the process
	 * Create a table based on the user rights, showing the existing exam subjects
	 * Some functionalities are added according to the user rights
	 */
	t._init = function(){
		// Check the readable right
		if(!t.can_see)
			return;
		t.section = new section("","Exams Subjects",t.table, false);
		t._setTableContent();
		container.appendChild(t.section.element);
		t._setStyle();
	};

	/**
	 * Set the seection layout into the container
	 */
	t._setStyle = function(){
		container.style.paddingTop = "20px";
		container.style.paddingLeft = "20px";
		container.style.paddingRight = "20px";
	};
	
	/**
	 * Set the section content
	 * One row is created by exam subject and functional buttons are added if allowed
	 */
	t._setTableContent = function(){
		//Set the header
		var tr_head = document.createElement("tr");
		var th = document.createElement("th");
		var text_header = document.createTextNode("Exam subjects ");
		th.appendChild(text_header);
		var info_subjects = document.createElement("img");
		info_subjects.src = theme.icons_16.info;
		info_subjects.style.verticalAlign = "bottom";
		tooltip(info_subjects,"An exam subject is made of parts and questions, and must represent the exam sheet given to the applicant. For instance, if you have three entrance examinations, \"Math & Logic\", \"English\", \"Speed and Accuracy\", you must create the three respective exam subjects.<br/> If you want to do any combination between the subjects parts (for instance separate the \"Math\" and the \"Logic\" parts), you will be able to do so at the <i>Exam Topics</i> step");
		th.appendChild(info_subjects);
		tr_head.appendChild(th);
		t.table.appendChild(tr_head);
		//set the body
		if(t.all_exams.length > 0)
			var ul = document.createElement("ul");
		for(var i = 0; i < t.all_exams.length; i++){
			var tr = document.createElement("tr");
			t._addExamRow(tr,i);
			ul.appendChild(tr);
		}
		if(t.all_exams.length > 0)
			t.table.appendChild(ul);
		
		//set the footer
		var create_button = document.createElement("button");
		create_button.className = "action";
		create_button.innerHTML = "<img src = '"+theme.build_icon("/static/selection/exam/subject_white.png",theme.icons_10.add,"right_bottom")+"'/> Create a subject";
		create_button.onclick = function(){
// 						location.assign("/dynamic/selection/page/exam/create_subject");
			var pop = new popup_window("Create an Exam Subject",
										theme.build_icon("/static/selection/exam/exam_16.png",theme.icons_10.add,"right_bottom"),
										t.container,
										false
									);
			pop.setContentFrame("/dynamic/selection/page/exam/create_subject");
			pop.onclose = function() {
				location.reload();
			};
			pop.show();
		};
		t.section.addToolBottom(create_button);
	};

	/**
	 * Create an exam subject row
	 * @param {HTML} tr where the row will be inserted
	 * @param {Number} i the index of the exam subject in the all_exams object
	 */
	t._addExamRow = function(tr,i){
		var td_name = document.createElement("td");
		var li = document.createElement("li");
		li.innerHTML = "<a title = 'See subject' class = 'black_link' href = '/dynamic/selection/page/exam/subject?id="+t.all_exams[i].id+"&readonly=true'/>"+t.all_exams[i].name+"</a>";
		td_name.appendChild(li);
		td_name.id = t.all_exams[i].id+"_td";
		tr.appendChild(td_name);
		tr.menu = []; // menu to display on mouse over
		
		export_button = t._createButton("<img src = '"+theme.icons_16._export+"'/>",t.all_exams[i].id);
		export_button.title = "Export this subject";
		export_button.onclick = function(){
			var t2 = this;
			var menu = new context_menu();
			menu.addTitleItem("", "Export format");
			menu.addIconItem('/static/data_model/excel_16.png', 'Excel 2007 (.xlsx)', function() { t._export_subject('excel2007',false,t2.id); });
			menu.addIconItem('/static/data_model/excel_16.png', 'Excel 5 (.xls)', function() { t._export_subject('excel5',false,t2.id); });
			menu.addIconItem('/static/selection/exam/sunvote_16.png', 'SunVote ETS compatible format', function() { t._export_subject('excel2007',true,t2.id); });
			menu.showBelowElement(document.getElementById(this.id+"_td"));
		};
		export_button.style.visibility = "hidden";
		export_button.className = "button_verysoft";
		td_export = document.createElement("td");
		td_export.appendChild(export_button);
		tr.appendChild(td_export);
		tr.menu.push(export_button);

//		see_button = t._createButton("<img src = '"+theme.icons_16.search+"'/>",t.all_exams[i].id);
//		see_button.title = "See this subject";
//		see_button.onclick = function(){
//			location.assign("/dynamic/selection/page/exam/subject?id="+this.id+"&readonly=true");
//		};
//		see_button.style.visibility = "hidden";
//		see_button.className = "button_verysoft";
//		td_see = document.createElement("td");
//		td_see.appendChild(see_button);
//		tr.appendChild(td_see);
//		tr.menu.push(see_button);
		
		if(t.can_manage){
			edit_button = t._createButton("<img src = '"+theme.icons_16.edit+"'/>",t.all_exams[i].id);
			edit_button.onclick = function(){
				location.assign("/dynamic/selection/page/exam/subject?id="+this.id);
			};
			edit_button.title = "Edit this subject";
			edit_button.style.visibility = "hidden";
			edit_button.className = "button_verysoft";
			td_edit = document.createElement("td");
			td_edit.appendChild(edit_button);
			tr.appendChild(td_edit);
			tr.menu.push(edit_button);
			
			remove_button = t._createButton("<img src = '"+theme.icons_16.remove+"'/>", t.all_exams[i].id);
			remove_button.title = "Remove this subject";
			remove_button.style.visibility = "hidden";
			remove_button.className = "button_verysoft";
			remove_button.onclick = function(){
				var subject_id = this.id;
				confirm_dialog("Do you really want to remove this exam subject and all the linked data?<br/><i>Parts, questions, topics...</i>",function(r){
					if(r){
						service.json("selection","exam/remove_subject",{id:subject_id},function(res){
							if(!res)
								error_dialog("An error occured, the subject was not removed");
							else {
								location.reload();
							}						
						});
					} else
						return;
				});
			};
			td_remove = document.createElement("td");
			td_remove.appendChild(remove_button);
			tr.appendChild(td_remove);
			tr.menu.push(remove_button);
		}

		require("animation.js",function() {
			animation.appearsOnOver(tr, tr.menu);
		});
	};

	/**
	 * Create a button div
	 * @param {HTML | String} content to set into the button
	 * @param {Number} id the id to set to the div
	 * @returns {HTML} the created button
	 */
	t._createButton = function(content, id){
		var div = document.createElement("div");
		div.innerHTML = content;
		div.className = "button";
		div.id = id;
		return div;
	};			

	/**
	 * Export an exam subject
	 * this method creates a hidden form that refers to the exam/export_subject service
	 * @param {String} format the exporting format
	 * @param {Boolean} compatible_clickers true if the exported file must match with SunVote ETS requirements
	 * @param {Number} exam_id the id of the exam subject to export
	 * For more details about the parameters, refer to export_subject service
	 */
	t._export_subject = function(format,compatible_clickers,exam_id){
		var form = document.createElement('form');
		form.action = "/dynamic/selection/service/exam/export_subject";
		form.method = "POST";
		var input = document.createElement("input");
		input.type = "hidden";
		input.name = "format";
		input.value = format;
		form.appendChild(input);
		var input2 = document.createElement("input");
		input2.type = "hidden";
		input2.value = exam_id;
		input2.name = "id";
		form.appendChild(input2);
		if(compatible_clickers){
			var input3 = document.createElement("input");
			input3.type = "hidden";
			input3.value = "true";
			input3.name = "clickers";
			form.appendChild(input3);
		}
		document.body.appendChild(form);
		form.submit();
	};
	
	require(["section.js","context_menu.js","popup_window.js"],function(){
		t._init();
	});
}