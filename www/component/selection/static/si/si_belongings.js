function belongings(section, list, applicant_id, can_edit) {
	section.content.style.padding = "4px";
	this.list = list;
	if (can_edit) {
		var t=this;
		var add_button = document.createElement("BUTTON");
		add_button.className = "flat small_icon";
		add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
		add_button.title = "Add an item";
		var id_counter = -1;
		add_button.onclick = function() {
			var item = {id:id_counter--,description:""};
			t.list.push(item);
			t.createRow(item);
		};
		section.content.appendChild(add_button);
		this.createRow = function(belonging) {
			var div = document.createElement("DIV");
			var text = new field_text(belonging.description, true, {can_be_null:false,min_length:1,max_length:500,min_size:15});
			div.appendChild(text.getHTMLElement());
			text.onchange.add_listener(function() { belonging.description = text.getCurrentData(); });
			var remove = document.createElement("BUTTON");
			remove.className = "flat small_icon";
			remove.style.verticalAlign = "middle";
			remove.style.marginLeft = "3px";
			remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
			remove.title = "Remove this item";
			div.appendChild(remove);
			section.content.insertBefore(div, add_button);
			remove.onclick = function() {
				t.list.removeUnique(belonging);
				section.content.removeChild(div);
			};
		};
		for (var i = 0; i < list.length; ++i) this.createRow(list[i]);
		this.save = function(ondone) {
			var locker = lock_screen(null, "Saving Belongings...");
			service.json("selection","si/save_belongings",{applicant:applicant_id,belongings:t.list},function(res) {
				if (res) {
					for (var i = 0; i < res.length; ++i)
						for (var j = 0; j < t.list.length; ++j)
							if (res[i].given_id == t.list[j].id) { t.list[j].id = res[i].new_id; break; }
				}
				unlock_screen(locker);
				ondone();
			});
		};
	} else {
		for (var i = 0; i < list.length; ++i) {
			var div = document.createElement("DIV");
			div.appendChild(document.createTextNode(list[i].description));
			section.content.appendChild(div);
		}
	}
}
