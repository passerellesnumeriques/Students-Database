function eligibility_rules_main_page(container, can_see, can_manage, all_topics, validated){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	t.table = document.createElement('table');
	
	t._init = function(){
		// Check the readable right
		if(!can_see)
			return;
		t.section = new section("","Exam topics for eligibility rules",t.table, false);
		t._setTableContent();
		container.appendChild(t.section.element);
		t._setStyle();
	};
	
	t._setStyle = function(){
		container.style.paddingTop = "20px";
		container.style.paddingLeft = "20px";
		container.style.paddingRight = "20px";
	};
	
	t._setTableContent = function(){
		//set the body
		if(all_topics.length > 0)
			var ul = document.createElement("ul");
		for(var i = 0; i < all_topics.length; i++){
			var tr = document.createElement("tr");
			t._addTopicRow(tr,i);
			ul.appendChild(tr);
		}
		if(all_topics.length > 0)
			t.table.appendChild(ul);
		
		//Add the row about the validation of the topics
		var tr_status = document.createElement("tr");
		t._addValidStatusRow(tr_status);
		t.table.appendChild(tr_status);
		//set the footer
		var tr_foot = document.createElement("tr");
		var td_foot = document.createElement("td");
		var create_button = document.createElement("div");
		create_button.className = "button";
		create_button.innerHTML = "<img src = '"+theme.build_icon("/static/selection/eligibility_rules/rules_16.png",theme.icons_10.add,"right_bottom")+"'/> Create a topic";
		create_button.onclick = function(){
 			location.assign("/dynamic/selection/page/eligibility_rules/manage_exam_topic");
		};
		if(can_manage && validated != null)
			td_foot.appendChild(create_button);
		tr_foot.appendChild(td_foot);
		t.table.appendChild(tr_foot);
	};
	
	t._addTopicRow = function(tr, i){
		var td_name = document.createElement("td");
		var li = document.createElement("li");
		li.innerHTML = all_topics[i].name;
		td_name.appendChild(li);
//		td_name.id = t.all_exams[i].id+"_td";
		tr.appendChild(td_name);
		tr.menu = []; // menu to display on mouse over
		
		if(can_manage){
			edit_button = t._createButton("<img src = '"+theme.icons_16.edit+"'/> Edit",all_topics[i].id);
			edit_button.onclick = function(){
//				alert("/dynamic/selection/page/eligibility_rules/manage_exam_topic?id="+this.id);
				location.assign("/dynamic/selection/page/eligibility_rules/manage_exam_topic?id="+this.id);
			};
			edit_button.style.visibility = "hidden";
			edit_button.className = "button_verysoft";
			td_edit = document.createElement("td");
			td_edit.appendChild(edit_button);
			tr.appendChild(td_edit);
			tr.menu.push(edit_button);
		}

		tr.onmouseover = function(){
			for(var i = 0; i < this.menu.length; i++)
				this.menu[i].style.visibility = "visible";
		};
		tr.onmouseout = function(){
			for(var i = 0; i < this.menu.length; i++)
				this.menu[i].style.visibility = "hidden";
		};
	};
	
	t._addValidStatusRow = function(tr){
		var td = document.createElement("td");
		//TODO set Colspan
		var div = document.createElement("div");
		if(validated != null){
			div.innerHTML = validated;
			div.style.color = "red";
		} else {
			div.innerHTML = "All the parts appear one and only one time in the topics, so eligibility rules can be applied";
			div.style.color = "green";
		}
		td.appendChild(div);
		tr.appendChild(td);
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
	
	require(["section.js","popup_window.js"],function(){
		t._init();
	});
}