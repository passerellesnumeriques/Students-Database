<?php 
class page_si_applicant extends Page {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function execute() {
		$people_id = $_GET["people"];
		
		$campaign = PNApplication::$instance->selection->getCampaignFromApplicant($people_id);
		if ($campaign == null || $campaign["sm"] == null) {
			PNApplication::error("Invalid people id: not an applicant");
			return;
		}
		$campaign_id = $campaign["id"];
		$calendar_id = $campaign["calendar"];
		
		if ($campaign_id == PNApplication::$instance->selection->getCampaignId()) {
			$can_edit = PNApplication::$instance->user_management->has_right("edit_social_investigation");
			$edit = $can_edit && @$_GET["edit"] == "true";
		} else
			$can_edit = false; 

		SQLQuery::setSubModel("SelectionCampaign", $campaign_id);
		
		$visits = SQLQuery::create()->select("SocialInvestigation")->whereValue("SocialInvestigation","applicant",$people_id)->field("event")->executeSingleField();
		if (count($visits) > 0) {
			require_once 'component/calendar/CalendarJSON.inc';
			$visits = CalendarJSON::getEventsFromDB($visits, $this->component->getCalendarId());
			$peoples_ids = array();
			foreach ($visits as $event)
				foreach ($event["attendees"] as $a)
					if ($a["people"] <> null && !in_array($a["people"], $peoples_ids))
						array_push($peoples_ids, $a["people"]);
			$investigators = PNApplication::$instance->people->getPeoples($peoples_ids, true, false, true, true);
			$can_do = SQLQuery::create()->select("StaffStatus")->whereIn("StaffStatus","people",$peoples_ids)->field("people")->field("si")->execute();
			$peoples = "[";
			$first = true;
			foreach ($investigators as $i) {
				$can_do_si = false;
				foreach ($can_do as $cd) if ($cd["people"] == $i["people_id"]) { $can_do_si = $cd["si"]; break; }
				if ($first) $first = false; else $peoples .= ",";
				$peoples .= "{can_do:".json_encode($can_do_si).",people:".PeopleJSON::People($i)."}";
			}
			$peoples .= "]";
		} else {
			$peoples = "[]";
		}
		$family = PNApplication::$instance->family->getFamily($people_id, "Child");
		$houses = SQLQuery::create()->select("SIHouse")->whereValue("SIHouse","applicant",$people_id)->execute();
		$farm = SQLQuery::create()->select("SIFarm")->whereValue("SIFarm", "applicant", $people_id)->executeSingleRow();
		$farm_prod = SQLQuery::create()->select("SIFarmProduction")->whereValue("SIFarmProduction","applicant",$people_id)->execute();
		$fishing = SQLQuery::create()->select("SIFishing")->whereValue("SIFishing","applicant",$people_id)->executeSingleRow();
		$q = SQLQuery::create()->select("SIPicture")->whereValue("SIPicture","applicant",$people_id);
		PNApplication::$instance->storage->joinRevision($q, "SIPicture", "picture", "revision");
		$pictures = $q->field("SIPicture","picture","id")->execute();
		$belongings = SQLQuery::create()->select("SIBelonging")->whereValue("SIBelonging","applicant",$people_id)->execute();
		$other_incomes = SQLQuery::create()->select("SIIncome")->whereValue("SIIncome","applicant",$people_id)->execute();
		$helps = SQLQuery::create()->select("SIHelpIncome")->whereValue("SIHelpIncome","applicant",$people_id)->execute();
		$health = SQLQuery::create()->select("SIHealth")->whereValue("SIHealth","applicant",$people_id)->execute();
		$expenses = SQLQuery::create()->select("SIExpense")->whereValue("SIExpense","applicant",$people_id)->execute();
		$global_comment = SQLQuery::create()->select("SIGlobalComment")->whereValue("SIGlobalComment","applicant",$people_id)->field("comment")->executeSingleValue();
		
