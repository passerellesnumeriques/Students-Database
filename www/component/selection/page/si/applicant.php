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
}
</script>
<?php 
	}
	
}
?>