function houses(section, houses, applicant_id, can_edit) {
	var t=this;
	section.content.style.padding = "3px";
	this.houses = houses;
	this.houses_controls = [];
	for (var i = 0; i < houses.length; ++i)
		this.houses_controls.push(new house(section.content, houses[i], can_edit, function(house) { t.removeHouse(house); }));
	if (can_edit) {
		var add_house = document.createElement("BUTTON");
		add_house.className = "flat icon";
		add_house.innerHTML = "<img src='"+theme.build_icon("/static/selection/si/house_16.png",theme.icons_10.add)+"'/>";
		add_house.title = "Add a house";
		var t=this;
		add_house.onclick = function() {
			var h = {
				id: -1,
				house_status: null,
				house_cost: null,
				house_comment: null,
				lot_status: null,
				lot_cost: null,
				lot_comment: null,
				roof_type: null,
				roof_condition: null,
				roof_comment: null,
				walls_type: null,
				walls_condition: null,
				walls_comment: null,
				floor_type: null,
				floor_condition: null,
				floor_comment: null,
				general_comment: null
			};
			t.houses.push(h);
			t.houses_controls.push(new house(section.content, h, can_edit, function(house) { t.removeHouse(house); })); 
		};
		section.addToolRight(add_house);
		this.removeHouse = function(house_control) {
			this.houses.remove(house_control.house_info);
			this.houses_controls.remove(house_control);
			section.content.removeChild(house_control.element);
			layout.changed(section.content);
		};
		this.save = function(ondone) {
			var locker = lock_screen(null, "Saving Houses Information...");
			var t=this;
			service.json("selection","si/save_houses",{houses:this.houses,applicant:applicant_id},function(res) {
				unlock_screen(locker);
				if (res) {
					for (var i = 0; i < t.houses.length; ++i)
						t.houses[i].id = res[i];
				}
				ondone();
			});
		};
	}
}
function house(container, house, can_edit, onremove) {
	this.house_info = house;
	this.element = document.createElement("DIV");
	container.appendChild(this.element);
	this.element.className = "si_house";
	this.header = document.createElement("DIV");
	this.element.appendChild(this.header);
	this.header.className = "header";
	this.header.innerHTML = "<div style='flex:1 1 auto'>House Description</div><img src='/static/selection/si/house_32.png' style='flex:none;margin-left:10px;'/>";
	if (can_edit) {
		var remove = document.createElement("BUTTON");
		remove.className = "flat small_icon";
		remove.style.selfAlign = "flex-start";
		remove.style.marginLeft = "2px";
		remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
		remove.title = "Remove this house";
		var t=this;
		remove.onclick = function() {
			onremove(t);
		};
		this.header.appendChild(remove);
	}
	this.content = document.createElement("TABLE");
	this.element.appendChild(this.content);
	this.content.className = "content";
	var tr, td, div;
	var createOption = function(select, value, text, selected) {
		if (!text) text = value;
		var o = document.createElement("OPTION");
		o.value = value;
		o.text = text;
		if (selected) o.selected = 'selected';
		select.add(o);
	};
	// house status
	this.content.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TH"));
	td.innerHTML = "<img src='/static/selection/si/house_16.png'> House Status";
	tr.appendChild(td = document.createElement("TD"));
	if (can_edit) {
		this.house_status_select = document.createElement("SELECT");
		td.appendChild(this.house_status_select);
		createOption(this.house_status_select, "");
		createOption(this.house_status_select, "Owned", null, house.house_status == "Owned");
		createOption(this.house_status_select, "Rented", null, house.house_status == "Rented");
		createOption(this.house_status_select, "Lended", null, house.house_status == "Lended");
		this.house_status_select.onchange = function() { house.house_status = this.value == "" ? null : this.value; }
		td.appendChild(document.createTextNode(" Cost ? "));
		this.house_status_cost = new field_integer(house.house_cost,true,{can_be_null:true,min:0});
		td.appendChild(this.house_status_cost.getHTMLElement());
		this.house_status_cost.onchange.addListener(function(f) { house.house_cost = f.getCurrentData(); });
		td.appendChild(document.createTextNode(" Comment: "));
		this.house_comment = new field_text(house.house_comment, true, {can_be_null:true,max_length:200});
		td.appendChild(this.house_comment.getHTMLElement());
		this.house_comment.onchange.addListener(function(f) { house.house_comment = f.getCurrentData(); });
	} else {
		var s = house.house_status ? house.house_status : "";
		if (house.house_cost) {
			if (s.length > 0) s += ", ";
			s += "Cost: "+house.house_cost;
		}
		if (house.house_comment) {
			if (s.length > 0) s += ", ";
			s += "Comment: ";
		}
		td.appendChild(document.createTextNode(s));
		if (house.house_comment) td.appendChild(document.createTextNode(house.house_comment));
	}
	// lot status
	this.content.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TH"));
	td.innerHTML = "Lot Status";
	tr.appendChild(td = document.createElement("TD"));
	if (can_edit) {
		this.lot_status_select = document.createElement("SELECT");
		td.appendChild(this.lot_status_select);
		createOption(this.lot_status_select, "");
		createOption(this.lot_status_select, "Owned", null, house.lot_status == "Owned");
		createOption(this.lot_status_select, "Rented", null, house.lot_status == "Rented");
		createOption(this.lot_status_select, "Lended", null, house.lot_status == "Lended");
		this.lot_status_select.onchange = function() { house.lot_status = this.value == "" ? null : this.value; }
		td.appendChild(document.createTextNode(" Cost ? "));
		this.lot_status_cost = new field_integer(house.lot_cost,true,{can_be_null:true,min:0});
		td.appendChild(this.lot_status_cost.getHTMLElement());
		this.lot_status_cost.onchange.addListener(function(f) { house.lot_cost = f.getCurrentData(); });
		td.appendChild(document.createTextNode(" Comment: "));
		this.lot_comment = new field_text(house.lot_comment, true, {can_be_null:true,max_length:200});
		td.appendChild(this.lot_comment.getHTMLElement());
		this.lot_comment.onchange.addListener(function(f) { house.lot_comment = f.getCurrentData(); });
	} else {
		var s = house.lot_status ? house.lot_status : "";
		if (house.lot_cost) {
			if (s.length > 0) s += ", ";
			s += "Cost: "+house.lot_cost;
		}
		if (house.lot_comment) {
			if (s.length > 0) s += ", ";
			s += "Comment: ";
		}
		td.appendChild(document.createTextNode(s));
		if (house.lot_comment) td.appendChild(document.createTextNode(house.lot_comment));
	}
	// roof
	this.content.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TH"));
	td.innerHTML = "<img src='/static/selection/si/roof_16.png'/> Roof";
	tr.appendChild(td = document.createElement("TD"));
	this.roof_type = new multiple_choice_other(td, [
		"Galvanized Iron Sheets", "galvanized_iron_sheets.jpg",
		"Thatch", "thatch.jpg",
		"Tarpaulin", "tarpaulin.jpg",
		"Plastic", null,
		"Wood", null
	], house.roof_type, can_edit, function(type) { house.roof_type = type; });
	this.roof_type.element.style.display = "inline-block";
	div = document.createElement("DIV");
	td.appendChild(div);
	div.style.display = "inline-block";
	div.style.verticalAlign = "top";
	div.style.marginLeft = "5px";
	div.appendChild(document.createTextNode("Condition: "));
	if (can_edit) {
		var select = document.createElement("SELECT");
		div.appendChild(select);
		createOption(select, "");
		createOption(select, "1 (Bad)", null, house.roof_condition == "1 (Bad)");
		createOption(select, "2", null, house.roof_condition == "2");
		createOption(select, "3", null, house.roof_condition == "3");
		createOption(select, "4", null, house.roof_condition == "4");
		createOption(select, "5 (Good)", null, house.roof_condition == "5 (Good)");
		select.onchange = function() { house.roof_condition = this.value; };
		div.appendChild(document.createElement("BR"));
		div.appendChild(document.createTextNode("Comment:"));
		div.appendChild(document.createElement("BR"));
		this.roof_comment = new field_text(house.roof_comment, true, {can_be_null:true,max_length:200});
		div.appendChild(this.roof_comment.getHTMLElement());
		this.roof_comment.onchange.addListener(function(f) { house.roof_comment = f.getCurrentData(); });
	} else {
		div.appendChild(document.createTextNode(house.roof_condition ? house.roof_condition : "Unknown"));
		if (house.roof_comment) div.appendChild(document.createTextNode(", Comment: "+house.roof_comment));
	}
	// walls
	this.content.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TH"));
	td.innerHTML = "<img src='/static/selection/si/wall_16.png'/> Walls";
	tr.appendChild(td = document.createElement("TD"));
	this.walls_type = new multiple_choice_other(td, [
		"Concrete", null,
		"Tarpaulin", "tarpaulin.jpg",
		"Bamboo", null,
		"Plastic", null,
		"Wood", null
	], house.walls_type, can_edit, function(type) { house.walls_type = type; });
	this.walls_type.element.style.display = "inline-block";
	div = document.createElement("DIV");
	td.appendChild(div);
	div.style.display = "inline-block";
	div.style.verticalAlign = "top";
	div.style.marginLeft = "5px";
	div.appendChild(document.createTextNode("Condition: "));
	if (can_edit) {
		var select = document.createElement("SELECT");
		div.appendChild(select);
		createOption(select, "");
		createOption(select, "1 (Bad)", null, house.walls_condition == "1 (Bad)");
		createOption(select, "2", null, house.walls_condition == "2");
		createOption(select, "3", null, house.walls_condition == "3");
		createOption(select, "4", null, house.walls_condition == "4");
		createOption(select, "5 (Good)", null, house.walls_condition == "5 (Good)");
		select.onchange = function() { house.walls_condition = this.value; };
		div.appendChild(document.createElement("BR"));
		div.appendChild(document.createTextNode("Comment:"));
		div.appendChild(document.createElement("BR"));
		this.walls_comment = new field_text(house.walls_comment, true, {can_be_null:true,max_length:200});
		div.appendChild(this.walls_comment.getHTMLElement());
		this.walls_comment.onchange.addListener(function(f) { house.walls_comment = f.getCurrentData(); });
	} else {
		div.appendChild(document.createTextNode(house.walls_condition ? house.walls_condition : "Unknown"));
		if (house.walls_comment) div.appendChild(document.createTextNode(", Comment: "+house.walls_comment));
	}
	// floor
	this.content.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TH"));
	td.innerHTML = "<img src='/static/selection/si/floor_16.png'/> Floor";
	tr.appendChild(td = document.createElement("TD"));
	this.floor_type = new multiple_choice_other(td, [
		"Concrete", null,
		"Wood", null,
		"Bamboo", null,
		"Tiles", null,
		"Sand", null,
		"Soil", null
	], house.floor_type, can_edit, function(type) { house.floor_type = type; });
	this.floor_type.element.style.display = "inline-block";
	div = document.createElement("DIV");
	td.appendChild(div);
	div.style.display = "inline-block";
	div.style.verticalAlign = "top";
	div.style.marginLeft = "5px";
	div.appendChild(document.createTextNode("Condition: "));
	if (can_edit) {
		var select = document.createElement("SELECT");
		div.appendChild(select);
		createOption(select, "");
		createOption(select, "1 (Bad)", null, house.floor_condition == "1 (Bad)");
		createOption(select, "2", null, house.floor_condition == "2");
		createOption(select, "3", null, house.floor_condition == "3");
		createOption(select, "4", null, house.floor_condition == "4");
		createOption(select, "5 (Good)", null, house.floor_condition == "5 (Good)");
		select.onchange = function() { house.floor_condition = this.value; };
		div.appendChild(document.createElement("BR"));
		div.appendChild(document.createTextNode("Comment:"));
		div.appendChild(document.createElement("BR"));
		this.floor_comment = new field_text(house.floor_comment, true, {can_be_null:true,max_length:200});
		div.appendChild(this.floor_comment.getHTMLElement());
		this.floor_comment.onchange.addListener(function(f) { house.floor_comment = f.getCurrentData(); });
	} else {
		div.appendChild(document.createTextNode(house.floor_condition ? house.floor_condition : "Unknown"));
		if (house.floor_comment) div.appendChild(document.createTextNode(", Comment: "+house.floor_comment));
	}
	// general comment
	this.content.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TH"));
	td.innerHTML = "General comment";
	tr.appendChild(td = document.createElement("TD"));
	if (can_edit) {
		this.general_comment = document.createElement("TEXTAREA");
		td.appendChild(this.general_comment);
		this.general_comment.value = house.general_comment ? house.general_comment : "";
		this.general_comment.onchange = function() { house.general_comment = this.value; };
	} else {
		td.appendChild(document.createTextNode(house.general_comment));
	}
}
