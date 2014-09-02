<?php 
/* @var $model DataModel */

$category = new datamodel\DataDisplayCategory("Personal Information", "/static/people/profile_16.png", "/dynamic/people/page/profile?plugin=people&people=%People.id%");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("People", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("first_name", "First Name"));
if (PNApplication::$instance->current_domain == "PNP")
	$display->addDataDisplay(new datamodel\SimpleDataDisplay("middle_name", "Middle Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("last_name", "Last Name"));
if (PNApplication::$instance->current_domain == "PNC")
	$display->addDataDisplay(new datamodel\SimpleDataDisplay("khmer_first_name", "Khmer First Name"));
if (PNApplication::$instance->current_domain == "PNC")
	$display->addDataDisplay(new datamodel\SimpleDataDisplay("khmer_last_name", "Khmer Last Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("sex", "Gender", false, true, "center"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("birth", "Birth Date"));
$display->addDataDisplay(new datamodel\CalculationDataDisplay("Age","TIMESTAMPDIFF(YEAR,`birth`,CURDATE())"));
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

class PeopleScreen extends datamodel\GenericDataScreen {
	
	public function generate(&$page, $paths, $values, $prefilled_values, $structure_name) {
		if ($values->hasColumnValue("People", "id")) {
			// existing people => add the picture
			$picture_td_id = $page->generateID();
			$picture_container_id = $page->generateID();
			$people_id = $values->getColumnValue("People", "id");
			echo "<table style='border-spacing:0px;'><tr><td id='$picture_td_id' valign=top align=center style='padding:2px 0px 0px 2px'><div id='$picture_container_id'></div><button id='change_picture' disabled='disabled'>Change picture</button></td><td valign=top style='padding-left:0px;padding-right:0px'>";
			parent::generate($page, $paths, $values, $prefilled_values, $structure_name);
			echo "</td></tr></table>";
			$page->requireJavascript("profile_picture.js");
			echo "<script type='text/javascript'>";
			echo "var picture = new profile_picture('$picture_container_id',150,150,'center','middle');";
			echo "picture.loadPeopleID($people_id);";
			echo "</script>";
			if (PNApplication::$instance->people->canModify($people_id)) {
				$page->requireJavascript("images_tool.js");
				?>
				<script type='text/javascript'>
				var tool = new images_tool();
				tool.usePopup(true, function() {
					tool.popup.freeze("Saving picture...");
					var data = tool.getPictures()[0].getResultData();
					service.json("people", "save_picture", {id:<?php echo $people_id;?>,picture:data}, function(res) {
						if (res) {
							window.top.datamodel.cellChanged("People","picture",<?php echo $people_id;?>,-1);
							//picture.reload();
							tool.popup.close();
						} else
							tool.popup.unfreeze();
					});
				});
				tool.useUpload();
				tool.useFaceDetection();
				tool.addTool("crop",function() {
					//tool.setToolValue("crop", null, {aspect_ratio:0.75}, true);
				});
				tool.addTool("scale", function() {
					tool.setToolValue("scale", null, {max_width:300,max_height:300}, false);
				});
				tool.init(function() {
					var button = document.getElementById('change_picture');
					button.disabled = "";
					button.onclick = function(ev) {
						tool.reset();
						tool.launchUpload(ev, false);
					};
				});
				var td_picture = document.getElementById('<?php echo $picture_td_id;?>');
				td_picture.ondragenter = function(event) {
					if (event.dataTransfer.types.contains("Files")) {
						this.style.backgroundColor = "#D0D0D0";
						this.style.outline = "5px dotted #808080";
						event.dataTransfer.dropEffect = "copy";
						event.preventDefault();
						return true;
					}
				};
				td_picture.ondragover = function(event) {
					if (event.dataTransfer.types.contains("Files")) {
						this.style.backgroundColor = "#D0D0D0";
						this.style.outline = "5px dotted #808080";
						event.dataTransfer.dropEffect = "copy";
						event.preventDefault();
						return false;
					}
				};
				td_picture.ondragleave = function(event) {
					this.style.backgroundColor = "";
					this.style.outline = "";
				};
				td_picture.ondrop = function(event) {
					this.style.backgroundColor = "";
					this.style.outline = "";
					if (event.dataTransfer.types.contains("Files")) {
						if (event.dataTransfer.files.length == 0) return;
						var first_file = event.dataTransfer.files[0];
						tool.launchUpload(null, false, [first_file]);
					}
					event.preventDefault();
					return false;
				};
				</script>
				<?php
			} 
		} else
			parent::generate($page, $paths, $values, $prefilled_values, $structure_name);
	}
	
}

$model->addDataScreen(new PeopleScreen($display));

?>