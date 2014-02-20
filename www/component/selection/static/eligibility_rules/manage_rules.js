function manage_rules(container, all_rules, all_topics, can_edit, db_lock){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	t.all_manage_rule = [];
	
//	can_edit = false;
	
	t._init = function(){
		t._initDiagram();
		if(can_edit)
			t._addButtons();
		t._setTableStyle();
		t._fireLayoutEvent();
		
	};
	
	t._fireLayoutEvent = function(){
		fireLayoutEventFor(t.diagram_container);
	};
	
	t._initDiagram = function(){
		t.diagram_container = document.createElement("div");
		t.diagram = new diagram_display_manager(t.diagram_container,null,null,100);
		//Add the first node
		var index_first = t._getRootRuleIndex();
		if(index_first == null){//no root rule yet, create it
			//Add a first rule
			all_rules.push(new EligibilityRule(-1,null,[])); //the rule is added at the first level	
		}
		t._createFirstNode();
		
		//Add the last node
		t._createLastNode();
		
		//Add the other nodes
		for(var i = 0; i < all_rules.length; i++){
			if(all_rules[i].parent != null)
				t._createMiddleNode(i);
		}
		
		//Show
		container.appendChild(t.diagram_container);
		t.diagram.show();
	};
	
	t._createFirstNode = function(){
		var index = t._getRootRuleIndex();
		var div = document.createElement("div");
		var first = new manage_rule(div,all_rules[index],all_topics,can_edit,null,index,t._fireLayoutEvent);
		first.onupdaterule.add_listener(t._onManageRuleChange);
		t.all_manage_rule.push({index:index, manage_rule:first}); //store the manage_rule object and its index
		t.diagram.createStartNode(null,div,"root");
	};
	
	t._createLastNode = function(){
		var div = document.createElement("div");
		div.innerHTML = "<center><i>The applicant passed the exam step!</i></center>"
		t.diagram.createEndNode("<center><img src = '"+theme.icons_16.winner+"'/> Succeed</center>",div,"last");		
	};
	
	t._createMiddleNode = function(index){
		if(can_edit){
			var remove = document.createElement("div");
			remove.className = "button_verysoft";
			remove.innerHTML = "<img src = '"+theme.icons_16.remove_black+"'/>";
			remove.title = "Remove this rule";
			remove.index = index;
			remove.onclick = function(){
				//remove from all_rules
				all_rules.splice(this.index,1);
				//Remove from diagram
				t.diagram.removeNode(this.index);
				//Remove from all_manage_rule
				var i = t._getIndexInAllManageRule(this.index);
				t.all_manage_rule.splice(i,1);
				//reset error row
				t._onManageRuleChange();
			};
		} else
			var remove = null;
		var div = document.createElement("div");
		var node = new manage_rule(div,all_rules[index],all_topics,can_edit,remove,index,t._fireLayoutEvent);
		node.onupdaterule.add_listener(t._onManageRuleChange);
		t.all_manage_rule.push({index:index, manage_rule:node});
		t.diagram.createChildNode(null,div,index);
	};
	
	t._getIndexInAllManageRule = function(index){
		for(var i = 0; i < t.all_manage_rule.length; i++){
			if(t.all_manage_rule[i].index == index)
				return i;
		}
	};
	
	/**
	 * Only called if can_edit
	 */
	t._addButtons = function(){
		t._footer = document.createElement("div");
		container.appendChild(t._footer);
		t._footer.style.borderTop = "1px solid #808080";
		
		//Add intermediate rule button
		var add_rule = document.createElement("div");
		add_rule.className = "button";
		add_rule.innerHTML = "<img src = '"+theme.icons_16.add+"'/> Add intermediate rule";
		add_rule.onclick = function(){
			//Add the rule in all_rules
			var rule = new EligibilityRule(-1,"root",[]); //parent attribute can only be null or root. Will be updated after saving
			all_rules.push(rule);
			t._createMiddleNode(all_rules.length-1);
			t._onManageRuleChange();
		};
		t._footer.appendChild(add_rule);
		
		//Add save button
		t._save = document.createElement("div");
		t._save.className = "button";
		t._save.innerHTML = "<img src = '"+theme.icons_16.save+"'/> Save";
		t._save.onclick = function(){
			//Last validity check
			if(t._checkNoDoubleRule() == null && t._checkNoEmptyRule() == null){
				var locker = lock_screen();
				alert(db_lock);
				service.json("selection","eligibility_rules/save_rules",{all_rules:all_rules,db_lock:db_lock},function(res){
					if(!res){
						error_dialog("An error occured your informations were not saved");
						unlock_screen(locker);
					} else {
						all_rules = res;
						t.reset(locker);
						window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "Your informations have been successfuly saved!", [{action:"close"}], 5000));
					}
						
				});
			}
		};
		t._footer.appendChild(t._save);
		
		//Add an error message displayer
		t._error = document.createElement("div");
		t._error.style.display = "inline-block";
		var text = document.createTextNode("There are mistakes in your rules");
		t._error_detail = null;
		var show_detail = document.createElement("div");
		show_detail.className = "button_verysoft";
		show_detail.innerHTML = "<i>See detail</i>";
		show_detail.style.fontWeight = "bold";
		show_detail.onclick = function(){
			require("popup_window.js",function(){
				var pop = new popup_window("Error details",theme.icons_16.error,t._error_detail);
				pop.show();
			});
		};
		t._error.appendChild(text);
		t._error.appendChild(show_detail);
		t._error.style.color = "red";
		t._error.style.visibility = "hidden";
		t._footer.appendChild(t._error);
	};
	
	/**
	 * Check there is no double rule in all the given rules<br/>
	 * @returns {Null|String} null if no double rule, else error message to display
	 */
	t._checkNoDoubleRule = function(){
		if(all_rules.length > 1){
			var res = null;
			for(var i = 0; i < all_rules.length -1; i++){
				for(var j = i+1; j < all_rules.length; j++){
					if(t._areRulesEquals(all_rules[i], all_rules[j])){
						if(res == null)
							res = "The following intermediate rules are identical:<ul>";
						res += "<li>Rules "+t._getRuleIndexInMiddleColumn(i)+" and "+t._getRuleIndexInMiddleColumn(j)+"</li>";
					}
				}
			}
			if(res != null)
				res += "</ul>";
			return res;
		} else //Nothing to do
			return;
	};
	
	t._getRuleIndexInMiddleColumn = function(index){
		var count = 1;
		var i = 0;
		while(i != index){
			if(all_rules[i].parent != null) //root rule may be in the middle of the all_rules list
				count++;
			i++;
		}
		return i;
	};
	
	t._areRulesEquals = function(r1,r2){
		if(r1.parent == r2.parent){ //As there is only one root rule, this condition obliges the two rules to be "middle" ones
			if(r1.topics.length == r2.topics.length){
				if(r1.topics.length == 1){
					if(r1.topics[0].topic.id == r2.topics[0].topic.id){
						if(r1.topics[0].coefficient == r2.topics[0].coefficient && r1.topics[0].expected == r2.topics[0].expected)
							return true;
					}
				} else {
					var global_res = true;
					for(var i = 0; i < r1.topics.length; i++){
						var local_res = false;
						for(var j = 0; j < r2.topics.length; j++){
							if(r1.topics[i].topic.id == r2.topics[j].topic.id){
								if(r1.topics[i].coefficient == r2.topics[j].coefficient && r1.topics[i].expected == r2.topics[j].expected){
									local_res = true;
									break;
								}
							}
						}
						if(!local_res)
							global_res = false;
					}
					return global_res;
				}
			}
		}
		return false;
	};
	
	t._checkNoEmptyRule = function(){
		var res = null;
		for(var i = 0; i < all_rules.length; i++){
			if(all_rules[i].topics.length == 0){
				if(res == null)
					res = "<br/>Some rules are empty:<ul>";
				if(all_rules[i].parent == null)
					res += "<li>The first rule</li>";
				else
					res += "<li>Intermediate rule "+t._getRuleIndexInMiddleColumn(i)+" </li>";
			}
		}
		if(res)
			res += "</ul>";
		return res;
	};
	
	t._onManageRuleChange = function(index){
		//update the all_rules object
		if(index != null){ //else called by the remove rule button, so the rule object has already been updated
			var index_in_all_manage_rule = t._getIndexInAllManageRule(index);
			t.all_manage_rule[index_in_all_manage_rule].manage_rule.updateRuleFields();
		}
		//update the error message / save button
		var identical = t._checkNoDoubleRule();
		var empty = t._checkNoEmptyRule();
		if(identical != null || empty != null){
			t._error.style.visibility = "";
			t._save.style.visibility = "hidden";
			if(identical)
				t._error_detail = identical;
			if(empty){
				if(identical)
					t._error_detail += empty;
				else
					t._error_detail = empty;
			}
		} else {
			t._error_detail = null;
			t._error.style.visibility = "hidden";
			t._save.style.visibility = "";
		}

		t._fireLayoutEvent(); //The width of any node may have been updated
	};
	
	t._getRootRuleIndex = function(){
		for(var i = 0; i < all_rules.length; i++){
			if(all_rules[i].parent == null)
				return i;
		}
	};
	
	t._setTableStyle = function(){
//		t.diagram_container.style.backgroundColor = "#FFFFFF";
	};
	
	t.reset = function(locker){
		//Reset the diagram
		t.diagram.close();
		delete t.diagram;
		container.removeChild(t.diagram_container);
		delete t.diagram_container;
		container.removeChild(t._footer);
		delete t._footer;
		t._init();
		if(locker)
			unlock_screen(locker);
	};
	
	require(["diagram_display_manager.js","manage_rule.js","eligibility_rules_objects.js"],function(){
		t._init();
	});
}