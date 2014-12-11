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
		$q = SQLQuery::create()->select("SIPicture")->whereValue("SIPicture","applicant",$people_id);
		PNApplication::$instance->storage->joinRevision($q, "SIPicture", "picture", "revision");
		$pictures = $q->field("SIPicture","picture","id")->execute();
		$belongings = SQLQuery::create()->select("SIBelonging")->whereValue("SIBelonging","applicant",$people_id)->execute();
		
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
		$this->requireJavascript("multiple_choice_other.js");
		$this->requireJavascript("pictures_section.js");
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

sectionFromHTML('section_visits');
// TODO date, who

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
// TODO descr, amount, frequency, comment
var section_help = sectionFromHTML('section_help');
// TODO who, amount, frequency, comment

var section_goods = sectionFromHTML('section_goods');
var applicant_belongings = new belongings(section_goods, <?php echo json_encode($belongings);?>, <?php echo $people_id;?>, <?php echo $edit ? "true" : "false";?>);

function edit() {
	location.href = "?people=<?php echo $people_id;?>&edit=true";
}
function cancelEdit() {
	location.href = "?people=<?php echo $people_id;?>&edit=false";
}
window.onuserinactive = cancelEdit;
function save() {
	fam.save(function() {
		applicant_houses.save(function() {
			applicant_farm.save(function() {
				applicant_fishing.save(function() {
					applicant_belongings.save(function() {
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