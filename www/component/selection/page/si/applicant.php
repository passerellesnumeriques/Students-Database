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
			});
		});
	});
}
</script>
<?php 
	}
	
}
?>