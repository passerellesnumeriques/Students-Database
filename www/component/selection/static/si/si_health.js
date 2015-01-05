function si_health(section, list, applicant_id, can_edit) {
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
			var what = new field_text(item.what, true, {can_be_null:true,max_length:200,min_size:10});
			td.appendChild(what.getHTMLElement());
			what.onchange.addListener(function() { item.what = what.getCurrentData(); });
		} else if (item.what)
			td.appendChild(document.createTextNode(item.what)); 
		tr.appendChild(td = document.createElement("TD"));
		if (can_edit) {
			var cost = new field_text(item.cost, true, {can_be_null:true,max_length:200,min_size:10});
			td.appendChild(cost.getHTMLElement());
			cost.onchange.addListener(function() { item.cost = cost.getCurrentData(); });
		} else if (item.cost)
			td.appendChild(document.createTextNode(item.cost)); 
		if (can_edit) {
			tr.appendChild(td = document.createElement("TD"));
			var remove = document.createElement("BUTTON");
			remove.className = "flat small_icon";
			remove.style.verticalAlign = "middle";
			remove.style.marginBottom = "3px";
			remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
			remove.title = "Remove this illness";
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
		th.innerHTML = "Illness";
		tr.appendChild(th = document.createElement("TH"));
		th.innerHTML = "Cost";
		if (can_edit) tr.appendChild(th = document.createElement("TH"));
		for (var i = 0; i < list.length; ++i)
			this.createRow(list[i]);
		if (can_edit) {
			var add_button = document.createElement("BUTTON");
			add_button.className = "flat small_icon";
			add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
			add_button.title = "Add a person having illness";
			var id_counter = -1;
			add_button.onclick = function() {
				var item = {id:id_counter--,who:null,what:null,cost:null};
				list.push(item);
				t.createRow(item);
			};
			section.content.appendChild(add_button);
		}
	};
	this._initTable();
	if (can_edit)
		this.save = function(ondone) {
			var locker = lockScreen(null, "Saving Health Information...");
			service.json("selection","si/save_health",{applicant:applicant_id,list:list},function(res) {
				if (res) {
					for (var i = 0; i < res.length; ++i)
						for (var j = 0; j < list.length; ++j)
							if (res[i].given_id == list[j].id) { list[j].id = res[i].new_id; break; }
				}
				unlockScreen(locker);
				ondone();
			});
		};
}
