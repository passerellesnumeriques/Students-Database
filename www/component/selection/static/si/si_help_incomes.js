function si_help_incomes(section, list, applicant_id, can_edit) {
	section.content.style.padding = "4px";
	if (!can_edit && list.length == 0) {
		section.content.style.fontStyle = "italic";
		section.content.innerHTML = "None";
		return;
	}
	var t=this;
	this.createRow = function(item) {
		var tr = document.createElement("TR");
		this.table.appendChild(tr);
		var td;
		tr.appendChild(td = document.createElement("TD"));
		if (can_edit) {
			var who = new field_text(item.who, true, {can_be_null:false,min_length:1,max_length:200,min_size:10});
			td.appendChild(who.getHTMLElement());
			who.onchange.addListener(function() { item.who = who.getCurrentData(); });
		} else
			td.appendChild(document.createTextNode(item.who)); 
		tr.appendChild(td = document.createElement("TD"));
		if (can_edit) {
			var income = new field_decimal(item.income, true, {can_be_null:true,min:0,integer_digits:10,decimal_digits:2});
			td.appendChild(income.getHTMLElement());
			income.onchange.addListener(function() { item.income = income.getCurrentData(); });
		} else if (item.income)
			td.appendChild(document.createTextNode(item.income)); 
		tr.appendChild(td = document.createElement("TD"));
		if (can_edit) {
			var income_freq = new field_text(item.income_freq, true, {can_be_null:true,max_length:25,min_size:5});
			td.appendChild(income_freq.getHTMLElement());
			income_freq.onchange.addListener(function() { item.income_freq = income_freq.getCurrentData(); });
		} else if (item.income_freq)
			td.appendChild(document.createTextNode(item.income_freq)); 
		tr.appendChild(td = document.createElement("TD"));
		if (can_edit) {
			var comment = new field_text(item.comment, true, {can_be_null:true,max_length:200,min_size:10});
			td.appendChild(comment.getHTMLElement());
			comment.onchange.addListener(function() { item.comment = comment.getCurrentData(); });
		} else if (item.comment)
			td.appendChild(document.createTextNode(item.comment));
		if (can_edit) {
			tr.appendChild(td = document.createElement("TD"));
			var remove = document.createElement("BUTTON");
			remove.className = "flat small_icon";
			remove.style.verticalAlign = "middle";
			remove.style.marginBottom = "3px";
			remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
			remove.title = "Remove this item";
			td.appendChild(remove);
			remove.onclick = function() {
				list.removeUnique(item);
				t.table.removeChild(tr);
			};
		}
	};
	this._initTable = function() {
		this.table = document.createElement("TABLE");
		section.content.appendChild(this.table);
		var tr, th;
		this.table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(th = document.createElement("TH"));
		th.innerHTML = "Who";
		tr.appendChild(th = document.createElement("TH"));
		th.innerHTML = "Amount";
		tr.appendChild(th = document.createElement("TH"));
		th.innerHTML = "Frequency";
		tr.appendChild(th = document.createElement("TH"));
		th.innerHTML = "Comment";
		if (can_edit) tr.appendChild(th = document.createElement("TH"));
		for (var i = 0; i < list.length; ++i)
			this.createRow(list[i]);
		if (can_edit) {
			var add_button = document.createElement("BUTTON");
			add_button.className = "flat small_icon";
			add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
			add_button.title = "Add an income";
			var id_counter = -1;
			add_button.onclick = function() {
				var item = {id:id_counter--,who:null,income:null,income_freq:null,comment:null};
				list.push(item);
				t.createRow(item);
			};
			section.content.appendChild(add_button);
		}
	};
	this._initTable();
	if (can_edit)
		this.save = function(ondone) {
			var locker = lock_screen(null, "Saving Helping NGO and Sponsorships...");
			service.json("selection","si/save_help_incomes",{applicant:applicant_id,list:list},function(res) {
				if (res) {
					for (var i = 0; i < res.length; ++i)
						for (var j = 0; j < list.length; ++j)
							if (res[i].given_id == list[j].id) { list[j].id = res[i].new_id; break; }
				}
				unlock_screen(locker);
				ondone();
			});
		};
}
