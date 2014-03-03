function match_people_in_excel(peoples, excel_frame, excel_container, wiz, ondone) {
	if (typeof excel_container == 'string') excel_container = excel_frame.document.getElementById(excel_container);
	
	var doc = excel_frame.document;
	wiz.setTitle("/static/people/people_list_32.png", "Find people names in Excel file");
	var container = doc.createElement("DIV");
	var header = doc.createElement("DIV");
	container.appendChild(header);
	container.appendChild(excel_container);
	excel_container.setAttribute("layout", "fill");
	wiz.setContent(container);

	header.style.backgroundColor = 'white';
	header.style.borderBottom = "1px solid black";

	var div = doc.createElement("DIV"); header.appendChild(div);
	div.style.padding = "2px";
	div.innerHTML = "How and where the names of people are in the Excel file ?";
	
	var createRangeSelector = function(container) {
		var input = doc.createElement("INPUT");
		input.type = 'text';
		input.size = 50;
		input.disabled = 'disabled';
		input.value = "";
		input.sheet = -1;
		input.range = null;
		container.appendChild(input);
		var button = doc.createElement("IMG");
		button.className = "button";
		button.src = "/static/excel/select_range.png";
		button.style.verticalAlign = "bottom";
		container.appendChild(button);
		button.onclick = function() {
			var sheet = excel_frame.excel.getActiveSheet();
			if (sheet == null) { input.value = ""; input.sheet = -1; input.range = null; return; };
			var sel = sheet.getSelection();
			if (sel == null) { input.value = ""; input.sheet = -1; input.range = null; return; };
			if (sel.start_col != sel.end_col) { alert("Please select a range with only 1 column"); };
			input.sheet = excel_frame.excel.getActiveSheetIndex();
			input.range = sel;
			input.value = sheet.name+'!'+excel_frame.getExcelColumnName(sel.start_col)+sel.start_row+":"+excel_frame.getExcelColumnName(sel.end_col)+sel.end_row;
		};
		return input;
	};
	
	div = doc.createElement("DIV"); header.appendChild(div);
	var radio_separate = doc.createElement("INPUT");
	radio_separate.type = 'radio';
	radio_separate.name = 'choice_how_names_are';
	div.appendChild(radio_separate);
	div.appendChild(doc.createTextNode("First names and last names are in separate columns"));
	var div_column_first_name = doc.createElement("DIV"); div.appendChild(div_column_first_name);
	div_column_first_name.style.marginLeft = "20px";
	div_column_first_name.appendChild(doc.createTextNode("Select the range containing the first names: "));
	div_column_first_name.style.visibility = 'hidden';
	div_column_first_name.style.position = 'absolute';
	var select_first_name = createRangeSelector(div_column_first_name);
	var div_column_last_name = doc.createElement("DIV"); div.appendChild(div_column_last_name);
	div_column_last_name.style.marginLeft = "20px";
	div_column_last_name.appendChild(doc.createTextNode("Select the range containing the last names: "));
	div_column_last_name.style.visibility = 'hidden';
	div_column_last_name.style.position = 'absolute';
	var select_last_name = createRangeSelector(div_column_last_name);
	
	div = doc.createElement("DIV"); header.appendChild(div);
	var radio_merge = doc.createElement("INPUT");
	radio_merge.type = 'radio';
	radio_merge.name = 'choice_how_names_are';
	div.appendChild(radio_merge);
	div.appendChild(doc.createTextNode("Only one column contains the full names"));
	var div_column_full_name = doc.createElement("DIV"); div.appendChild(div_column_full_name);
	div_column_full_name.style.marginLeft = "20px";
	div_column_full_name.appendChild(doc.createTextNode("Select the range containing the names: "));
	div_column_full_name.style.visibility = 'hidden';
	div_column_full_name.style.position = 'absolute';
	var select_full_name = createRangeSelector(div_column_full_name);
	
	excel_frame.require("vertical_layout.js", function() {
		new excel_frame.vertical_layout(container);
	});
	
	var select_changed = function() {
		if (radio_separate.checked) {
			div_column_first_name.style.visibility = 'visible';
			div_column_first_name.style.position = 'static';
			div_column_last_name.style.visibility = 'visible';
			div_column_last_name.style.position = 'static';
			div_column_full_name.style.visibility = 'hidden';
			div_column_full_name.style.position = 'absolute';
		} else {
			div_column_first_name.style.visibility = 'hidden';
			div_column_first_name.style.position = 'absolute';
			div_column_last_name.style.visibility = 'hidden';
			div_column_last_name.style.position = 'absolute';
			div_column_full_name.style.visibility = 'visible';
			div_column_full_name.style.position = 'static';
		}
		layout.invalidate(wiz.container);
	};
	radio_separate.onchange = select_changed;
	radio_merge.onchange = select_changed;
	
	wiz.resetButtons();
	wiz.addContinueButton(function() {
		if (radio_separate.checked) {
			if (select_first_name.sheet == -1) { alert('Please select the range containing the first names in the Excel file'); return; }
			if (select_last_name.sheet == -1) { alert('Please select the range containing the last names in the Excel file'); return; }
			if (select_first_name.sheet != select_last_name.sheet) { alert('You selected first names and last names in different Excel sheets. Please use a single sheet.'); return; }
			var nb_fn = (select_first_name.range.end_row-select_first_name.range.start_row+1);
			var nb_ln = (select_last_name.range.end_row-select_last_name.range.start_row+1);
			if (nb_fn != nb_ln) { alert('You selected '+nb_fn+' first name(s) and '+nb_ln+' last name(s): please select the same number so we can match them'); return; }
			var names = [];
			for (var row = select_first_name.range.start_row; row <= select_first_name.range.end_row; ++row) {
				var fn = excel_frame.excel.sheets[select_first_name.sheet].getCell(select_first_name.range.start_col,row).getValue();
				names.push({first_name:fn,last_name:"",row:row});
			}
			var i = 0;
			for (var row = select_last_name.range.start_row; row <= select_last_name.range.end_row; ++row) {
				var ln = excel_frame.excel.sheets[select_last_name.sheet].getCell(select_last_name.range.start_col,row).getValue();
				names[i].last_name = ln;
				i++;
			}
			match_people_in_excel__check_separate(peoples, names, wiz, select_first_name.sheet, ondone);
		} else if (radio_merge.checked) {
			if (select_full_name.sheet == -1) { alert('Please select the range containing the names in the Excel file'); return; }
			var names = [];
			for (var row = select_full_name.range.start_row; row <= select_full_name.range.end_row; ++row) {
				var name = excel_frame.excel.sheets[select_full_name.sheet].getCell(select_full_name.range.start_col,row).getValue();
				names.push({name:name,row:row});
			}
			match_people_in_excel__check_merge(peoples, names, wiz, select_full_name.sheet, ondone);
		} else {
			alert('Please indicate how and where the names are in the Excel file');
		}
	});
}

