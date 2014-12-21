function farm(container, info, productions, applicant_id, can_edit) {
	container.style.padding = "3px";
	this.info = info;
	this.productions = productions;
	var t=this;
	this._initNoFarm = function() {
		container.removeAllChildren();
		var div = document.createElement("DIV");
		var span = document.createElement("SPAN");
		span.style.fontStyle = "italic";
		span.appendChild(document.createTextNode("No Farm"));
		div.appendChild(span);
		container.appendChild(div);
		if (can_edit) {
			var button = document.createElement("BUTTON");
			button.className = "action";
			button.style.marginLeft = "5px";
			button.innerHTML = "Create";
			div.appendChild(button);
			button.onclick = function() {
				t.info = {
					land_size: null,
					land_status: null,
					land_cost: null,
					land_comment: null,
					income: null,
					income_freq: null,
					comment: null
				};
				t.productions = [];
				t._initFarm();
			};
		}
	};
	this._initFarm = function() {
		container.removeAllChildren();
		// Land
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.innerHTML = "<b>Land:</b> ";
		if (can_edit) {
			div.appendChild(document.createTextNode("Size: "));
			var land_size = new field_decimal(this.info.land_size, true, {can_be_null:true,integer_digits:10,decimal_digits:2,min:0});
			div.appendChild(land_size.getHTMLElement());
			land_size.onchange.addListener(function() { t.info.land_size = land_size.getCurrentData(); });
		} else if (this.info.land_size > 0)
			div.appendChild(document.createTextNode("Size: "+this.info.land_size));
		if (can_edit) {
			div.appendChild(document.createTextNode(" Status: "));
			var land_status = new field_enum(this.info.land_status, true, {can_be_null:true,possible_values:["Owned","Rented","Lended"]});
			div.appendChild(land_status.getHTMLElement());
			land_status.onchange.addListener(function() { t.info.land_status = land_status.getCurrentData(); });
		} else if (this.info.land_status)
			div.appendChild(document.createTextNode(" Status: "+this.info.land_status));
		if (can_edit) {
			div.appendChild(document.createTextNode(" Cost ? "));
			var land_cost = new field_integer(this.info.land_cost, true, {can_be_null:true,min:0});
			div.appendChild(land_cost.getHTMLElement());
			land_cost.onchange.addListener(function() { t.info.land_cost = land_cost.getCurrentData(); });
		} else if (this.info.land_cost > 0)
			div.appendChild(document.createTextNode(" Cost: "+this.info.land_cost));
		if (can_edit) {
			div.appendChild(document.createTextNode(" Comment: "));
			var land_comment = new field_text(this.info.land_comment, true, {can_be_null:true,max_length:200});
			div.appendChild(land_comment.getHTMLElement());
			land_comment.onchange.addListener(function() { t.info.land_comment = land_comment.getCurrentData(); });
		} else if (this.info.land_comment)
			div.appendChild(document.createTextNode(" Comment: "+this.info.land_comment));
		
		var table = document.createElement("TABLE");
		container.appendChild(table);
		var tr, td;
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TH"));
		td.colSpan = 5;
		td.innerHTML = "<img src='/static/selection/si/animal_16.png'/> Animals";
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Type";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Number";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Income";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Frequency";
		tr.appendChild(td = document.createElement("TH"));
		td.innerHTML = "Comment";

		// Animals
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TH"));
		td.colSpan = 5;
		td.style.textAlign = "left";
		var tr_end_animals = tr;
		var add_animal = document.createElement("BUTTON");
		add_animal.className = "flat small_icon";
		add_animal.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
		add_animal.title = "Add another type of animal";
		if (can_edit) td.appendChild(add_animal);
		var addAnimal = function(animal, is_default) {
			if (animal.id < 0) t.productions.push(animal);
			var tr = document.createElement("TR");
			table.insertBefore(tr, tr_end_animals);
			var td;
			tr.appendChild(td = document.createElement("TD"));
			if (is_default || !can_edit) {
				td.appendChild(document.createTextNode(animal.name));
			} else {
				var name = new field_text(animal.name,true,{can_be_null:false,max_length:50,min_length:1});
				td.appendChild(name.getHTMLElement());
				name.onchange.addListener(function() { animal.name = name.getCurrentData(); });
			}
			var remove = document.createElement("BUTTON");
			remove.className = "flat small_icon";
			remove.style.marginLeft = "3px";
			remove.style.verticalAlign = "middle";
			remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
			remove.onclick = function() {
				t.productions.remove(animal);
				table.removeChild(tr);
			};
			if (can_edit) td.appendChild(remove);
			tr.appendChild(td = document.createElement("TD"));
			if (can_edit) {
				var nb = new field_integer(animal.nb,true,{can_be_null:false,min:1});
				td.appendChild(nb.getHTMLElement());
				nb.onchange.addListener(function() { animal.nb = nb.getCurrentData(); });
			} else if (animal.nb != null)
				td.appendChild(document.createTextNode(animal.nb));
			tr.appendChild(td = document.createElement("TD"));
			if (can_edit) {
				var income = new field_decimal(animal.income,true,{can_be_null:true,integer_digits:10,decimal_digits:2,min:0});
				td.appendChild(income.getHTMLElement());
				income.onchange.addListener(function() { animal.income = income.getCurrentData(); });
			} else if (animal.income != null)
				td.appendChild(document.createTextNode(animal.income));
			tr.appendChild(td = document.createElement("TD"));
			if (can_edit) {
				var income_freq = new field_text(animal.income_freq,true,{can_be_null:true,max_length:25});
				td.appendChild(income_freq.getHTMLElement());
				income_freq.onchange.addListener(function() { animal.income_freq = income_freq.getCurrentData(); });
			} else if (animal.income_freq != null)
				td.appendChild(document.createTextNode(animal.income_freq));
			tr.appendChild(td = document.createElement("TD"));
			if (can_edit) {
				var comment = new field_text(animal.comment,true,{can_be_null:true,max_length:200});
				td.appendChild(comment.getHTMLElement());
				comment.onchange.addListener(function() { animal.comment = comment.getCurrentData(); });
			} else if (animal.comment != null)
				td.appendChild(document.createTextNode(animal.comment));
		};
		var animal_id_counter = -1;
		add_animal.onclick = function() {
			addAnimal({id:animal_id_counter--,type:"Animal",name:null,nb:null,income:null,income_freq:null,comment:null},false);
		};
		if (can_edit) {
			var pig = {id:animal_id_counter--,type:"Animal",name:"Pig",nb:null,income:null,income_freq:null,comment:null};
			var chicken = {id:animal_id_counter--,type:"Animal",name:"Chicken",nb:null,income:null,income_freq:null,comment:null};
			var cow = {id:animal_id_counter--,type:"Animal",name:"Cow",nb:null,income:null,income_freq:null,comment:null};
			var others = [];
			for (var i = 0; i < this.productions.length; ++i) {
				if (this.productions[i].type != "Animal") continue;
				if (this.productions[i].name.isSame("Pig")) pig = this.productions[i];
				else if (this.productions[i].name.isSame("Chicken")) chicken = this.productions[i];
				else if (this.productions[i].name.isSame("Cow")) cow = this.productions[i];
				else others.push(this.productions[i]);
			}
			addAnimal(pig,true);
			addAnimal(chicken,true);
			addAnimal(cow,true);
			for (var i = 0; i < others.length; ++i)
				addAnimal(others[i], false);
		} else for (var i = 0; i < this.productions.length; ++i)
			if (this.productions[i].type == "Animal")
				addAnimal(this.productions[i],false);

		// Plants
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TH"));
		td.colSpan = 5;
		td.innerHTML = "<img src='/static/selection/si/plant_16.png'/> Plants";
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TH"));
		td.colSpan = 5;
		td.style.textAlign = "left";
		var tr_end_plants = tr;
		var add_plant = document.createElement("BUTTON");
		add_plant.className = "flat small_icon";
		add_plant.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
		add_plant.title = "Add another type of plant";
		if (can_edit) td.appendChild(add_plant);
		var addPlant = function(plant, is_default) {
			if (plant.id < 0) t.productions.push(plant);
			var tr = document.createElement("TR");
			table.insertBefore(tr, tr_end_plants);
			var td;
			tr.appendChild(td = document.createElement("TD"));
			if (is_default || !can_edit) {
				td.appendChild(document.createTextNode(plant.name));
			} else {
				var name = new field_text(plant.name,true,{can_be_null:false,max_length:50,min_length:1});
				td.appendChild(name.getHTMLElement());
				name.onchange.addListener(function() { plant.name = name.getCurrentData(); });
			}
			var remove = document.createElement("BUTTON");
			remove.className = "flat small_icon";
			remove.style.marginLeft = "3px";
			remove.style.verticalAlign = "middle";
			remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
			remove.onclick = function() {
				t.productions.remove(plant);
				table.removeChild(tr);
			};
			if (can_edit) td.appendChild(remove);
			tr.appendChild(td = document.createElement("TD"));
			if (can_edit) {
				var nb = new field_integer(plant.nb,true,{can_be_null:false,min:1});
				td.appendChild(nb.getHTMLElement());
				nb.onchange.addListener(function() { plant.nb = nb.getCurrentData(); });
			} else if (plant.nb != null)
				td.appendChild(document.createTextNode(plant.nb));
			tr.appendChild(td = document.createElement("TD"));
			if (can_edit) {
				var income = new field_decimal(plant.income,true,{can_be_null:true,integer_digits:10,decimal_digits:2,min:0});
				td.appendChild(income.getHTMLElement());
				income.onchange.addListener(function() { plant.income = income.getCurrentData(); });
			} else if (plant.income != null)
				td.appendChild(document.createTextNode(plant.income));
			tr.appendChild(td = document.createElement("TD"));
			if (can_edit) {
				var income_freq = new field_text(plant.income_freq,true,{can_be_null:true,max_length:25});
				td.appendChild(income_freq.getHTMLElement());
				income_freq.onchange.addListener(function() { plant.income_freq = income_freq.getCurrentData(); });
			} else if (plant.income_freq != null)
				td.appendChild(document.createTextNode(plant.income_freq));
			tr.appendChild(td = document.createElement("TD"));
			if (can_edit) {
				var comment = new field_text(plant.comment,true,{can_be_null:true,max_length:200});
				td.appendChild(comment.getHTMLElement());
				comment.onchange.addListener(function() { plant.comment = comment.getCurrentData(); });
			} else if (plant.comment != null)
				td.appendChild(document.createTextNode(plant.comment));
		};
		var plant_id_counter = -1;
		add_plant.onclick = function() {
			addPlant({id:plant_id_counter--,type:"Plant",name:null,nb:null,income:null,income_freq:null,comment:null},false);
		};
		for (var i = 0; i < this.productions.length; ++i)
			if (this.productions[i].type == "Plant")
				addPlant(this.productions[i],false);
		
		// General comment
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.innerHTML = "<b>General Comment:</b><br/>";
		if (can_edit) {
			var comment = document.createElement("TEXTAREA");
			if (this.info.comment) comment.value = this.info.comment;
			comment.onchange = function() { t.info.comment = this.value; };
			div.appendChild(comment);
		} else if (this.info.comment)
			div.appendChild(document.createTextNode(this.info.comment));
		
		if (can_edit) {
			div = document.createElement("DIV");
			container.appendChild(div);
			var remove = document.createElement("BUTTON");
			remove.className = "action red";
			remove.innerHTML = "Remove Farm Information";
			div.appendChild(remove);
			remove.onclick = function() {
				t.info = null;
				t.productions = [];
				t._initNoFarm();
			};
		}
	};
	this.save = function(ondone) {
		var locker = lock_screen(null,"Saving Farm...");
		service.json("selection","si/save_farm",{applicant:applicant_id,farm:this.info,productions:this.productions},function(res) {
			if (res && res.length > 0) {
				for (var i = 0; i < res.length; ++i)
					for (var j = 0; j < t.productions.length; ++j)
						if (t.productions[j].id == res[i].given_id) { t.productions[j].id = res[i].new_id; break; }
			}
			unlock_screen(locker);
			ondone();
		});
	};
	if (!info) this._initNoFarm();
	else this._initFarm();
}
