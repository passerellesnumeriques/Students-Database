function manage_rule(container, rule, all_topics, can_edit, title_ending){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	t.table = document.createElement("table");
	
	t.config = {};
	t.config.can_be_null = false;
	t.config.integer_digits = 2;
	t.config.decimal_digits = 2;
	
	t._init = function(){
		t._setTitle();
		t._setBody();
		if(can_edit)
			t._setFooter();
		container.appendChild(t.table);
		
	};
	
	t._setTitle = function(){
		var tr = document.createElement("tr");
		var th1 = document.createElement("th");
		var td2 = document.createElement("td");
		th1.innerHTML = "Minimum";
		tr.appendChild(th1);
		if(title_ending){
			td2.appendChild(title_ending);
			tr.appendchild(td2);
		} else
			th1.colSpan = 2;
		t.table.appendChild(tr);
	};
	
	t._setBody = function(){
		var ul = document.createElement("ul");
		t.table.appendChild(ul);
		if(rule.topics.length == 0){
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			td.innerHTML = "No Topic Yet";
			td.style.fontStyle = "italic";
			td.style.color = "#808080";
			td.colSpan = 3;
			tr.appendChild(td);
		} else {
			for(var i = 0; i < rule.topics.length; i++){
				var tr = document.createElement("tr");
				ul.appendChild(tr);
				t._createTopicRow(tr,i);
			}
		}
	};
	
	t._setFooter = function(){
		var tr = document.createElement("tr");
		var td1 = document.createElement("td");
//		var td2 = document.createElement("td");
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
				if(this.options[this.selectedIndex].value != 0)
					t._addTopic(this.options[this.selectedIndex].value);
			};
//			td2.appendChild(select);
			td1.innerHTML = "Add a topic";
			td1.appendChild(select);
		} else {
			td1.innerHTML = "No more topic";
			td1.style.color = "#808080";
		}
		td1.style.fontStyle = "italic";
		td1.style.textAlign = "center";
		tr.appendChild(td1);
//		tr.appendChild(td2);
		t.table.appendChild(tr);
	};
	
	t._createTopicRow = function(tr, index){
		var td1 = document.createElement("td");
		var td2 = document.createElement('td');
		var td3 = document.createElement('td');
		var li = document.createElement('li');
		td1.appendChild(li);
		var text = rule.topics[index].topic.name.uniformFirstLetterCapitalized()+", "+rule.topics[index].topic.max_score+" points";
		li.innerHTML = text;
		if(!rule.topics[index].coefficient)
			rule.topics[index].coefficient = 1;
		if(can_edit){
			t.field_decimal = new field_decimal(rule.topics[index].coefficient,true,t.config);
			var td3 = document.createElement("td");
			td3.appendChild(t._createRemoveTopicButton(index));
		} else {
			t.field_decimal = new field_decimal(rule.topics[index].coefficient,false,t.config);
			td2.colSpan = 2;
		}
		td2.appendChild(t.field_decimal.getHTMLElement());
		tr.appendChild(td1);
		tr.appendChild(td2);
		if(can_edit)
			tr.appendChild(td3);
	};
	
	t._createRemoveTopicButton = function(index){
		var div = document.createElement("div");
		div.className = "button_verysoft";
		div.innerHTML = "<img src = '"+theme.icons_16.remove+"'/>";
		div.index = index;
		div.onclick = function(){
			//Remove from rule
			rule.topics.splice(index,1);
			//Reset
			t.reset();
		};
		return div;
	};
	
	t.reset = function(){
		container.removeChild(t.table);
		delete t.table;
		t.table = document.createElement("table");
		t._init();
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
	
	require([["typed_field.js","field_decimal.js"]],function(){
		t._init();
	});
}