function match_people_in_excel__check_separate(peoples, names, wiz, sheet_index, ondone) {
	wiz.resetContent();
	wiz.resetButtons();

	for (var i = 0; i < peoples.length; ++i) {
		var p = peoples[i];
		p.row = -1;
		for (var j = 0; j < names.length; ++j) {
			if (p.first_name.trim().toLowerCase() != names[j].first_name.trim().toLowerCase()) continue;
			if (p.last_name.trim().toLowerCase() != names[j].last_name.trim().toLowerCase()) continue;
			p.row = names[j].row;
			break;
		}
	}
	var fullnames = [];
	for (var i = 0; i < names.length; ++i)
		fullnames.push({name:names[i].first_name+' '+names[i].last_name,row:names[i].row});
	match_people_in_excel__check_screen(peoples, fullnames, wiz, sheet_index, ondone);
}
function match_people_in_excel__check_merge(peoples, names, excel_frame, excel_container, sheet_index, ondone) {
	wiz.resetContent();
	wiz.resetButtons();
	
	for (var i = 0; i < peoples.length; ++i) {
		var p = peoples[i];
		p.row = -1;
		for (var j = 0; j < names.length; ++j) {
			var fn1 = p.first_name.trim().toLowerCase()+' '+p.last_name.trim().toLowerCase();
			var fn2 = p.last_name.trim().toLowerCase()+' '+p.first_name.trim().toLowerCase();
			if (fn1 == names[j].name.trim().toLowerCase() || fn2 == names[j].name.trim().toLowerCase()) {
				p.row = names[j].row;
				break;
			}
		}
	}
	match_people_in_excel__check_screen(peoples, names, wiz, sheet_index, ondone);
}

