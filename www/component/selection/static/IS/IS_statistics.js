function IS_statistics(container, separate_boys_girls, can_edit, boys_expected, boys_real, girls_expected, girls_real){
	var t = this;
	t.table = document.createElement("table");
	t.text_boys_expected = "";
	t.text_boys_real = "";
	t.text_girls_expected = "";
	t.text_girls_real = "";
	t.boys_expected = boys_expected;
	t.boys_real = boys_real;
	t.girls_expected = girls_expected;
	t.girls_real = girls_real;
	
	t._setSection = function(){
		t.container_of_section_content = document.createElement("div");
		t.section = new section("/static/selection/IS/statistics.png","Statistics",t.container_of_section_content,false);
	}
	
	t._init = function(){
		t.table.style.width = "100%";
		t._setTextFields();
		// t._setTableHeader();
		t._setTableBody();
		t.container_of_section_content.appendChild(t.table);
		container.appendChild(t.section.element);
	}
	
	t._setTextFields = function(){
		if(t.boys_expected != null)
			t.text_boys_expected = t.boys_expected;
		if(t.boys_real != null)
			t.text_boys_real = t.boys_real;
		if(t.girls_expected != null)
			t.text_girls_expected = t.girls_expected;
		if(t.girls_real != null)
			t.text_girls_real = t.girls_real;
	}
	
	// t._setTableHeader = function(){
		// var thead = document.createElement("thead");
		// var th = document.createElement("th");
		// var tr = document.createElement("tr");
		// th.colSpan = 3;
		// th.innerHTML = "<img src = '/static/selection/IS/statistics.png' style='vertical-align:bottom'/> Statistics";
		// setCommonStyleTable(t.table, th, "#DADADA");
		// tr.appendChild(th);
		// thead.appendChild(th);
		// t.table.appendChild(thead);
	// }
	
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
		if(t.boys_expected == null) t.text_boys_expected = 0;
		if(t.boys_real == null) t.text_boys_real = 0;
		if(t.girls_expected == null) t.text_girls_expected = 0;
		if(t.girls_real == null) t.text_girls_real = 0;
		
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
				var data22 = parseInt(t.text_boys_expected) + parseInt(t.text_girls_expected);
				t.field_int_22 = new field_integer(data22,true,field_int_config); 
//				var input22 = document.createElement("input");
//				input22.type = 'text';
				var input22 = t.field_int_22.getHTMLElement();
				autoresize_input(input22);
				input22.style.marginLeft = "15px";				
//				var input23 = document.createElement("input");
				var data23 = parseInt(t.text_boys_real) + parseInt(t.text_girls_real);
				t.field_int_23 = new field_integer(data23,true,field_int_config);
				input23 = t.field_int_23.getHTMLElement();
				input23.style.marginLeft = "15px";
				input23.type = 'text';
				autoresize_input(input23);
//				input22.value = t.text_boys_expected + t.text_girls_expected;
//				input23.value = t.text_boys_real + t.text_girls_real;
//				input22.oninput = function(){
//					var new_figure = t._setNewFigure(this.value);
//					if(new_figure == 0) t.boys_expected = null;
//					else t.boys_expected = new_figure;
//					t.text_boys_expected = new_figure;
//					t._reset_girls_figures();
//				};
//				input23.oninput = function(){
//					var new_figure = t._setNewFigure(this.value);
//					if(new_figure == 0) t.boys_real = null;
//					else t.boys_real = new_figure;
//					t.text_boys_real = new_figure;
//					t._reset_girls_figures();
//				};
				td22.appendChild(input22);
				td23.appendChild(input23);
			} else {
				td22.innerHTML = parseInt(t.text_boys_expected) + parseInt(t.text_girls_expected);
				td22.style.textAlign = "center";
				td23.innerHTML = parseInt(t.text_boys_real) + parseInt(t.text_girls_real);
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
//				var input22 = document.createElement("input");
//				var input23 = document.createElement("input");
//				var input32 = document.createElement("input");
//				var input33 = document.createElement("input");
//				input22.type = 'text';
//				input23.type = 'text';
//				input32.type = 'text';
//				input33.type = 'text';
				t.field_int_22 = new field_integer(t.text_boys_expected,true,field_int_config);
				input22 = t.field_int_22.getHTMLElement();
				t.field_int_23 = new field_integer(t.text_boys_real,true,field_int_config);
				input23 = t.field_int_23.getHTMLElement();
				t.field_int_32 = new field_integer(t.text_girls_expected,true,field_int_config);
				input32 = t.field_int_32.getHTMLElement();
				t.field_int_33 = new field_integer(t.text_girls_real,true,field_int_config);
				input33 = t.field_int_33.getHTMLElement();
				input22.style.marginLeft = "15px";
				input23.style.marginLeft = "15px";
				input32.style.marginLeft = "15px";
				input33.style.marginLeft = "15px";
				autoresize_input(input22);
				autoresize_input(input23);
				autoresize_input(input32);
				autoresize_input(input33);
//				input22.value = t.text_boys_expected;
//				input22.oninput = function(){
//					var new_figure = t._setNewFigure(this.value);
//					if(new_figure == 0) t.boys_expected = null;
//					else t.boys_expected = new_figure;
//					t.text_boys_expected = new_figure;
//				};
//				input23.value = t.text_boys_real;
//				input23.oninput = function(){
//					var new_figure = t._setNewFigure(this.value);
//					if(new_figure == 0) t.boys_real = null;
//					else t.boys_real = new_figure;
//					t.text_boys_real = new_figure;
//				};
//				input32.value = t.text_girls_expected;
//				input32.oninput = function(){
//					var new_figure = t._setNewFigure(this.value);
//					if(new_figure == 0) t.girls_expected = null;
//					else t.girls_expected = new_figure;
//					t.text_girls_expected = new_figure;
//				};
//				input33.value = t.text_girls_real;
//				input33.oninput = function(){
//					var new_figure = t._setNewFigure(this.value);
//					if(new_figure == 0) t.girls_real = null;
//					else t.girls_real = new_figure;
//					t.text_girls_real = new_figure;
//				};
				td22.appendChild(input22);
				td23.appendChild(input23);
				td32.appendChild(input32);
				td33.appendChild(input33);
			} else {
				td22.innerHTML = t.text_boys_expected;
				td23.innerHTML = t.text_boys_real;
				td32.innerHTML = t.text_girls_expected;
				td33.innerHTML = t.text_girls_real;
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
	}
	
	t._setNewFigure = function(text){
		if(isNaN(text)){
			text = 0;
		}
		return text;
	}
	
	t._reset_girls_figures = function(){
		t.girls_expected = null;
		t.text_girls_expected = 0;
		t.girls_real = null;
		t.text_girls_real = 0;
	}
	
	t.getFigures = function(){
		var figures = {};
		if(!separate_boys_girls){
			figures.girls_expected = 0;
			figures.girls_real = 0;
		} else {
			figures.girls_expected = t.field_int_32.getCurrentData();
			figures.girls_real = t.field_int_33.getCurrentData();
		}
//		figures.boys_expected = t.boys_expected;
		figures.boys_expected = t.field_int_22.getCurrentData();
		figures.boys_real = t.field_int_23.getCurrentData();
		return figures;
	}
	
	require(["autoresize_input.js","section.js","field_integer.js"],function(){
		t._setSection();
		t._init();
	});
}