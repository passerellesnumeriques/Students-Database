/**
 * 
 * @param container
 * @param rule
 * @param all_topics
 * @param can_edit
 * @param title_ending
 * @param {Number|null} index_in_all_rules in the all_rules array, given when the t.onupdaterule custom event is fired 
 */
function manage_rule(container, rule, all_topics, can_edit, footer_ending, index_in_all_rules, onreset){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	t.table = document.createElement("table");
	
	t.onupdaterule = new Custom_Event();
	
	t.config = {};
	t.config.can_be_null = false;
	t.config.integer_digits = 2;
	t.config.decimal_digits = 2;
	t.field_decimal_coeff = {};
	t.field_decimal_expected = {};
	
	t._init = function(){
		t._setTitle();
		t._setBody();
		if(can_edit)
			t._setFooter();
		container.appendChild(t.table);
		if(onreset) //Fire the function once the process is ended
			onreset();
	};
	
	t._setTitle = function(){
		var tr = document.createElement("tr");
		var th1 = document.createElement("th");
		var th2 = document.createElement("th");
		var th3 = document.createElement("th");
		var th4 = document.createElement("th");
		th1.innerHTML = "Topic";
		th2.innerHTML = "Required";
		th3.innerHTML = "Coeff";
		tr.appendChild(th1);
		tr.appendChild(th2);
		tr.appendChild(th3);
		tr.appendChild(th4);
		t.table.appendChild(tr);
	};
	
	t._setBody = function(){
//		var ul = document.createElement("ul");
//		t.table.appendChild(ul);
		if(rule.topics.length == 0){
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			td.innerHTML = "No Topic Yet";
			td.style.fontStyle = "italic";
			td.style.color = "#808080";
			td.colSpan = 4;
			tr.appendChild(td);
		} else {
			for(var i = 0; i < rule.topics.length; i++){
				var tr = document.createElement("tr");
//				ul.appendChild(tr);
//				t.table.appendChild(ul);
				t.table.appendChild(tr);
				t._createTopicRow(tr,i);
			}
		}
	};
	
	t._setFooter = function(){
		var tr = document.createElement("tr");
		var td1 = document.createElement("td");
		var td2 = document.createElement("td");
		var can_be_selected = [];
		for(var i = 0; i < all_topics.length; i++){
			if(!t._isTopicInCurrentRule(all_topics[i].id))
				can_be_selected.push({id:all_topics[i].id, name:all_topics[i].name});
		}
		if(can_be_selected.length > 0){
			var select = document.createElement('select');
			var start = document.createElement("option");
			start.value = 0;
			select.appendChild(start);
			for(var i = 0; i < can_be_selected.length; i++){
				var option = document.createElement("option");
				option.innerHTML = can_be_selected[i].name.uniformFirstLetterCapitalized();
				option.value = can_be_selected[i].id;
				select.appendChild(option);
			}
			select.onchange = function(){
				if(this.options[this.selectedIndex].value != 0){
					t._addTopic(this.options[this.selectedIndex].value);
					//Fire the custom event
					t.onupdaterule.fire(index_in_all_rules);
				}
			};
//			td2.appendChild(select);
			var text = document.createTextNode("Add a topic ");
			td1.appendChild(text);
			td1.appendChild(select);
			td1.colSpan = 3;
		} else {
			td1.innerHTML = "No more topic";
			td1.style.color = "#808080";
			td1.colSpan = 3;
		}
		td1.style.fontStyle = "italic";
		td1.style.textAlign = "center";
		tr.appendChild(td1);
		if(footer_ending)
			td2.appendChild(footer_ending);
		td1.style.borderTop = "1px solid #808080";
		td2.style.borderTop = "1px solid #808080";
		tr.appendChild(td2);
		t.table.appendChild(tr);
	};
	
	t._createTopicRow = function(tr, index){
		var td1 = document.createElement("td");//contains the topic data
		var td2 = document.createElement('td');//contains the grade expected for this topic
		var td3 = document.createElement('td');//contains the coeff
		var td4 = document.createElement('td');//contains the remove button
//		var li = document.createElement('li');
//		td1.appendChild(li);
		td1.innerHTML = rule.topics[index].topic.name.uniformFirstLetterCapitalized();
		td1.style.textAlign = "center";
		td2.style.textAlign = "center";
		td3.style.textAlign = "center";
		td4.style.textAlign = "center";
		if(!rule.topics[index].coefficient)
			rule.topics[index].coefficient = 1.00;
		if(!rule.topics[index].expected)
			rule.topics[index].expected = 1.00;
//		td3.appendChild(document.createTextNode("coeff "));
		var config_grade_expected = t.config;
		config_grade_expected.max = rule.topics[index].topic.max_score; //The maximum value of the score expected is the max score of the topic
		if(can_edit){
			t.field_decimal_coeff[rule.topics[index].topic.id] = new field_decimal(rule.topics[index].coefficient,true,t.config);
			t.field_decimal_expected[rule.topics[index].topic.id] = new field_decimal(rule.topics[index].expected,true,config_grade_expected);
			td4.appendChild(t._createRemoveTopicButton(index));
		} else {
			t.field_decimal_coeff[rule.topics[index].topic.id] = new field_decimal(rule.topics[index].coefficient,false,t.config);
			t.field_decimal_expected[rule.topics[index].topic.id] = new field_decimal(rule.topics[index].expected,false,config_grade_expected);
		}
		//Add the custom event to the field_decimal onchange
//		t.field_decimal_coeff.ondatachanged = t.onupdaterule.fire(index_in_all_rules);
//		t.field_decimal_expected.ondatachanged = t.onupdaterule.fire(index_in_all_rules);
		t.field_decimal_coeff[rule.topics[index].topic.id].ondatachanged.add_listener( function(){t.onupdaterule.fire(index_in_all_rules);});
		t.field_decimal_expected[rule.topics[index].topic.id].ondatachanged.add_listener( function(){t.onupdaterule.fire(index_in_all_rules);});
		td2.appendChild(t.field_decimal_expected[rule.topics[index].topic.id].getHTMLElement());
		var text_score = document.createTextNode("/"+rule.topics[index].topic.max_score);
		td2.appendChild(text_score);
		td3.appendChild(t.field_decimal_coeff[rule.topics[index].topic.id].getHTMLElement());
		tr.appendChild(td1);
		tr.appendChild(td2);
		tr.appendChild(td3);
		tr.appendChild(td4);
	};
	
	t._createRemoveTopicButton = function(index){
		var div = document.createElement("div");
		div.className = "button_verysoft";
		div.innerHTML = "<img src = '"+theme.icons_16.remove+"'/>";
		div.index = index;
		div.title = "Remove this topic from the rule";
		div.onclick = function(){
			//Remove from rule
			rule.topics.splice(index,1);
			//Reset
			t.reset();
			//Fire the custom event
			t.onupdaterule.fire(index_in_all_rules);
		};
		return div;
	};
	
	t.reset = function(){
		container.removeChild(t.table);
		delete t.table;
		t.table = document.createElement("table");
		t._init();
		if(onreset)
			onreset();
	};
	
	t._isTopicInCurrentRule = function(id){
		for(var i = 0; i < rule.topics.length; i++){
			if(rule.topics[i].topic.id == id)
				return true;
		}
		return false;
	};
	
	t._addTopic = function(id){
		var index = t._getTopicIndexInAllTopics(id);
		//Add in rule with coeff = 1
		rule.topics.push({coefficient:1,topic:all_topics[index]});
		//reset
		t.reset();
	};
	
	t._getTopicIndexInAllTopics = function(id){
		for(var i = 0; i < all_topics.length; i++){
			if(all_topics[i].id == id)
				return i;
		}
	};
	
	t._getTopicIndexInRule = function(id){
		for(var i = 0; i < rule.topics.length; i++){
			if(rule.topics[i].topic.id == id)
				return i;
		}
	};
	
	t._updateRuleCoefficientFields = function(){
		for(id in t.field_decimal_coeff){
			var index = t._getTopicIndexInRule(id);
			rule.topics[index].coefficient = t.field_decimal_coeff[id].getCurrentData();
		}
	};
	
	t._updateRuleExpectedFields = function(){
		for(id in t.field_decimal_expected){
			var index = t._getTopicIndexInRule(id);
			rule.topics[index].expected = t.field_decimal_expected[id].getCurrentData();
		}
	};
	
	t.updateRuleFields = function(){
		t._updateRuleCoefficientFields();
		t._updateRuleExpectedFields();
	};
	
	require([["typed_field.js","field_decimal.js"]],function(){
		t._init();
	});
}