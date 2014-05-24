<?php 
/* @var $model DataModel */

$category = new datamodel\DataDisplayCategory("Personal Information", "/static/people/profile_16.png", "/dynamic/people/page/profile?plugin=people&people=%People.id%");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("People", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("first_name", "First Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("middle_name", "Middle Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("last_name", "Last Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("khmer_first_name", "Khmer First Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("khmer_last_name", "Khmer Last Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("sex", "Gender", false, true, "center"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("birth", "Birth Date"));
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

class PeopleScreen extends datamodel\GenericDataScreen {
	
	public function generate(&$page, $paths, $values, $prefilled_values, $structure_name) {
		if ($values->hasColumnValue("People", "id")) {
			// existing people => add the picture
			$picture_container_id = $page->generateID();
			$people_id = $values->getColumnValue("People", "id");
			echo "<table><tr><td valign=top align=center><div id='$picture_container_id'></div><button id='change_picture' disabled='disabled'>Change picture</button></td><td valign=top>";
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
							picture.reload();
							tool.popup.close();
						} else
							tool.popup.unfreeze();
					});
				});
				tool.useUpload();
				tool.useFaceDetection();
				tool.addTool("crop",function() {
					tool.setToolValue("crop", null, {aspect_ratio:0.75}, true);
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
				</script>
				<?php
			} 
		} else
			parent::generate($page, $paths, $values, $prefilled_values, $structure_name);
	}
	
}

$model->addDataScreen(new PeopleScreen($display));

?>