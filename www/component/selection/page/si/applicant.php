<?php 
class page_si_applicant extends Page {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function execute() {
		$people_id = $_GET["people"];
		
		$can_edit = PNApplication::$instance->user_management->has_right("edit_social_investigation");
		$edit = $can_edit && @$_GET["edit"] == "true"; 

		$family = PNApplication::$instance->family->getFamily($people_id, "Child");
		$houses = SQLQuery::create()->select("SIHouse")->whereValue("SIHouse","applicant",$people_id)->execute();
		$farm = SQLQuery::create()->select("SIFarm")->whereValue("SIFarm", "applicant", $people_id)->executeSingleRow();
		$farm_prod = SQLQuery::create()->select("SIFarmProduction")->whereValue("SIFarmProduction","applicant",$people_id)->execute();
		$fishing = SQLQuery::create()->select("SIFishing")->whereValue("SIFishing","applicant",$people_id)->executeSingleRow();
		
		$this->requireJavascript("section.js");
		$this->requireJavascript("family.js");
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_integer.js");
		$this->requireJavascript("field_decimal.js");
		$this->requireJavascript("field_enum.js");
		$this->requireJavascript("field_text.js");
		$this->requireJavascript("si_houses.js");
		$this->requireJavascript("si_farm.js");
		$this->requireJavascript("multiple_choice_other.js");
		$this->addStylesheet("/static/selection/si/si_houses.css");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div style='flex:1 1 auto;overflow:auto;'>
		<div id='section_family' title="Family" icon="/static/family/family_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
			<div id='family_container'></div>
		</div>
		<div id='section_houses' title="Houses" icon="/static/selection/si/house_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
		</div>
		<div id='section_farm' title="Farm" icon="/static/selection/si/farm_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
		</div>
		<div id='section_fishing' title="Fishing" icon="/static/selection/si/fish_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
		</div>
		<div id='section_goods' title="Goods/Belongings" icon="/static/selection/si/tv_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
		</div>
	</div>
	<div class='page_footer' style='flex:none'>
		<?php if ($can_edit) {
			if ($edit) {?>
				<button class='action' onclick='save();'><img src='<?php echo theme::$icons_16["save"];?>'/> Save</button>
				<button class='action' onclick='cancelEdit();'><img src='<?php echo theme::$icons_16["no_edit"];?>'/> Cancel modifications / Stop editing</button>
			<?php } else {?>
				<button class='action' onclick='edit();'><img src='<?php echo theme::$icons_16["edit"];?>'/> Edit data</button>
			<?php }
		}?>
	</div>
</div>
<script type='text/javascript'>
var can_edit = <?php echo $edit ? "true" : "false";?>;
	
var section_family = sectionFromHTML('section_family');
var fam = new family(section_family.content, <?php echo json_encode($family[0]);?>, <?php echo json_encode($family[1]);?>, <?php echo $people_id;?>, <?php echo $edit ? "true" : "false";?>);

var section_houses = sectionFromHTML('section_houses');
var applicant_houses = new houses(section_houses, [<?php
$first = true;
foreach ($houses as $house) {
	if ($first) $first = false; else echo ",";
	echo json_encode($house);
}
?>],<?php echo $people_id;?>,can_edit);

var section_farm = sectionFromHTML('section_farm');
var applicant_farm = new farm(section_farm.content, <?php echo json_encode($farm);?>, <?php echo json_encode($farm_prod);?>, <?php echo $people_id;?>, <?php echo $edit ? "true" : "false";?>);

var section_fishing = sectionFromHTML('section_fishing');
function fishing(container, info, applicant_id, can_edit) {
	container.style.padding = "3px";
	this.info = info;
	var t=this;
	this._initNoInfo = function() {
		container.removeAllChildren();
		var div = document.createElement("DIV");
		var span = document.createElement("SPAN");
		span.style.fontStyle = "italic";
		span.appendChild(document.createTextNode("Not Fishing"));
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
					boat: null,
					net: null,
					income: null,
					income_freq: null,
					other: null
				};
				t._initFishing();
			};
		}
	};
	this._initFishing = function() {
		container.removeAllChildren();
		// Boat
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.innerHTML = "<img src='/static/selection/si/boat_16.png' style='vertical-align:bottom'/> <b>Boat:</b> ";
		if (can_edit) {
			var boat = new field_text(this.info.boat, true, {can_be_null:true,max_length:250});
			div.appendChild(boat.getHTMLElement());
			boat.onchange.add_listener(function() { t.info.boat = boat.getCurrentData(); });
		} else if (this.info.boat)
			div.appendChild(document.createTextNode(this.info.boat));
		// Net
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.innerHTML = "<img src='/static/selection/si/fish_net.png' style='vertical-align:bottom'/> <b>Net:</b> ";
		if (can_edit) {
			var net = new field_text(this.info.net, true, {can_be_null:true,max_length:250});
			div.appendChild(net.getHTMLElement());
			net.onchange.add_listener(function() { t.info.net = net.getCurrentData(); });
		} else if (this.info.net)
			div.appendChild(document.createTextNode(this.info.net));
		// Income
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.innerHTML = "<b>Income:</b> ";
		if (can_edit) {
			var income = new field_integer(this.info.income, true, {can_be_null:true,min:0});
			div.appendChild(income.getHTMLElement());
			income.onchange.add_listener(function() { t.info.income = income.getCurrentData(); });
			div.appendChild(document.createTextNode(" Frequency: "));
			var freq = new field_text(this.info.income_freq, true, {can_be_null:true,max_length:25});
			div.appendChild(freq.getHTMLElement());
			freq.onchange.add_listener(function() { t.info.income_freq = freq.getCurrentData(); });
		} else if (this.info.income > 0) {
			div.appendChild(document.createTextNode(this.info.income));
			if (this.info.income_freq)
				div.appendChild(document.createTextNode(" frequency: "+this.info.income_freq));
		}
		// Other
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.innerHTML = "<b>Other Information:</b> ";
		var div = document.createElement("DIV");
		container.appendChild(div);
		if (can_edit) {
			var other = document.createElement("TEXTAREA");
			div.appendChild(other);
			if (this.info.other) other.value = this.info.other;
			other.onchange = function() { t.info.other = this.value; };
		} else if (this.info.other)
			div.appendChild(document.createTextNode(this.info.other));
		
		if (can_edit) {
			div = document.createElement("DIV");
			container.appendChild(div);
			var remove = document.createElement("BUTTON");
			remove.className = "action red";
			remove.innerHTML = "Remove Fishing Information";
			div.appendChild(remove);
			remove.onclick = function() {
				t.info = null;
				t._initNoInfo();
			};
		}
	};
	this.save = function(ondone) {
		var locker = lock_screen(null,"Saving Fishing Information...");
		service.json("selection","si/save_fishing",{applicant:applicant_id,fishing:this.info},function(res) {
			unlock_screen(locker);
			ondone();
		});
	};
	if (!info) this._initNoInfo();
	else this._initFishing();
}
var applicant_fishing = new fishing(section_fishing.content, <?php echo json_encode($fishing);?>, <?php echo $people_id;?>, <?php echo $edit ? "true" : "false";?>);

var section_goods = sectionFromHTML('section_goods');

function edit() {
	location.href = "?people=<?php echo $people_id;?>&edit=true";
}
function cancelEdit() {
	location.href = "?people=<?php echo $people_id;?>&edit=false";
}
function save() {
	fam.save(function() {
		applicant_houses.save(function() {
			applicant_farm.save(function() {
				applicant_fishing.save(function() {
				});
			});
		});
	});
}
</script>
<?php 
	}
	
}
?>