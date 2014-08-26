/**
 * Create a section containing statistics about an information session
 * @param {String|HTMLElement}container
 * @param {Boolean} separate_boys_girls true if the boys and girls figures shall be separated, according to the selection campaign config
 * @param {Boolean} can_edit
 * @param {Number|NULL} boys_expected if any
 * @param {Number|NULL} boys_real if any
 * @param {Number|NULL} girls_expected if any
 * @param {Number|NULL} girls_real if any
 */
function is_statistics(container, separate_boys_girls, can_edit, boys_expected, boys_real, girls_expected, girls_real){
	var t = this;
	if(typeof(container) == "string") container = document.getElementById(container);
	t.table = document.createElement("table");
	
	/**
	 * Get the figures attributes
	 * @returns {Object} containing 4 attributes: <ul><li><code>girls_expected</code> {Number} if the separate_boys_girls param is set as false, this attribute is set as 0</li><li><code>girls_real</code> {Number} if the separate_boys_girls param is set as false, this attribute is set as 0</li><li><code>boys_expected</code> {Number}</li><li><code>boys_real</code> {Number}</li></ul>
	 */
	t.getFigures = function(){
		var figures = {};
		if(!separate_boys_girls){
			figures.girls_expected = 0;
			figures.girls_real = 0;
		} else {
			if (can_edit) {
				figures.girls_expected = t.field_int_32.getCurrentData();
				figures.girls_real = t.field_int_33.getCurrentData();
			} else {
				figures.girls_expected = girls_expected;
				figures.girls_real = girls_real;
			}
		}
		if (can_edit) {
			figures.boys_expected = t.field_int_22.getCurrentData();
			figures.boys_real = t.field_int_23.getCurrentData();
		} else {
			figures.boys_expected = boys_expected;
			figures.boys_real = boys_real;
		}
		return figures;
	};
	
	/**Private attributes and functionalities*/
	t._text_boys_expected = "";
	t._text_boys_real = "";
	t._text_girls_expected = "";
	t._text_girls_real = "";
	t._boys_expected = boys_expected;
	t._boys_real = boys_real;
	t._girls_expected = girls_expected;
	t._girls_real = girls_real;
	
	/**
	 * Set the section element
	 */
	t._setSection = function(){
		t.container_of_section_content = document.createElement("div");
		t.section = new section("/static/selection/is/statistics.png","Statistics",t.container_of_section_content,false,false,"soft");
	};
	
	/**
	 * Launch the process, populate the table
	 */
	t._init = function(){
		t.table.style.width = "100%";
		t._setTextFields();
		t._setTableBody();
		t.container_of_section_content.appendChild(t.table);
		container.appendChild(t.section.element);
	};
	
	/**
	 * Initiate the text fields (this fields contains the data to display: for instance 0 instead of null into the database)
	 */
	t._setTextFields = function(){
		if(t._boys_expected != null)
			t._text_boys_expected = t._boys_expected;
		if(t._boys_real != null)
			t._text_boys_real = t._boys_real;
		if(t._girls_expected != null)
			t._text_girls_expected = t._girls_expected;
		if(t._girls_real != null)
			t._text_girls_real = t._girls_real;
	};
	
	t._datachanged = function(name) {
		window.pnapplication.dataUnsaved("IS_statistics_"+name);
	};
	t._dataunchanged = function(name) {
		window.pnapplication.dataSaved("IS_statistics_"+name);
	};
	
	/**
	 * Populate the table body, with one row per sex and one column per category (real / expected)
	 * All the figures are handled by field_int
	 */
	t._setTableBody = function(){
		var tbody = document.createElement("tbody");
		var tr1 = document.createElement("tr");
		var td11 = document.createElement("td");
		var td12 = document.createElement("td");
		var td13 = document.createElement("td");
		var tr2 = document.createElement("tr");
		var td21 = document.createElement("td");
		var td22 = document.createElement("td");
		var td23 = document.createElement("td");
		var div12 = document.createElement("div");
		var div13 = document.createElement("div");
		div12.innerHTML = "<font color='#808080'><b>Expected </b></font>";
		div13.innerHTML = "<font color='#808080'><b>Real </b></font>";
		div12.style.paddingLeft = "10px";
		div12.style.paddingRight = "10px";
		div13.style.paddingLeft = "10px";
		div13.style.paddingRight = "10px";
		td12.appendChild(div12);
		td13.appendChild(div13);
		if(t._boys_expected == null) t._text_boys_expected = 0;
		if(t._boys_real == null) t._text_boys_real = 0;
		if(t._girls_expected == null) t._text_girls_expected = 0;
		if(t._girls_real == null) t._text_girls_real = 0;
		
		tr1.appendChild(td11);
		tr1.appendChild(td12);
		tr1.appendChild(td13);
		tbody.appendChild(tr1);
		tr2.appendChild(td21);
		tr2.appendChild(td22);
		tr2.appendChild(td23);
		tbody.appendChild(tr2);
		t.table.appendChild(tbody);
		
		var field_int_config = {};
		field_int_config.min = 0;
		if(!separate_boys_girls){
			td21.innerHTML = "<font color='#808080'><b>Attendees </b></font>";
			if(can_edit){
				var data22 = parseInt(t._text_boys_expected) + parseInt(t._text_girls_expected);
				t.field_int_22 = new field_integer(data22,true,field_int_config); 
				var input22 = t.field_int_22.getHTMLElement();
				t.field_int_22.ondatachanged.add_listener(function() { t._datachanged('field_int_22'); });
				t.field_int_22.ondataunchanged.add_listener(function() { t._dataunchanged('field_int_22'); });
				inputAutoresize(input22);
				input22.style.marginLeft = "15px";				
				var data23 = parseInt(t._text_boys_real) + parseInt(t._text_girls_real);
				t.field_int_23 = new field_integer(data23,true,field_int_config);
				input23 = t.field_int_23.getHTMLElement();
				t.field_int_23.ondatachanged.add_listener(function() { t._datachanged('field_int_23'); });
				t.field_int_23.ondataunchanged.add_listener(function() { t._dataunchanged('field_int_23'); });
				input23.style.marginLeft = "15px";
				input23.type = 'text';
				inputAutoresize(input23);
				td22.appendChild(input22);
				td23.appendChild(input23);
			} else {
				td22.innerHTML = parseInt(t._text_boys_expected) + parseInt(t._text_girls_expected);
				td22.style.textAlign = "center";
				td23.innerHTML = parseInt(t._text_boys_real) + parseInt(t._text_girls_real);
				td23.style.textAlign = "center";
			}
		} else {
			var tr3 = document.createElement("tr");
			var td31 = document.createElement("td");
			var td32 = document.createElement("td");
			var td33 = document.createElement("td");
			td21.innerHTML = "<font color='#808080'><b>Boys </b></font>";
			td31.innerHTML = "<font color='#808080'><b>Girls </b></font>";
			if(can_edit){
				t.field_int_22 = new field_integer(t._text_boys_expected,true,field_int_config);
				input22 = t.field_int_22.getHTMLElement();
				t.field_int_22.ondatachanged.add_listener(function() { t._datachanged('field_int_22'); });
				t.field_int_22.ondataunchanged.add_listener(function() { t._dataunchanged('field_int_22'); });
				t.field_int_23 = new field_integer(t._text_boys_real,true,field_int_config);
				input23 = t.field_int_23.getHTMLElement();
				t.field_int_23.ondatachanged.add_listener(function() { t._datachanged('field_int_23'); });
				t.field_int_23.ondataunchanged.add_listener(function() { t._dataunchanged('field_int_23'); });
				t.field_int_32 = new field_integer(t._text_girls_expected,true,field_int_config);
				input32 = t.field_int_32.getHTMLElement();
				t.field_int_32.ondatachanged.add_listener(function() { t._datachanged('field_int_32'); });
				t.field_int_32.ondataunchanged.add_listener(function() { t._dataunchanged('field_int_32'); });
				t.field_int_33 = new field_integer(t._text_girls_real,true,field_int_config);
				input33 = t.field_int_33.getHTMLElement();
				t.field_int_33.ondatachanged.add_listener(function() { t._datachanged('field_int_33'); });
				t.field_int_33.ondataunchanged.add_listener(function() { t._dataunchanged('field_int_33'); });
				input22.style.marginLeft = "15px";
				input23.style.marginLeft = "15px";
				input32.style.marginLeft = "15px";
				input33.style.marginLeft = "15px";
				inputAutoresize(input22);
				inputAutoresize(input23);
				inputAutoresize(input32);
				inputAutoresize(input33);
				td22.appendChild(input22);
				td23.appendChild(input23);
				td32.appendChild(input32);
				td33.appendChild(input33);
			} else {
				td22.innerHTML = t._text_boys_expected;
				td23.innerHTML = t._text_boys_real;
				td32.innerHTML = t._text_girls_expected;
				td33.innerHTML = t._text_girls_real;
				td22.style.textAlign = "center";
				td23.style.textAlign = "center";
				td32.style.textAlign = "center";
				td33.style.textAlign = "center";
			}
			
			tr3.appendChild(td31);
			tr3.appendChild(td32);
			tr3.appendChild(td33);
			tbody.appendChild(tr3);
		}
	};
	
	/**
	 * Convert a text which is not a number into 0
	 * @param {String} text
	 * @returns {Stirng} the text, updated to 0 if it was not a number
	 */
	t._setNewFigure = function(text){
		if(isNaN(text)){
			text = 0;
		}
		return text;
	};
	
	/**
	 * Reset the girls data
	 */
	t._reset_girls_figures = function(){
		t._girls_expected = null;
		t._text_girls_expected = 0;
		t._girls_real = null;
		t._text_girls_real = 0;
	};
	
	require(["input_utils.js","section.js",["typed_field.js","field_integer.js"]],function(){
		t._setSection();
		t._init();
	});
}