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
		$this->requireJavascript("multiple_choice_other.js");
		$this->addStylesheet("/static/selection/si/si_houses.css");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div style='flex:1 1 auto;overflow:auto;'>
		<div id='section_family' title="Family" icon="/static/family/family_white_16.png" collapsable="true" style='margin:5px;display:inline-block;vertical-align:top'>
			<div id='family_container'></div>
		</div>
		<div id='section_visits' title="Visits" collapsable="true" style='margin:5px;display:inline-block;'>
			<div>
			</div>
		</div>
		<div id='section_pictures' title="Pictures" collapsable="true" style='margin:5px;display:inline-block;'>
			<div>
			</div>
		</div>
		<div id='section_residence' title="Residence Status" collapsable="true" style='margin:5px;display:inline-block;'>
			<div>
				<div id='section_houses' title="Houses" icon="/static/selection/si/house_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
				</div>
				<div id='section_goods' title="Goods/Belongings" icon="/static/selection/si/tv_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
				</div>
			</div>
		</div>
		<div id='section_incomes' title="Economic Activites / Incomes" collapsable="true" style='margin:5px;display:inline-block;'>
			<div>
				<div id='section_farm' title="Farm" icon="/static/selection/si/farm_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
				</div>
				<div id='section_fishing' title="Fishing" icon="/static/selection/si/fish_16.png" collapsable="true" css='soft' style='margin:5px;display:inline-block;vertical-align:top'>
				</div>
			</div>
		</div>
		<div id='section_expenses' title="Health / Expenses" collapsable="true" style='margin:5px;display:inline-block;'>
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

var section_pictures = sectionFromHTML('section_pictures');
function pictures_section(section, pictures, max_width, max_height, can_edit, component, add_picture_service, add_picture_data) {
	this.pictures = pictures;
	this.createPicture = function(picture) {
		var div = document.createElement("DIV");
		div.style.display = "inline-block";
		div.style.position = "relative";
		div.style.width = max_width+"px";
		div.style.height = max_height+"px";
		div.style.border = "1px solid black";
		setBorderRadius(div,3,3,3,3,3,3,3,3);
		div.style.margin = "1px 2px";
		var img = document.createElement("IMG");
		img.style.width = max_width+"px";
		img.style.height = max_height+"px";
		img.style.position = "absolute";
		img.onload = function() {
			var w = img.naturalWidth;
			var h = img.naturalHeight;
			var ratio = 1;
			if (w > max_width) ratio = max_width/w;
			if (h > max_height && max_height/h < ratio) ratio = max_height/h;
			w = Math.floor(w*ratio);
			h = Math.floor(h*ratio);
			img.style.width = w+"px";
			img.style.height = h+"px";
			img.style.left = Math.floor((max_width-w)/2)+"px";
			img.style.top = Math.floor((max_height-h)/2)+"px";
		};
		img.src = "/dynamic/storage/service/get?id="+picture.id+"&revision="+picture.revision;
		div.appendChild(img);
		section.content.appendChild(div);
	};
	for (var i = 0; i < pictures.length; ++i) this.createPicture(pictures[i]);
	if (can_edit) {
		var add_button = document.createElement("BUTTON");
		add_button.innerHTML = "<img src='/static/storage/import_image_16.png'/> Upload pictures";
		add_button.className = "action";
		section.addToolBottom(add_button);
		var t=this;
		add_button.onclick = function(ev) {
			var upl = createUploadTempFile(true, 10);
			upl.addUploadPopup('/static/storage/import_image_16.png',"Uploading pictures");
			upl.ondonefile = function(file, output, errors, warnings) {
				if (output && output.id) {
					var data = objectCopy(add_picture_data, 10);
					data.id = output.id;
					data.revision = output.revision;
					service.json(component, add_picture_service, data, function(res) {
						if (res) {
							t.pictures.push(output);
							t.createPicture(output);
						}
					}); 
				}
			};
			upl.openDialog(ev, "image/*");
		};
		add_button.disabled = "disabled";
		require("upload.js", function() {
			add_button.disabled = "";
		});
	}
}
new pictures_section(section_pictures, <?php echo json_encode($pictures);?>, 150, 150, can_edit, "selection", "si/add_picture", {applicant:<?php echo $people_id;?>});

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