function IS_name(container, name, can_edit){
	var t = this;
	t.table = document.createElement("table");
	t.text = "";
	t.name = name;
	
	require(["autoresize_input.js","section.js"],function(){
		t._setSection();
		t._init();
	});
	
	t._setSection = function(){
		t.container_of_section_content = document.createElement("div");
		t.section = new section("/static/selection/IS/label.png","Information Session name",t.container_of_section_content,false);
	}
	
	t._init = function(){
		t.table.style.width = "100%";
		// t._setTableHeader();
		t._setTableBody();
		t.container_of_section_content.appendChild(t.table);
		container.appendChild(t.section.element);
	}
	
	// t._setTableHeader = function(){
		// var thead = document.createElement("thead");
		// var th = document.createElement("th");
		// var tr = document.createElement("tr");
		// th.colSpan = 2;
		// th.innerHTML = "<img src = '/static/selection/IS/label.png' style='vertical-align:bottom'/> Information Session name";
		// setCommonStyleTable(t.table, th, "#DADADA");
		// tr.appendChild(th);
		// thead.appendChild(th);
		// t.table.appendChild(thead);
	// }
	
	t._setTableBody = function(){
		var tbody = document.createElement("tbody");
		var tr = document.createElement("tr");
		var td1 = document.createElement("td");
		// var td2 = document.createElement("td");
		td1.innerHTML = "<font color='#808080'><b>Custom name: </b></font>"
		if(t.name == null){
			t.text = "";
		} else t.text = t.name.uniformFirstLetterCapitalized();
		if(can_edit){
			var input = document.createElement("input");
			input.value = t.text;
			autoresize_input(input,15);
			input.oninput = function(){
				if(this.value.checkVisible() && this.value != ""){
					t.name = this.value.uniformFirstLetterCapitalized();
				} else t.name = null;
			};
			td1.appendChild(input);
		} else {
			// if(t.name == null) t.text = "<i>Unnamed</i>";
			td1.innerHTML = "<font color='#808080'><b>Custom name: </b></font>"+t.text;
		}
		
		tr.appendChild(td1);
		// tr.appendChild(td2);
		tbody.appendChild(tr);
		t.table.appendChild(tbody);
	}
	
	t.getName = function(){
		return t.name;
	}
}