		$locked_by = null;
		if ($edit) {
			require_once 'component/data_model/DataBaseLock.inc';
			if ($family[0]["id"] > 0) {
				$lock_family = DataBaseLock::lockRow("Family", $family[0]["id"], $locked_by, true);
				if ($lock_family <> null) {
					DataBaseLock::generateScript($lock_family);
				}
			}
			if ($locked_by == null) {
				$lock_applicant = DataBaseLock::lockRow("Applicant_".$this->component->getCampaignId(), $people_id, $locked_by);
				if ($lock_applicant <> null) {
					DataBaseLock::generateScript($lock_applicant);
				}
			}
		}
		
		$this->requireJavascript("section.js");
		$this->requireJavascript("family.js");
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_integer.js");
		$this->requireJavascript("field_decimal.js");
		$this->requireJavascript("field_enum.js");
		$this->requireJavascript("field_text.js");
		$this->requireJavascript("si_houses.js");
		$this->requireJavascript("si_farm.js");
		$this->requireJavascript("si_fishing.js");
		$this->requireJavascript("si_belongings.js");
		$this->requireJavascript("si_other_incomes.js");
		$this->requireJavascript("si_help_incomes.js");
		$this->requireJavascript("si_health.js");
		$this->requireJavascript("si_other_expenses.js");
		$this->requireJavascript("multiple_choice_other.js");
		$this->requireJavascript("pictures_section.js");
		$this->requireJavascript("who.js");
		$this->requireJavascript("calendar_objects.js");
		$this->addStylesheet("/static/selection/si/si_houses.css");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
<?php
if ($locked_by <> null) {
	echo "<div class='error_box' style='flex:none'>You cannot edit data for this applicant because it is currently edited by $locked_by.</div>";
} 
?>
	<div style='flex:1 1 auto;overflow:auto;'>
		<div id='section_family' title="Family" icon="/static/family/family_white_16.png" collapsable="true" style='margin:5px;display:inline-block;vertical-align:top'>
			<div id='family_container'></div>
		</div>
		<div id='section_visits' title="Visits" collapsable="true" style='margin:5px;display:inline-block;vertical-align:top'>
			<div>
			</div>
		</div>
		<div id='section_pictures' title="Pictures" collapsable="true" style='margin:5px;display:inline-block;vertical-align:top'>
			<div>
			</div>
		</div>
		<div id='section_residence' title="Residence Status" collapsable="true" style='margin:5px;display:inline-block;vertical-align:top'>
			<div>
				<div id='section_houses' title="Houses" icon="/static/selection/si/house_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
				</div>
				<div id='section_goods' title="Goods / Belongings / Furnitures" icon="/static/selection/si/tv_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
				</div>
			</div>
		</div>
		<div id='section_incomes' title="Economic Activites / Incomes" collapsable="true" style='margin:5px;display:inline-block;vertical-align:top'>
			<div>
				<div id='section_farm' title="Farm" icon="/static/selection/si/farm_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
				</div>
				<div id='section_fishing' title="Fishing" icon="/static/selection/si/fish_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
				</div>
				<div id='section_other_incomes' title="Other incomes" icon="/static/selection/si/money.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
				</div>
				<div id='section_help' title="Helping NGO / Sponsorships..." icon="/static/selection/si/helping.gif" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
				</div>
			</div>
		</div>
		<div id='section_expenses' title="Health / Expenses" collapsable="true" style='margin:5px;display:inline-block;vertical-align:top'>
			<div>
				<div id='section_health' title="Health" icon="/static/health/health.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
				</div>
				<div id='section_other_expenses' title="Other expenses" icon="/static/selection/si/money.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
				</div>
			</div>
		</div>
		<div id='section_global_comment' title="Global Comment" collapsable="true" style='margin:5px;vertical-align:top'>
			<div style='padding:5px;padding-right:10px'>
				<textarea id='global_comment' style='width:100%;' rows=10 <?php if (!$edit) echo "disabled='disabled'";?>><?php if ($global_comment <> null) echo toHTML($global_comment);?></textarea>
			</div>
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

var section_visits = sectionFromHTML('section_visits');
function si_visits(section, events, calendar_id, applicant_id, known_peoples, can_edit) {
	section.content.style.padding = "5px";
	this.events = events;
	this.whos = [];
	var t=this;
	this.createEvent = function(event) {
		if (this.events.length == 1) section.content.innerHTML = ""; // first one, remove the 'None'
		var container = document.createElement("DIV");
		section.content.appendChild(container);
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.innerHTML = "<b>When ?</b> ";
		var date = document.createElement(can_edit ? "A" : "SPAN");
		div.appendChild(date);
		if (can_edit) {
			var updateText = function() {
				if (!event.start) date.innerHTML = "Not set";
				else {
					var d = new Date();
					d.setFullYear(event.start.getUTCFullYear());
					d.setMonth(event.start.getUTCMonth());
					d.setDate(event.start.getUTCDate());
					date.innerHTML = getDateString(d);
				}
			};
			updateText();
			date.href = '#';
			date.className = "black_link";
			date.onclick = function() {
				require(["date_picker.js","context_menu.js"],function() {
					var menu = new context_menu();
					new date_picker(event.start,null,null,function(picker){
						picker.onchange = function(picker, date) {
							event.start = new Date();
							event.start.setHours(0,0,0,0);
							event.start.setUTCFullYear(date.getFullYear());
							event.start.setUTCMonth(date.getMonth());
							event.start.setUTCDate(date.getDate());
							updateText();
						};
						picker.getElement().style.border = 'none';
						menu.addItem(picker.getElement());
						picker.getElement().onclick = null;
						menu.element.className = menu.element.className+" popup_date_picker";
						menu.showBelowElement(date);
					});
				});
				return false;
			};
			var remove = document.createElement("BUTTON");
			remove.className = "flat small_icon";
			remove.style.marginLeft = "5px";
			remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
			remove.title = "Remove this visit";
			div.appendChild(remove);
			remove.onclick = function() {
				var i = t.events.indexOd(event);
				t.events.splice(i,1);
				t.whos.splice(i,1);
				section.content.removeChild(container);
			};
		} else {
			var d = new Date();
			d.setFullYear(event.start.getUTCFullYear());
			d.setMonth(event.start.getUTCMonth());
			d.setDate(event.start.getUTCDate());
			date.appendChild(document.createTextNode(getDateString(d)));
		}
		div = document.createElement("DIV");
		div.innerHTML = "<b>Who ?</b>";
		container.appendChild(div);
		var peoples = [];
		for (var i = 0; i < event.attendees.length; ++i) {
			if (event.attendees[i].organizer) continue;
			if (event.attendees[i].people) {
				for (var j = 0; j < known_peoples.length; ++j)
					if (known_peoples[j].people.id == event.attendees[i].people) {
						peoples.push(known_peoples[j]);
						break;
					}
			} else
				peoples.push(event.attendees[i].name);
		}
		var who = new who_container(container,peoples,can_edit,'si');
		container.appendChild(who.createAddButton("Which Social Investigators ?"));
		this.whos.push(who);
	};
	if (events.length == 0) section.content.innerHTML = "<i>None</i>";
	for (var i = 0; i < events.length; ++i) this.createEvent(events[i]);
	if (can_edit) {
		var add_button = document.createElement("BUTTON");
		add_button.className = "flat icon";
		add_button.innerHTML = "<img src='"+theme.build_icon("/static/calendar/calendar_16.png",theme.icons_10.add)+"'/>";
		add_button.title = "Schedule a new visit";
		section.addToolRight(add_button);
		var id_counter = -1;
		add_button.onclick = function() {
			var ev = new CalendarEvent(id_counter--, 'PN', calendar_id, null, null, null, true);
			t.events.push(ev);
			t.createEvent(ev);
		};
		this.save = function(ondone) {
			var locker = lock_screen(null, "Saving Visits Schedules...");
			for (var i = 0; i < this.events.length; ++i) {
				this.events[i].attendees = [];
				for (var j = 0; j < this.whos[i].peoples.length; ++j) {
					if (typeof this.whos[i].peoples[j] == 'string') {
						this.events[i].attendees.push(new CalendarEventAttendee(this.whos[i].peoples[j]));
					} else {
						this.events[i].attendees.push(new CalendarEventAttendee(null, null, null, null, null, null, this.whos[i].peoples[j].people.id));
					}
				}
			}
			service.json("selection","si/save_visits",{applicant:applicant_id,visits:this.events},function(res) {
				pnapplication.dataSaved('who');
				if (res && res.length > 0)
					for (var i = 0; i < res.length; ++i)
						for (var j = 0; j < t.events.length; ++j)
							if (t.events[j].id == res[i].given_id) { t.events[j].id = res[i].new_id; break; }
				unlock_screen(locker);
				ondone();
			});
		};
	}
}
var visits = new si_visits(section_visits, <?php if (count($visits) == 0) echo "[]"; else echo CalendarJSON::JSONList($visits);?>, <?php echo $calendar_id;?>, <?php echo $people_id;?>, <?php echo $peoples;?>, can_edit);

var section_pictures = sectionFromHTML('section_pictures');
new pictures_section(section_pictures, <?php echo json_encode($pictures);?>, 200, 200, can_edit, "selection", "si/add_picture", {applicant:<?php echo $people_id;?>});

sectionFromHTML('section_residence');
sectionFromHTML('section_incomes');
sectionFromHTML('section_expenses');

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
var applicant_fishing = new fishing(section_fishing.content, <?php echo json_encode($fishing);?>, <?php echo $people_id;?>, <?php echo $edit ? "true" : "false";?>);

var section_other_incomes = sectionFromHTML('section_other_incomes');
var other_incomes = new si_other_incomes(section_other_incomes, <?php echo json_encode($other_incomes);?>, <?php echo $people_id;?>, <?php echo $edit ? "true" : "false";?>);

var section_help = sectionFromHTML('section_help');
var help_incomes = new si_help_incomes(section_help, <?php echo json_encode($helps);?>, <?php echo $people_id;?>, <?php echo $edit ? "true" : "false";?>);

var section_goods = sectionFromHTML('section_goods');
var applicant_belongings = new belongings(section_goods, <?php echo json_encode($belongings);?>, <?php echo $people_id;?>, <?php echo $edit ? "true" : "false";?>);

var section_health = sectionFromHTML('section_health');
var health = new si_health(section_health, <?php echo json_encode($health);?>, <?php echo $people_id;?>, <?php echo $edit ? "true" : "false";?>);

var section_other_expenses = sectionFromHTML('section_other_expenses');
var expenses = new si_other_expenses(section_other_expenses, <?php echo json_encode($expenses);?>, <?php echo $people_id;?>, <?php echo $edit ? "true" : "false";?>);

var section_global_comment = sectionFromHTML('section_global_comment');
var has_global_comment = <?php echo $global_comment <> null ? "true" : "false";?>;

function edit() {
	location.href = "?people=<?php echo $people_id;?>&edit=true";
}
function cancelEdit() {
	location.href = "?people=<?php echo $people_id;?>&edit=false";
}
window.onuserinactive = cancelEdit;
function save() {
	visits.save(function() {
		fam.save(function() {
			applicant_houses.save(function() {
				applicant_farm.save(function() {
					applicant_fishing.save(function() {
						applicant_belongings.save(function() {
							other_incomes.save(function() {
								help_incomes.save(function() {
									health.save(function() {
										expenses.save(function() {
											var comment = document.getElementById('global_comment').value;
											var locker = lock_screen(null, "Saving Global Comment...");
											if (has_global_comment) {
												service.json("data_model","save_cell",{table:'SIGlobalComment',column:'comment',value:comment,row_key:<?php echo $people_id;?>,sub_model:<?php echo $this->component->getCampaignId();?>,lock:null},function(res) {
													unlock_screen(locker);
												});
											} else {
												service.json("data_model","add_row",{table:'SIGlobalComment',columns:{applicant:<?php echo $people_id;?>,comment:comment},sub_model:<?php echo $this->component->getCampaignId();?>},function(res) {
													if (res) has_global_comment = true;
													unlock_screen(locker);
												});
											}
										});
									});
								});
							});
						});
					});
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