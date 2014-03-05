function eligibility_rules_main_page(container, can_see, can_manage, all_topics, validated){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	t.internal_container = document.createElement("table");//The container inside the section
	t.internal_container.style.position = "relative"; 
	t.internal_container.style.width = '100%';
	t.internal_container.style.height = '100%';
	t.table_topics_container = document.createElement("div");//Contains the table with the exam topics
	t.table_topics = document.createElement('table');//Contains the exam topics data
	t.table_topics_container.appendChild(t.table_topics);
//	t.table_topics_container.style.width = "30%";
//	t.table_topics_container.style.position = "absolute";
//	t.table_topics_container.style.left = "0px";
//	t.table_topics_container.style.top = "0px";
	

	t._init = function(){
		// Check the readable right
		if(!can_see)
			return;
		t.section = new section("","Exam topics and eligibility rules",t.internal_container , false);
//		t._setTableTopicsContent();
//		t._setRulesContent();
		container.appendChild(t.section.element);
		t._setInternalContainerStructure();
		t._setStyle();
	};
	
	t._setInternalContainerStructure = function(){		
		//Set the first row with the headers for the left and the right part
		var tr_head = document.createElement("tr");
		var th_topics = document.createElement("th");
		var th_rules = document.createElement("th");
		var text1 = document.createTextNode("Exam topics ");
		var text2 = document.createTextNode("Eligibility rules ");
		th_topics.appendChild(text1);
		th_rules.appendChild(text2);
		tr_head.appendChild(th_topics);
		tr_head.appendChild(th_rules);
		t.internal_container.appendChild(tr_head);
		//Set the info buttons
		var info_topics = document.createElement("img");
		info_topics.src = theme.icons_16.info;
		info_topics.style.cursor = "pointer";
		info_topics.style.verticalAlign = "bottom";
		info_topics.onmouseover = function(){
			createToolTip(this,"An exam topic is the entity used for creating the eligibility rules<br/>For instance, if there is a math subject, and a logic object, you may want to group these two exams for the eligibility rules: you shall create a \"Math and Logic\" topic.<br/>A topic can be set as <b>full subject</b> for an exam subject, meaning that all the parts from this subject belong to the topic. Also, when a part is added to the subject, it is automatically added to the topic.<br/>By default, when an exam subject is created a topic is also created, and set as full subject for this subject.<br/>To avoid any adverse effect you must respect the following rules:<ul><li>All the parts of the exam subject must appear in the topics</li><li>A part can only appear one time in all the topics</li></ul>");
		};
		info_topics.onmouseout = function(){
			removeToolTip(this);
		};
		th_topics.appendChild(info_topics);
		
		var info_rules = document.createElement("img");
		info_rules.src = theme.icons_16.info;
		info_rules.style.cursor = "pointer";
		info_rules.style.verticalAlign = "bottom";
		info_rules.onmouseover = function(){
			createToolTip(this,"Eligibility rules are made of exam topics.<br/>Each rule node on the diagram is a minimum to be respected, to pass this rule.<br/>This diagram shall be read from the left side to the right one.<br/> To know if an applicant passes the exam, he must pass the first rule node (the one on the left of the diagram) and at least one middle rule (in the middle column), so that he can reach the last node, \"Succeed\". So the middle column represents the \"thresholds\" rules.<br/>In each rule node, you can add / remove any exam topic, and for each topic you can:<ul><li>Set the expected grade (minimum)</li><li>Set a coefficient</li></ul>");
		};
		info_rules.onmouseout = function(){
			removeToolTip(this);
		};
		th_rules.appendChild(info_rules);
		
		//Create the row for the topics and the rules
		var tr = document.createElement("tr");
		var td_topics = document.createElement("td");
		t._td_rules = document.createElement("td");
		td_topics.style.width = "30%";
		td_topics.style.borderRight = "1px solid #808080";
		t._td_rules.style.width = "70%";
		tr.appendChild(td_topics);
		tr.appendChild(t._td_rules);
		t.internal_container.appendChild(tr);
		
		//Fill up
		t._setTableTopicsContent(td_topics);
		t._setRulesContent();
	};
	
	t._setStyle = function(){
		container.style.paddingTop = "20px";
		container.style.paddingLeft = "20px";
		container.style.paddingRight = "20px";
		t._setInternalContainerHeight();
	};
	
	t._setTableTopicsContent = function(cont){
		cont.appendChild(t.table_topics_container);
		//set the body
		if(all_topics.length > 0)
			var ul = document.createElement("ul");
		for(var i = 0; i < all_topics.length; i++){
			var tr = document.createElement("tr");
			t._addTopicRow(tr,i);
			ul.appendChild(tr);
		}
		if(all_topics.length > 0)
			t.table_topics.appendChild(ul);
		
		//Add the row about the validation of the topics
		var tr_status = document.createElement("tr");
		t._addValidStatusRow(tr_status);
		t.table_topics.appendChild(tr_status);
		//set the footer
		var tr_foot = document.createElement("tr");
		var td_foot = document.createElement("td");
		tr_foot.style.height = "100%";
		td_foot.style.height = "100%";
		var create_button = document.createElement("div");
		create_button.className = "button";
		create_button.innerHTML = "<img src = '"+theme.build_icon("/static/selection/eligibility_rules/rules_16.png",theme.icons_10.add,"right_bottom")+"'/> Create a topic";
		create_button.onclick = function(){
 			location.assign("/dynamic/selection/page/eligibility_rules/manage_exam_topic");
		};
		if(can_manage && validated != null){
			td_foot.appendChild(create_button);
//			new vertical_align(td_foot,"bottom");
		}
		tr_foot.appendChild(td_foot);
		t.table_topics.appendChild(tr_foot);
		
	};
	
	t._addTopicRow = function(tr, i){
		var td_name = document.createElement("td");
		var li = document.createElement("li");
		li.innerHTML = all_topics[i].name;
		td_name.appendChild(li);
		tr.appendChild(td_name);
		tr.menu = []; // menu to display on mouse over
		
		see_button = t._createButton("<img src = '"+theme.icons_16.search+"'/>",all_topics[i].id);
		see_button.title = "See this topic";
		see_button.onclick = function(){
			location.assign("/dynamic/selection/page/eligibility_rules/manage_exam_topic?id="+this.id+"&read_only=true");
		};
		see_button.style.visibility = "hidden";
		see_button.className = "button_verysoft";
		var td_see = document.createElement("td");
		td_see.appendChild(see_button);
		tr.appendChild(td_see);
		tr.menu.push(see_button);
		
		if(can_manage){
			edit_button = t._createButton("<img src = '"+theme.icons_16.edit+"'/>",all_topics[i].id);
			edit_button.title = "Edit this topic";
			edit_button.onclick = function(){
				location.assign("/dynamic/selection/page/eligibility_rules/manage_exam_topic?id="+this.id);
			};
			edit_button.style.visibility = "hidden";
			edit_button.className = "button_verysoft";
			var td_edit = document.createElement("td");
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
		if(all_topics.length > 0){
			if(validated != null){
				div.innerHTML = validated;
				div.style.color = "red";
				div.style.paddingLeft = "2px";
			} else {
				div.innerHTML = "All the parts appear one and only one time in the topics, so eligibility rules can be applied";
				div.style.color = "green";
				div.style.paddingLeft = "0px";
			}
			td.appendChild(div);
			tr.appendChild(td);
		}
	};
	
	t._setInternalContainerHeight = function(){
		//Once the table with the topic is set, get its height
		var h = getHeight(t.table_topics_container);
		h = h + 10;
		h = h > 250 ? h : 250; //Set a minimum height, otherwize problems for displaying the diagram
		t.internal_container.style.height = h+"px";
		//Then center the topics table
		t.table_topics_container.style.height = h+"px";
		t.table_topics.style.height = "100%";
		new vertical_align(t.table_topics_container,"middle");
	};
	
	t._setRulesContentHeight = function(){
		var container_height = getHeight(t.rules_container);
		var h = container_height - 25; //Add the space for a button
		if(h > 0)
			t.rules_content.style.height = h+"px";
	};
	
	t._setRulesContent = function(){
		//Done on a backend to avoid too long loading time
		service.json("selection","eligibility_rules/status_from_steps",{},function(r){
			if(r && r.topic_exist){
				var all_rules = null;
				t.rules_container = document.createElement("div");
				t.rules_container.style.height = "100%";
				t.rules_container.style.width = "100%";
				t._td_rules.appendChild(t.rules_container);
				t.rules_content = document.createElement("div");
				t.rules_content.style.height = "90%";
				if(r.rule_exist){
					service.json("selection","eligibility_rules/get_json_all_rules",{},function(res){
						if(!res) return;
						else all_rules = res;
						require("manage_rules.js",function(){
							t._manage_rules = new manage_rules(t.rules_content, all_rules, all_topics, false);
						});
					});
				} else {
					//No rule yet so no need to display the diagram. But add the button to create rules
					var text = document.createTextNode("There is no eligibility rule yet");
					t.rules_container.appendChild(text);
					t.rules_container.style.fontStyle = "italic";
					t.rules_container.style.textAlign = "center";
					t.rules_container.style.verticalAlign = "middle";
				}
				var rules_footer = document.createElement("div");
				rules_footer.style.heigth = "24px";
				rules_footer.style.paddingLeft = "30px";
				rules_footer.id = "footer_eligibility_rules";
				if(r.can_valid||r.can_unvalid)
					t._setRulesFooter(rules_footer);//Contains the manage rules button
				t.rules_container.appendChild(t.rules_content);
				t.rules_container.appendChild(rules_footer);
//				t.internal_container.appendChild(t.rules_container);
				t._setRulesContentHeight();
			}//else nothing to do
		});
	};
	
	t._setRulesFooter = function(e){
		if(can_manage){
			//Add the manage_rules button
			var manage = t._createButton("<img src = '"+theme.icons_16.edit+"'/> Edit the rules", "manage_rules_button");
			manage.onclick = function(){
				require("popup_window.js",function(){
					var pop = new popup_window("Manage Eligibility Rules","/static/selection/eligibility_rules/rules_16.png",null);
					pop.setContentFrame("/dynamic/selection/page/eligibility_rules/manage");
					pop.onclose = t._resetRulesContainer;
					pop.show();
				});
			};
			e.appendChild(manage);
		}
		//Add the tips buttons
	};
	
	t._resetRulesContainer = function(){
		t._manage_rules.closeDiagram();
		while(t.rules_container.firstChild)
			t.rules_container.removeChild(t.rules_container.firstChild);
		t._td_rules.removeChild(t.rules_container);
		delete t.rules_container;
		delete t._manage_rules;
		t._setRulesContent();
	};
	
	/**
	 * Create a button div
	 * @param {HTMLElement | String} content to set into the button
	 * @param {String|Null} id the id to set to the div
	 * @returns {HTML} the created button
	 */
	t._createButton = function(content, id){
		var div = document.createElement("div");
		if(typeof content == "string")
			div.innerHTML = content;
		else
			div.appendChild(content);
		div.className = "button";
		if(id)
			div.id = id;
		return div;
	};			
	
	require(["section.js","popup_window.js","vertical_align.js"],function(){
		t._init();
	});
}