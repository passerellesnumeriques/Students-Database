/**
 * Create a table to manage an EligibilityRule object. One row is created for each topic set into the rule.
 * @param {HTMLElement|String}container
 * @param {Object} rule the EligibilityRule object to manage
 * @param {Array} all_topics an array containing all the topics set into the database
 * @param {Boolean} can_edit
 * @param {HTMLElement|null}footer_ending (optional), element to add at the end of the footer
 * @param {Number|null} index_in_all_rules array, given when the t.onupdaterule custom event is fired
 * @param {Function|null} onreset(optional) called when the table is reseted
 */
function manage_rule(container, rule, all_topics, can_edit, footer_ending, index_in_all_rules, onreset){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	t._table = document.createElement("table");
	
	t.onupdaterule = new Custom_Event(); //Custom event fired each time any data about the rule is updated	
	t._config = {}; //The config attribute used by the field_decimal objects
	t._config.can_be_null = false;
	t._config.integer_digits = 2;
	t._config.decimal_digits = 2;
	t._field_decimal_coeff = {}; //Object to store all the fields_decimal created to manage the coefficients data
	t._field_decimal_expected = {}; //Object to store all the fields_decimal created to manage the expected data
	
	/**
	 * Init the process, setting the table and style
	 * At the end of the process, onreset function is called
	 */
	t._init = function(){
		t._setTitle();
		t._setBody();
		if(can_edit)
			t._setFooter();
		container.appendChild(t._table);
		if(onreset) //Fire the function once the process is ended
			onreset();
	};
	
	/**
	 * Set the headers of each column (topic, required, coeff)
	 */
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
		t._table.appendChild(tr);
	};
	
	/**
	 * Set the body of the table
	 * Each topic has a row in the table
	 */
	t._setBody = function(){
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
				t._table.appendChild(tr);
				t._createTopicRow(tr,i);
			}
		}
	};
	
	/**
	 * Set the footer of the table. A row is added with a select element containing all the topics available (meaning not set in this rule)
	 * This method is only called if can_edit == true
	 */
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
		t._table.appendChild(tr);
	};
	
	/**
	 * Set the content of a row for a given topic
	 * The row contains the topic name, and field_decimals for coefficient and expected data.
	 * These fields decimals are stored into t._field_decimal_coeff (respectively t._field_decimal_expected) objects, referenced by their topic id
	 * @param {HTMLElement} tr the row to fill up
	 * @param {Number} index the index of the topic into the rule.topics array
	 */
	t._createTopicRow = function(tr, index){
		var td1 = document.createElement("td");//contains the topic data
		var td2 = document.createElement('td');//contains the grade expected for this topic
		var td3 = document.createElement('td');//contains the coeff
		var td4 = document.createElement('td');//contains the remove button
		td1.innerHTML = rule.topics[index].topic.name.uniformFirstLetterCapitalized();
		td1.style.textAlign = "center";
		td2.style.textAlign = "center";
		td3.style.textAlign = "center";
		td4.style.textAlign = "center";
		if(!rule.topics[index].coefficient)
			rule.topics[index].coefficient = 1.00;
		if(!rule.topics[index].expected)
			rule.topics[index].expected = 1.00;
		var config_grade_expected = t._config;
		config_grade_expected.max = rule.topics[index].topic.max_score; //The maximum value of the score expected is the max score of the topic
		if(can_edit){
			t._field_decimal_coeff[rule.topics[index].topic.id] = new field_decimal(rule.topics[index].coefficient,true,t._config);
			t._field_decimal_expected[rule.topics[index].topic.id] = new field_decimal(rule.topics[index].expected,true,config_grade_expected);
			td4.appendChild(t._createRemoveTopicButton(index));
		} else {
			t._field_decimal_coeff[rule.topics[index].topic.id] = new field_decimal(rule.topics[index].coefficient,false,t._config);
			t._field_decimal_expected[rule.topics[index].topic.id] = new field_decimal(rule.topics[index].expected,false,config_grade_expected);
		}
		//Add the custom event to the field_decimal onchange
		t._field_decimal_coeff[rule.topics[index].topic.id].ondatachanged.add_listener( function(){t.onupdaterule.fire(index_in_all_rules);});
		t._field_decimal_expected[rule.topics[index].topic.id].ondatachanged.add_listener( function(){t.onupdaterule.fire(index_in_all_rules);});
		td2.appendChild(t._field_decimal_expected[rule.topics[index].topic.id].getHTMLElement());
		var text_score = document.createTextNode("/"+rule.topics[index].topic.max_score);
		td2.appendChild(text_score);
		td3.appendChild(t._field_decimal_coeff[rule.topics[index].topic.id].getHTMLElement());
		tr.appendChild(td1);
		tr.appendChild(td2);
		tr.appendChild(td3);
		tr.appendChild(td4);
	};
	
	/**
	 * Create a remove topic button, to add at the end of a topic row
	 * When clicked, this button removes the topic from rule object, reset the table, and then fires onupdaterule custom event
	 * @param {Number} index the index of the topic into the rule.topics array
	 * @return {HTMLElement} div the button to insert into the document
	 */
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
	
	/**
	 * Reset the table, restarts the process calling t._init method
	 * The onreset function is called by the init method
	 */
	t.reset = function(){
		container.removeChild(t._table);
		delete t._table;
		t._table = document.createElement("table");
		t._init();
//		if(onreset)
//			onreset();
	};
	
	/**
	 * Check if the given topic is already set into the given rule
	 * @param {Number} id the id of the topic seeked
	 * @returns {Boolean} true if already set, else false
	 */
	t._isTopicInCurrentRule = function(id){
		for(var i = 0; i < rule.topics.length; i++){
			if(rule.topics[i].topic.id == id)
				return true;
		}
		return false;
	};
	
	/**
	 * Add a topic into the rule object and reset
	 * @param  {Number} id the topic id to add
	 */
	t._addTopic = function(id){
		var index = t._getTopicIndexInAllTopics(id);
		//Add in rule with coeff = 1
		rule.topics.push({coefficient:1,topic:all_topics[index]});
		//reset
		t.reset();
	};
	
	/**
	 * Get a topic index into all_topics array from its id
	 * @param {Number} id the id of the topic seeked
	 * @returns {Number} i the index found
	 */
	t._getTopicIndexInAllTopics = function(id){
		for(var i = 0; i < all_topics.length; i++){
			if(all_topics[i].id == id)
				return i;
		}
	};
	
	/**
	 * Get a topic index into rule.topics array from its id
	 * @param {Number} id the id of the topic seeked
	 * @returns {Number} i the index found
	 */
	t._getTopicIndexInRule = function(id){
		for(var i = 0; i < rule.topics.length; i++){
			if(rule.topics[i].topic.id == id)
				return i;
		}
	};
	
	/**
	 * Update the coefficients fields into all the rule.topics topics, from the data set into the t._field_decimal_coeff fields
	 */
	t._updateRuleCoefficientFields = function(){
		for(id in t._field_decimal_coeff){
			var index = t._getTopicIndexInRule(id);
			if(index != null)
				rule.topics[index].coefficient = t._field_decimal_coeff[id].getCurrentData();
		}
	};
	
	/**
	 * Update the expected fields into all the rule.topics topics, from the data set into the t._field_decimal_expected fields
	 */
	t._updateRuleExpectedFields = function(){
		for(id in t._field_decimal_expected){
			var index = t._getTopicIndexInRule(id);
			if(index != null)
				rule.topics[index].expected = t._field_decimal_expected[id].getCurrentData();
		}
	};
	
	/**
	 * Update the expected and coefficient fields of all the rule.topics objects
	 */
	t.updateRuleFields = function(){
		t._updateRuleCoefficientFields();
		t._updateRuleExpectedFields();
	};
	
	require([["typed_field.js","field_decimal.js"]],function(){
		t._init();
	});
}