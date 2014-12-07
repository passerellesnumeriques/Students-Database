<?php 
class page_si_applicant extends Page {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function execute() {
		$people_id = $_GET["people"];
		
		$can_edit = PNApplication::$instance->user_management->has_right("edit_social_investigation");
		$edit = $can_edit && @$_GET["edit"] == "true"; 

		$family = PNApplication::$instance->family->getFamily($people_id, "Child");
		$houses = SQLQuery::create()->select("SIHouse")->whereValue("SIHouse","applicant",$people_id)->execute();
		
		$this->requireJavascript("section.js");
		$this->requireJavascript("family.js");
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_integer.js");
?>
<div class='page_title'>
	Social Investigation Data
	<?php if ($can_edit) {
		if ($edit) {?>
			<button class='action' onclick='save();'><img src='<?php echo theme::$icons_16["save"];?>'/> Save</button>
			<button class='action' onclick='cancelEdit();'><img src='<?php echo theme::$icons_16["no_edit"];?>'/> Cancel modifications</button>
		<?php } else {?>
			<button class='action' onclick='edit();'><img src='<?php echo theme::$icons_16["edit"];?>'/> Edit data</button>
		<?php }
	}?>
</div>
<div id='section_family' title="Family" icon="/static/family/family_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
	<div id='family_container'></div>
</div>
<div id='section_houses' title="Houses" icon="/static/selection/si/house_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
</div>
<style type='text/css'>
.si_house {
	border: 1px solid #8080A0;
	border-radius: 5px;
	margin: 3px;
}
.si_house>.header {
	font-size: 12pt;
	background-color: #D0D0E8;
	border-bottom: 1px solid #808080;
	padding: 2px; 5px;
	display: flex;
	flex-direction: row;
	align-items: center;
	border-top-left-radius: 5px;
	border-top-right-radius: 5px;
}
.si_house>.content {
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
	border-collapse: collapse;
	border-spacing: 0px;
}
.si_house>.content th {
	background-color: #C0C0C0;
	border-right: 1px solid #808080;
	border-bottom: 1px solid #808080;
	font-weight: bold;
	text-align: right;
	vertical-align: top;
	padding: 2px 1px;
}
.si_house>.content th>img {
	vertical-align: bottom;
}
.si_house>.content td {
	background-color: white;
	text-align: left;
	border-bottom: 1px solid #808080;
	padding: 2px 1px;
}
.si_house>.content td, .si_house>.content td>input, .si_house>.content td>select, .si_house>.content td>textarea {
	vertical-align: top;
}
.si_house>.content tr:last-child th, .si_house>.content tr:last-child td {
	border-bottom: none;
}
</style>
<script type='text/javascript'>
var can_edit = <?php echo $edit ? "true" : "false";?>;
	
sectionFromHTML('section_family');
var fam = new family(document.getElementById('family_container'), <?php echo json_encode($family[0]);?>, <?php echo json_encode($family[1]);?>, <?php echo $people_id;?>, <?php echo $edit ? "true" : "false";?>);

function multiple_choice_other(container, choices, value, can_edit, onchange) {
	var values = value ? value.split(",") : [];
	this.element = document.createElement("DIV");
	container.appendChild(this.element);
	if (can_edit) {
		var checkboxes = [];
		var input;
		var changed = function() {
			var s = "";
			for (var i = 0; i < checkboxes.length; ++i) {
				if (!checkboxes[i].checked) continue;
				if (s != "") s += ", ";
				s += checkboxes[i]._value;
			}
			var o = input.value.trim();
			if (o.length > 0) {
				if (s.length > 0) s += ", ";
				s += o;
			}
			onchange(s);
		};
		for (var i = 0; i < choices.length; i+=2) {
			var div = document.createElement("DIV");
			this.element.appendChild(div);
			var cb = document.createElement("INPUT");
			cb.type = "checkbox";
			div.appendChild(cb);
			cb.style.verticalAlign = "middle";
			cb.style.marginBottom = "4px";
			cb.style.marginRight = "3px";
			div.appendChild(document.createTextNode(choices[i]));
			for (var j = 0; j < values.length; ++j)
				if (values[j].isSame(choices[i].trim())) {
					values.splice(j,1);
					cb.checked = "checked";
					break;
				}
			cb._value = choices[i];
			cb.onchange = changed;
			checkboxes.push(cb);
			if (choices[i+1]) {
				var help = document.createElement("IMG");
				help.style.verticalAlign = "middle";
				help.style.marginLeft = "5px";
				help.src = theme.icons_16.help;
				div.appendChild(help);
				tooltip(help, "<img src='/static/selection/si/"+choices[i+1]+"'/>");
			}
		}
		var div = document.createElement("DIV");
		this.element.appendChild(div);
		div.appendChild(document.createTextNode("Other: "));
		div.appendChild(input = document.createElement("INPUT"));
		input.type = "text";
		input.value = "";
		for (var i = 0; i < values.length; ++i) {
			if (input.value != "") input.value += ", ";
			input.value += values[i];
		}
		input.onchange = changed;
	} else {
		var first = true;
		for (var i = 0; i < choices.length; i+=2) {
			var found = false;
			for (var j = 0; j < values.length; ++j)
				if (values[j].isSame(choices[i].trim())) {
					values.splice(j,1);
					found = true;
					break;
				}
			if (!found) continue;
			if (first) first = false;
			else this.element.appendChild(document.createTextNode(", "));
			this.element.appendChild(document.createTextNode(choices[i]));
			if (choices[i+1]) {
				var help = document.createElement("IMG");
				help.style.verticalAlign = "middle";
				help.style.marginLeft = "5px";
				help.src = theme.icons_16.help;
				this.element.appendChild(help);
				tooltip(help, "<img src='/static/selection/si/"+choices[i+1]+"'/>");
			}
		}
		for (var i = 0; i < values.length; ++i) {
			if (first) first = false;
			else this.element.appendChild(document.createTextNode(", "));
			this.element.appendChild(document.createTextNode(values[i]));
		}
	}
}

var section_houses = sectionFromHTML('section_houses');
function houses(section, houses, can_edit) {
	section.content.style.padding = "3px";
	this.houses = houses;
	this.houses_controls = [];
	for (var i = 0; i < houses.length; ++i)
		this.houses_controls.push(new house(section.content, houses[i], can_edit));
	if (can_edit) {
		var add_house = document.createElement("BUTTON");
		add_house.className = "flat icon";
		add_house.innerHTML = "<img src='"+theme.build_icon("/static/selection/si/house_16.png",theme.icons_10.add)+"'/>";
		add_house.title = "Add a house";
		var t=this;
		add_house.onclick = function() {
			var h = {
				house_status: null,
				house_cost: null,
				house_status_comment: null,
				lot_status: null,
				lot_cost: null,
				lot_status_comment: null,
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
			t.houses_controls.push(new house(section.content, h, can_edit)); 
		};
		section.addToolRight(add_house);
	}
}
function house(container, house, can_edit) {
	this.element = document.createElement("DIV");
	container.appendChild(this.element);
	this.element.className = "si_house";
	this.header = document.createElement("DIV");
	this.element.appendChild(this.header);
	this.header.className = "header";
	this.header.innerHTML = "<div style='flex:1 1 auto'>House Description</div><img src='/static/selection/si/house_32.png' style='flex:none;margin-left:10px;'/>";
	this.content = document.createElement("TABLE");
	this.element.appendChild(this.content);
	this.content.className = "content";
	var tr, td;
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
		this.house_status_cost.onchange.add_listener(function(f) { house.house_cost = f.getCurrentData(); });
		td.appendChild(document.createTextNode(" Comment: "));
		this.house_status_comment = document.createElement("TEXTAREA");
		this.house_status_comment.value = house.house_status_comment;
		td.appendChild(this.house_status_comment);
		this.house_status_comment.onchange = function() { house.house_status_comment = this.value; };
	} else {
		// TODO
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
		this.lot_status_cost.onchange.add_listener(function(f) { house.lot_cost = f.getCurrentData(); });
		td.appendChild(document.createTextNode(" Comment: "));
		this.lot_status_comment = document.createElement("TEXTAREA");
		this.lot_status_comment.value = house.lot_status_comment;
		td.appendChild(this.lot_status_comment);
		this.lot_status_comment.onchange = function() { house.lot_status_comment = this.value; };
	} else {
		// TODO
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
	if (can_edit) {
		// TODO
	} else {
		// TODO
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
	if (can_edit) {
		// TODO
	} else {
		// TODO
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
	if (can_edit) {
		// TODO
	} else {
		// TODO
	}
	// general comment
	this.content.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TH"));
	td.innerHTML = "General comment";
	tr.appendChild(td = document.createElement("TD"));
	td.innerHTML = "TODO";
}
new houses(section_houses, [<?php
$first = true;
foreach ($houses as $house) {
	if ($first) $first = false; else echo ",";
	echo json_encode($house);
}
?>],can_edit);

function edit() {
	location.href = "?people=<?php echo $people_id;?>&edit=true";
}
function cancelEdit() {
	location.href = "?people=<?php echo $people_id;?>&edit=false";
}
function save() {
	alert("TODO");
}
</script>
<?php 
	}
	
}
?>