function match_people_in_excel__check_screen(peoples, names, wiz, sheet_index, ondone) {
	wiz.setTitle("/static/people/people_list_32.png", "Please check and adjust how the names are matched between the Excel file and the Database");

	var doc = document;
	var container = doc.createElement("DIV");
	container.style.overflow = "auto";
	var table = doc.createElement("TABLE");
	table.className = 'all_borders';
	table.style.backgroundColor = 'white';
	container.appendChild(table);
	wiz.setContent(container);

	var tr, td;
	table.appendChild(tr = doc.createElement("TR"));
	tr.appendChild(td = doc.createElement("TH"));
	td.innerHTML = "Names from Excel File";
	tr.appendChild(td = doc.createElement("TH"));
	td.innerHTML = "Names from Database";
	tr.appendChild(td = doc.createElement("TH"));
	tr.appendChild(td = doc.createElement("TH"));
	td.innerHTML = "Names which cannot be matched";
	
	var nb = 0;
	var selects = [];
	var td_no_match;
	
	var reset_selects = function() {
		td_no_match.rowSpan = nb;
		for (var i = 0; i < selects.length; ++i) {
			var select = selects[i];
			while (select.options.length > 0) select.options.remove(0);
			var o = doc.createElement("OPTION");
			o.value = -1;
			o.text = "";
			select.add(o);
			for (var j = 0; j < peoples.length; ++j) {
				if (peoples[j].row >= 0) continue;
				o = doc.createElement("OPTION");
				o.value = j;
				o.text = peoples[j].first_name+' '+peoples[j].last_name;
				select.add(o);
			}
			select.onchange = function() {
				if (this.value < 0) return;
				var name = this.to_match_name;
				var people = peoples[this.value];
				create_tr(people, name);
				selects.remove(this);
				this.div.parentNode.removeChild(this.div);
				reset_selects();
			};
		}
	};
	var create_select = function(name) {
		var div = doc.createElement("DIV");
		div.appendChild(doc.createTextNode(name.name));
		var select = doc.createElement("SELECT");
		selects.push(select);
		div.appendChild(select);
		td_no_match.appendChild(div);
		select.div = div;
		select.to_match_name = name;
	};
	var create_tr = function(people, name) {
		table.appendChild(tr = doc.createElement("TR"));
		if (first_tr != tr)
			tr.appendChild(td = doc.createElement("TD"));
		else
			td = first_tr.childNodes[0];
		td.innerHTML = name.name;
		if (first_tr != tr)
			tr.appendChild(td = doc.createElement("TD"));
		else
			td = first_tr.childNodes[1];
		td.innerHTML = people.first_name+' '+people.last_name;
		if (first_tr != tr)
			tr.appendChild(td = doc.createElement("TD"));
		else
			td = first_tr.childNodes[2];
		if (!first_tr) first_tr = tr;
		var icon = doc.createElement("IMG");
		icon.src = theme.icons_16.remove;
		icon.className = 'button_verysoft';
		icon.style.padding = "0px";
		icon.style.verticalAlign = 'bottom';
		td.appendChild(icon);
		icon.tr = tr;
		icon.people = people;
		icon._name = name;
		icon.onclick = function() {
			if (this.tr != first_tr)
				table.removeChild(this.tr);
			else {
				first_tr.childNodes[0].innerHTML = "";
				first_tr.childNodes[1].innerHTML = "";
				first_tr.childNodes[2].innerHTML = "";
			}
			this.people.row = -1;
			nb--;
			create_select(this._name);
			reset_selects();
		};
		nb++;
	};
	
	var first_tr = null;
	for (var i = 0; i < peoples.length; ++i) {
		if (peoples[i].row < 0) continue;
		var name = null;
		for (var j = 0; j < names.length; ++j) if (names[j].row == peoples[i].row) { name = names[j]; break; }
		create_tr(peoples[i], name);
	}

	if (!first_tr) table.appendChild(first_tr = doc.createElement("TR"));
	first_tr.appendChild(td = doc.createElement("TD"));
	td.style.verticalAlign = "top";
	td_no_match = td;
	
	for (var i = 0; i < names.length; ++i) {
		var found = false;
		for (var j = 0; j < peoples.length; ++j) if (peoples[j].row == names[i].row) { found = true; break; }
		if (found) continue;
		create_select(names[i]);
	}
	
	reset_selects();
	
	wiz.resetButtons();
	wiz.addContinueButton(function() {
		var new_peoples = [];
		for (var i = 0; i < peoples.length; ++i)
			if (peoples[i].row >= 0) new_peoples.push(peoples[i]);
		ondone(new_peoples, sheet_index);
	});
}