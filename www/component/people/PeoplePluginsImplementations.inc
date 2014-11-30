<?php
require_once("PeopleProfilePagePlugin.inc");
class PeopleProfilePage implements PeopleProfilePagePlugin {
	
	public function isValidFor($people_id, $types) { return true; }
	public function getPriority() { return 0; }
	public function getIcon() { return "/static/people/profile_16.png"; }
	public function getName() { return "General Information"; }
	public function getTooltip() { return "Civil information, contacts (emails, phone...), and other general information"; }
	public function getURL($people_id) { return "/dynamic/people/page/profile_people?people=".$people_id; }
	
}

class PeopleDocumentsPage implements PeopleProfilePagePlugin {

	public function isValidFor($people_id, $types) { return true; }
	public function getPriority() { return 10000; }
	public function getIcon() { return "/static/documents/documents_16.png"; }
	public function getName() { return "Documents"; }
	public function getTooltip() { return "Files attached to this person"; }
	public function getURL($people_id) { return "/dynamic/people/page/people_docs?people=".$people_id; }

}

require_once("PeopleCreationStepPlugin.inc");
class PeopleCreationAddPicturesPlugin implements PeopleCreationStep {
	
	public function getId() { return "import_pictures"; }
	
	public function isApplicable($people) {
		return true;
	}
	
	public function generatePageFor($peoples_ids, &$page) {
		$page->requireJavascript("images_tool.js");
		$page->requireJavascript("images_tool_crop.js");
		$page->requireJavascript("images_tool_face_detection.js");
		$page->requireJavascript("images_tool_scale.js");
		$page->requireJavascript("images_tool_people.js");
		$page->requireJavascript("upload.js");
		$page->requireJavascript("progress_bar.js");
		$page->requireJavascript("section.js");
		$page->requireJavascript("ccv.js");
		$page->requireJavascript("face.js");
		
		$peoples = PNApplication::$instance->people->getPeoples($peoples_ids, false, false, false, true);
?>
<div style='background-color:white;padding:10px'>
Would you like to import pictures for the new people ?<br/>
<button class='action' onclick='doImport(event);'>
	<img src='/static/images_tool/people_picture.png'/>
	Import Pictures
</button>
</div>
<script type='text/javascript'>
var w=window;
window.popup.addContinueButton(function() { w.stepDone(); });
tool = new images_tool();
tool.usePopup(true, function() {
	var pictures = [];
	for (var i = 0; i < tool.getPictures().length; ++i) pictures.push(tool.getPictures()[i]);
	var nb = 0;
	for (var i = 0; i < pictures.length; ++i)
		if (tool.getTool("people").getPeople(pictures[i]))
			nb++;
	if (nb == 0) return;
	tool.popup.freeze_progress("Saving pictures...", nb, function(span_message, progress_bar) {
		var next = function(index) {
			if (index == pictures.length) {
				if (tool.getPictures().length > 0) {
					tool.popup.unfreeze();
					return;
				}
				tool.popup.close();
				return;
			}
			var people = tool.getTool("people").getPeople(pictures[index]);
			if (!people) {
				next(index+1);
				return;
			}
			span_message.removeAllChildren();
			span_message.appendChild(document.createTextNode("Saving picture for "+people.first_name+" "+people.last_name));
			var data = pictures[index].getResultData();
			service.json("people", "save_picture", {id:people.id,picture:data}, function(res) {
				if (res)
					tool.removePicture(pictures[index]);
				progress_bar.addAmount(1);
				next(index+1);
			});
		};
		next(0);
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
tool.addTool("people", function() {});
tool.init(function() {
	tool.setToolValue("people", null, <?php echo PeopleJSON::Peoples($peoples);?>, false);
});
function doImport(ev) {
	tool.launchUpload(ev, true);
}
</script>
<?php 
	}
	
}

?>