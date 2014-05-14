/**
 * Create a section object containing the custom name of the given Information Session
 * @param {String|HTMLElement}container
 * @param {String|NULL} name if any
 * @param {Boolean} can_edit
 * @param {String} title title of the section element
 */
function center_name(container, name, can_edit, title){
	if(typeof(container) == "string") container = document.getElementById(container);
	var t = this;
	t.table = document.createElement("table");
	t.onupdate = new Custom_Event();
	t.onupdate.add_listener(function() {
		if (t._name == name) // didn't change
			window.pnapplication.dataSaved("SelectionCenterCustomName");
		else
			window.pnapplication.dataUnsaved("SelectionCenterCustomName");
	});
	
	/**
	 * Get the name attribute
	 * @returns {String} the name of the IS
	 */
	t.getName = function(){
		return t._name;
	};
	
	/**Private attributes and functionalities*/
	
	t._text = "";
	t._name = name;
	
	/**
	 * Create a section object
	 */
	t._setSection = function(){
		t._container_of_section_content = document.createElement("div");
		t.section = new section("/static/selection/common_centers/label.png",title,t._container_of_section_content,false,false,"soft");
	};
	
	/**
	 * Launch the process, populate the section
	 */
	t._init = function(){
		t.table.style.width = "100%";
		t._setTableBody();
		t._container_of_section_content.appendChild(t.table);
		container.appendChild(t.section.element);
	};
	
	/**
	 * Set the content of the table
	 * A row is created containing an input for the custom name
	 */
	t._setTableBody = function(){
		var tbody = document.createElement("tbody");
		var tr = document.createElement("tr");
		var td1 = document.createElement("td");
		// var td2 = document.createElement("td");
		td1.innerHTML = "<font color='#808080'><b>Custom name: </b></font>"
		if(t._name == null){
			t._text = "";
		} else t._text = t._name.uniformFirstLetterCapitalized();
		if(can_edit){
			var input = document.createElement("input");
			input.type = 'text';
			input.value = t._text;
			inputAutoresize(input,15);
			input.oninput = function(){
				if(this.value.checkVisible()){
					t._name = this.value.uniformFirstLetterCapitalized();
				} else t._name = null;
				t.onupdate.fire();
			};
			td1.appendChild(input);
		} else {
			// if(t._name == null) t._text = "<i>Unnamed</i>";
			td1.innerHTML = "<font color='#808080'><b>Custom name: </b></font>"+t._text;
		}
		
		tr.appendChild(td1);
		// tr.appendChild(td2);
		tbody.appendChild(tr);
		t.table.appendChild(tbody);
	};
	
	require(["input_utils.js","section.js"],function(){
		t._setSection();
		t._init();
	});
}